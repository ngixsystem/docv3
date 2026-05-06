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
        $documents = $this->documentIndexQuery($request, $user)->paginate(20)->withQueryString();
        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)
                ->select(['id', 'name', 'department_id'])
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
        );
        $groups = Group::withCount('users')->orderBy('name')->get(['id', 'name']);
        $pageTitle = 'Реестр документов';
        $cardTitle = 'Документы';
        $listRoute = route('documents.index');
        $showFilters = true;

        return view('documents.index', compact('documents', 'users', 'groups', 'user', 'pageTitle', 'cardTitle', 'listRoute', 'showFilters'));
    }

    public function drafts(Request $request)
    {
        $user = Auth::user();
        $documents = $this->documentIndexQuery($request, $user)
            ->where('status', 'draft')
            ->paginate(20)
            ->withQueryString();

        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)
                ->select(['id', 'name', 'department_id'])
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
        );
        $groups = Group::withCount('users')->orderBy('name')->get(['id', 'name']);
        $pageTitle = 'Черновики';
        $cardTitle = 'Незарегистрированные документы';
        $listRoute = route('documents.drafts');
        $showFilters = false;

        return view('documents.index', compact('documents', 'users', 'groups', 'user', 'pageTitle', 'cardTitle', 'listRoute', 'showFilters'));
    }

    private function documentIndexQuery(Request $request, User $user)
    {
        $with = [
            'sender:id,name',
            'recipientGroup:id,name',
            'recipients:id,name',
            'executor:id,name',
            'files:id,document_id',
        ];
        if (Document::hasExecutorPivotTable()) {
            $with[] = 'executors:id,name';
        }

        $query = Document::query()
            ->visibleTo($user)
            ->with($with)
            ->select([
                'id', 'number', 'type', 'subject', 'status', 'doc_date',
                'sender_id', 'recipient_group_id', 'executor_id',
                'sender_org', 'recipient_orgs',
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
                    ->orWhereRaw("recipient_orgs::text ilike ?", ["%{$search}%"])
                    ->orWhereHas('recipientGroup', fn ($groups) => $groups->where('name', 'ilike', "%{$search}%"));
            });
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        return $query;
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

        $recipientIds = array_map('intval', $validated['recipient_ids'] ?? []);
        $executorIds = array_map('intval', $validated['executor_ids'] ?? []);
        unset($validated['related_document_ids'], $validated['recipient_ids'], $validated['executor_ids']);
        $validated['recipient_orgs'] = array_values(array_filter($validated['recipient_orgs'] ?? []));
        $validated['executor_id'] = $executorIds[0] ?? null;

        if (($validated['type'] ?? null) === 'memo') {
            $recipientIds = User::where('is_active', true)
                ->where('role', 'clerk')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $validated['recipient_orgs'] = [];
        }

        $validated['created_by'] = $user->id;
        $validated['number'] = Document::generateNumber($validated['type']);
        $validated['status'] = 'draft';

        $doc = Document::create($validated);
        $doc->recipients()->sync($recipientIds);
        $this->syncExecutors($doc, $executorIds);

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

        if ($doc->type === 'memo') {
            $notification = new AppNotification(
                'Новая служебная записка',
                $doc->number . ': ' . $doc->subject,
                route('documents.show', $doc)
            );
            User::where('is_active', true)
                ->where('role', 'clerk')
                ->where('id', '!=', $user->id)
                ->get()
                ->each(fn (User $clerk) => $clerk->notify($notification));
        }

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
            'recipients:id,name',
            'executors:id,name',
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

        $recipientIds = array_map('intval', $validated['recipient_ids'] ?? []);
        $executorIds = array_map('intval', $validated['executor_ids'] ?? []);
        unset($validated['related_document_ids'], $validated['recipient_ids'], $validated['executor_ids']);
        $validated['recipient_orgs'] = array_values(array_filter($validated['recipient_orgs'] ?? []));
        $validated['executor_id'] = $executorIds[0] ?? null;

        $document->update($validated);
        $document->recipients()->sync($recipientIds);
        $this->syncExecutors($document, $executorIds);
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

        $with = [
            'sender:id,name,department_id',
            'sender.department:id,name',
            'recipients:id,name,department_id',
            'recipients.department:id,name',
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
        ];
        if (Document::hasExecutorPivotTable()) {
            $with[] = 'executors:id,name,department_id';
            $with[] = 'executors.department:id,name';
        }
        $document->load($with);

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

        $registryDepartments = \App\Http\Controllers\RegistryController::accessibleDepartments($user);
        $registryEntries = \App\Models\RegistryEntry::where('document_id', $document->id)
            ->with('department:id,name')
            ->get();

        return view('documents.show', compact('document', 'users', 'groups', 'relatedDocuments', 'registryDepartments', 'registryEntries'));
    }

    public function updateStatus(Request $request, Document $document)
    {
        $user = Auth::user();
        abort_unless($user->canViewDocument($document), 403);

        $validated = $request->validate([
            'status' => 'required|string',
            'comment' => 'required|string|min:3',
        ]);

        $allowed = Document::$transitions[$document->status] ?? [];
        if (!in_array($validated['status'], $allowed, true)) {
            return back()->with('error', 'Недопустимый переход статуса.');
        }

        if (!$user->canChangeDocumentStatus($document, $validated['status'])) {
            if ($validated['status'] === 'approved' && !$document->allExecutorsCompleted()) {
                return back()->with('error', 'Нельзя выполнить документ, пока все исполнители не отметили свою часть.');
            }

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

    public function completeExecutor(Request $request, Document $document)
    {
        if (!Document::hasExecutorPivotTable()) {
            return back()->with('error', 'Функция соисполнителей пока недоступна: не применены миграции базы данных.');
        }

        $user = Auth::user();
        abort_unless($user->isDocumentExecutor($document), 403);

        if ($document->status !== 'review') {
            return back()->with('error', 'Отметить выполнение можно только когда документ находится в работе.');
        }

        $validated = $request->validate([
            'completion_comment' => 'nullable|string|max:1000',
        ]);

        $document->executors()->updateExistingPivot($user->id, [
            'completed_at' => now(),
            'completion_comment' => $validated['completion_comment'] ?? null,
            'updated_at' => now(),
        ]);

        $this->notifyDocumentParticipants(
            $document->fresh(['executors', 'sender', 'recipients', 'executor', 'createdBy']),
            $user,
            'Исполнитель выполнил свою часть',
            $document->number . ': ' . $user->name,
        );

        return back()->with('success', 'Ваша часть выполнения отмечена.');
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
        Storage::disk('public')->makeDirectory('uploads');
        $path = $file->storeAs('uploads', $storedName, 'public');

        if (!$path) {
            throw ValidationException::withMessages([
                'files' => 'Не удалось сохранить файл. Проверьте права на storage/app/public/uploads.',
            ]);
        }

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
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => 'integer|exists:users,id',
            'recipient_group_id' => 'nullable|exists:groups,id',
            'sender_org' => 'nullable|string|max:255',
            'recipient_orgs' => 'nullable|array',
            'recipient_orgs.*' => 'nullable|string|max:255',
            'executor_ids' => 'nullable|array',
            'executor_ids.*' => 'integer|exists:users,id',
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

        $relations = ['sender', 'recipients', 'executor', 'createdBy'];
        if (Document::hasExecutorPivotTable()) {
            $relations[] = 'executors';
        }
        $document->loadMissing($relations);

        $recipients = collect([$document->sender, $document->executor, $document->createdBy])
            ->merge(Document::hasExecutorPivotTable() ? $document->executors : collect())
            ->merge($document->recipients)
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

    private function syncExecutors(Document $document, array $executorIds): void
    {
        if (!Document::hasExecutorPivotTable()) {
            return;
        }

        $executorIds = collect($executorIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $existing = $document->executors()
            ->get()
            ->keyBy('id');

        $sync = $executorIds->mapWithKeys(function (int $executorId) use ($existing) {
            $pivot = $existing->get($executorId)?->pivot;

            return [
                $executorId => [
                    'completed_at' => $pivot?->completed_at,
                    'completion_comment' => $pivot?->completion_comment,
                ],
            ];
        })->all();

        $document->executors()->sync($sync);
    }
}
