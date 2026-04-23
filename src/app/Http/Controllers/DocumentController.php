<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentStatusHistory;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)
                ->select(['id', 'name', 'department_id'])
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
        );
        $groups = Group::withCount('users')->orderBy('name')->get(['id', 'name']);

        return view('documents.create', compact('users', 'groups', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
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
        ]);

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

        return redirect()->route('documents.show', $doc)
            ->with('success', 'Документ создан: ' . $doc->number);
    }

    public function show(Document $document)
    {
        abort_unless(Auth::user()->canViewDocument($document), 403);

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
        ]);

        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)
                ->select(['id', 'name', 'department_id'])
                ->with('department:id,name')
                ->orderBy('name')
                ->get()
        );
        $groups = Group::withCount('users')->orderBy('name')->get(['id', 'name']);

        return view('documents.show', compact('document', 'users', 'groups'));
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

        if ($validated['status'] === 'registered' && !$user->canRegisterDocuments()) {
            abort(403);
        }

        if (in_array($validated['status'], ['approved', 'rejected'], true) && !$user->canApproveDocuments()) {
            abort(403);
        }

        if (in_array($validated['status'], ['review', 'archive'], true) && !$user->hasAnyRole(['admin', 'manager', 'clerk'])) {
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

        return back()->with('success', 'Статус изменен на: ' . Document::$statusNames[$validated['status']]);
    }

    public function addComment(Request $request, Document $document)
    {
        abort_unless(Auth::user()->canViewDocument($document), 403);

        $request->validate(['body' => 'required|string|max:2000']);
        $document->comments()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

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
}
