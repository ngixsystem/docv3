<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentStatusHistory;
use App\Models\Group;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Document::query()
            ->visibleTo($user)
            ->with([
                'sender:id,name',
                'recipient:id,name',
                'recipientGroup:id,name',
                'executor:id,name',
                'files:id,document_id',
            ])
            ->select([
                'id', 'number', 'type', 'subject', 'status', 'doc_date',
                'sender_id', 'recipient_id', 'recipient_group_id', 'executor_id',
                'sender_org', 'recipient_org',
            ])
            ->latest();

        if ($type = $request->type) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ilike', "%{$search}%")
                    ->orWhere('number', 'ilike', "%{$search}%")
                    ->orWhere('sender_org', 'ilike', "%{$search}%")
                    ->orWhere('recipient_org', 'ilike', "%{$search}%")
                    ->orWhereHas('recipientGroup', fn ($groups) => $groups->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $documents = $query->paginate(20)->withQueryString();
        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)
                ->select(['id', 'name', 'department_id'])
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
        );
        $groups = Group::withCount('users')->orderBy('name')->get(['id', 'name']);

        return view('documents.index', compact('documents', 'users', 'groups', 'user'));
    }

    public function create()
    {
        $user = Auth::user();
        abort_unless(!empty($user->allowedDocumentTypes()), 403);

        return view('documents.create', $this->getDocumentFormData($user));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate($this->documentRules($user));
        $relatedDocumentIds = $this->validatedRelatedDocumentIds($user, $validated);

        unset($validated['related_document_ids']);

        $validated['created_by'] = $user->id;
        $validated['number'] = Document::generateNumber($validated['type']);
        $validated['status'] = 'draft';

        $doc = Document::create($validated);

        DocumentStatusHistory::create([
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'from_status' => null,
            'to_status' => 'draft',
            'comment' => 'Документ создан',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $this->storeFile($doc, $file, $user->id);
            }
        }

        $this->storeRelatedDocuments($doc, $relatedDocumentIds);

        return redirect()->route('documents.show', $doc)
            ->with('success', 'Документ создан: ' . $doc->number);
    }

    public function edit(Document $document)
    {
        $user = Auth::user();
        abort_unless($user->canEditDocument($document), 403);

        $document->load([
            'relatedDocuments:id,number,subject,doc_date',
            'reverseRelatedDocuments:id,number,subject,doc_date',
        ]);

        return view('documents.edit', array_merge(
            $this->getDocumentFormData($user, $document),
            ['document' => $document]
        ));
    }

    public function update(Request $request, Document $document)
    {
        $user = Auth::user();
        abort_unless($user->canEditDocument($document), 403);

        $validated = $request->validate($this->documentRules($user));
        $relatedDocumentIds = $this->validatedRelatedDocumentIds($user, $validated, $document);

        unset($validated['related_document_ids']);

        $document->update($validated);
        $this->syncRelatedDocuments($document, $relatedDocumentIds);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Документ обновлен: ' . $document->number);
    }

    public function destroy(Document $document)
    {
        $user = Auth::user();
        abort_unless($user->canDeleteDocument($document), 403);

        $document->loadMissing('files');

        foreach ($document->files as $file) {
            if ($file->path) {
                Storage::disk('public')->delete($file->path);
            }
        }

        $number = $document->number;
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Документ удален: ' . $number);
    }

    public function show(Document $document)
    {
        $user = Auth::user();
        abort_unless($user->canViewDocument($document), 403);

        $document->load([
            'sender:id,name,department_id',
            'sender.department:id,name',
            'recipient:id,name,department_id',
            'recipient.department:id,name',
            'recipientGroup:id,name',
            'executor:id,name,department_id',
            'executor.department:id,name',
            'createdBy:id,name,department_id',
            'files',
            'files.uploader:id,name',
            'comments.user:id,name',
            'statusHistory.user:id,name',
            'tasks.assignee:id,name',
            'relatedDocuments:id,number,subject,type,status,doc_date',
            'reverseRelatedDocuments:id,number,subject,type,status,doc_date',
        ]);

        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)
                ->select(['id', 'name', 'department_id'])
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
        );
        $groups = Group::withCount('users')->orderBy('name')->get(['id', 'name']);
        $relatedDocuments = $document->all_related_documents
            ->filter(fn (Document $relatedDocument) => $user->canViewDocument($relatedDocument))
            ->sortByDesc(fn (Document $relatedDocument) => optional($relatedDocument->doc_date)?->timestamp ?? 0)
            ->values();

        return view('documents.show', compact('document', 'users', 'groups', 'relatedDocuments'));
    }

    public function updateStatus(Request $request, Document $document)
    {
        $user = Auth::user();
        abort_unless($user->canViewDocument($document), 403);

        $validated = $request->validate([
            'status' => 'required|string',
            'comment' => 'nullable|string',
        ]);

        $allowed = Document::$transitions[$document->status] ?? [];
        if (!in_array($validated['status'], $allowed, true)) {
            return back()->with('error', 'Недопустимый переход статуса.');
        }

        if (!$user->canChangeDocumentStatus($document, $validated['status'])) {
            abort(403);
        }

        DocumentStatusHistory::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'from_status' => $document->status,
            'to_status' => $validated['status'],
            'comment' => $validated['comment'] ?? null,
        ]);

        $document->update(['status' => $validated['status']]);

        $this->notifyDocumentParticipants(
            $document,
            $user,
            'Статус документа изменён',
            $document->number . ': ' . Document::$statusNames[$validated['status']],
        );

        return back()->with('success', 'Статус изменен на: ' . Document::$statusNames[$validated['status']]);
    }

    public function addComment(Request $request, Document $document)
    {
        abort_unless(Auth::user()->canViewDocument($document), 403);

        $request->validate(['body' => 'required|string|max:2000']);
        $commenter = Auth::user();
        $document->comments()->create([
            'user_id' => $commenter->id,
            'body' => $request->body,
        ]);

        $this->notifyDocumentParticipants(
            $document,
            $commenter,
            'Новый комментарий к документу',
            $document->number . ': ' . mb_strimwidth($request->body, 0, 80, '…'),
        );

        return back()->with('success', 'Комментарий добавлен.');
    }

    private function storeFile(Document $doc, $file, int $userId): void
    {
        $storedName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('uploads', $storedName, 'public');
        $doc->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'path' => $path,
            'uploaded_by' => $userId,
        ]);
    }

    private function storeRelatedDocuments(Document $document, Collection $relatedDocumentIds): void
    {
        if ($relatedDocumentIds->isEmpty()) {
            return;
        }

        $rows = $relatedDocumentIds
            ->reject(fn (int $relatedId) => $relatedId === $document->id)
            ->map(function (int $relatedId) use ($document) {
                return [
                    'document_id' => min($document->id, $relatedId),
                    'related_document_id' => max($document->id, $relatedId),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->unique(fn (array $row) => $row['document_id'] . ':' . $row['related_document_id'])
            ->values()
            ->all();

        if (!empty($rows)) {
            DB::table('document_links')->insertOrIgnore($rows);
        }
    }

    private function syncRelatedDocuments(Document $document, Collection $relatedDocumentIds): void
    {
        DB::table('document_links')
            ->where('document_id', $document->id)
            ->orWhere('related_document_id', $document->id)
            ->delete();

        $this->storeRelatedDocuments($document, $relatedDocumentIds);
    }

    private function getDocumentFormData(User $user, ?Document $document = null): array
    {
        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)
                ->select(['id', 'name', 'department_id'])
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
        );
        $groups = Group::withCount('users')->orderBy('name')->get(['id', 'name']);
        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'details']);
        $relatedDocuments = Document::query()
            ->visibleTo($user)
            ->when($document, fn ($query) => $query->where('id', '!=', $document->id))
            ->select(['id', 'number', 'subject', 'doc_date', 'status', 'type'])
            ->latest('doc_date')
            ->latest('id')
            ->get();

        return compact('users', 'groups', 'companies', 'user', 'relatedDocuments');
    }

    private function documentRules(User $user): array
    {
        return [
            'type' => 'required|in:' . implode(',', $user->allowedDocumentTypes()),
            'subject' => 'required|string|max:500',
            'description' => 'nullable|string',
            'sender_id' => 'nullable|exists:users,id',
            'recipient_id' => 'nullable|exists:users,id',
            'recipient_group_id' => 'nullable|exists:groups,id',
            'sender_org' => 'nullable|string|max:255',
            'recipient_org' => 'nullable|string|max:255',
            'executor_id' => 'nullable|exists:users,id',
            'doc_date' => 'required|date',
            'deadline' => 'nullable|date',
            'related_document_ids' => 'nullable|array',
            'related_document_ids.*' => 'integer|distinct|exists:documents,id',
        ];
    }

    private function notifyDocumentParticipants(Document $document, User $actor, string $title, string $body): void
    {
        $url = route('documents.show', $document);
        $notification = new AppNotification($title, $body, $url);

        $document->loadMissing(['sender', 'recipient', 'executor', 'createdBy']);

        $recipients = collect([
            $document->sender,
            $document->recipient,
            $document->executor,
            $document->createdBy,
        ])
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => $u->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    private function validatedRelatedDocumentIds(User $user, array $validated, ?Document $document = null): Collection
    {
        $relatedDocumentIds = collect($validated['related_document_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $document && $id === $document->id)
            ->unique()
            ->values();

        if ($relatedDocumentIds->isEmpty()) {
            return $relatedDocumentIds;
        }

        $visibleRelatedIds = Document::query()
            ->visibleTo($user)
            ->when($document, fn ($query) => $query->where('id', '!=', $document->id))
            ->whereIn('id', $relatedDocumentIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($visibleRelatedIds) !== $relatedDocumentIds->count()) {
            throw ValidationException::withMessages([
                'related_document_ids' => 'Выбранные связанные документы недоступны.',
            ]);
        }

        return $relatedDocumentIds;
    }
}
