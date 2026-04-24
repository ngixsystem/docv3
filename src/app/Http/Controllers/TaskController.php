<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Task;
use App\Models\TaskFile;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $tasks = Task::query()
            ->visibleTo($user)
            ->with([
                'assignee:id,name',
                'creator:id,name',
                'document:id,number,subject',
            ])
            ->select([
                'id', 'title', 'description', 'status', 'priority', 'deadline',
                'assignee_id', 'created_by', 'document_id', 'created_at',
            ])
            ->get();

        $columns = [
            'new' => $tasks->where('status', 'new')->values(),
            'in_progress' => $tasks->where('status', 'in_progress')->values(),
            'paused' => $tasks->where('status', 'paused')->values(),
            'done' => $tasks->where('status', 'done')->values(),
        ];

        $users = Cache::remember('all_users_simple', 300, fn () =>
            User::where('is_active', true)->select(['id', 'name'])->orderBy('name')->get()
        );
        $documents = Document::query()
            ->visibleTo($user)
            ->select(['id', 'number', 'subject'])
            ->orderBy('number')
            ->get();

        return view('tasks.index', compact('columns', 'users', 'documents', 'user'));
    }

    public function show(Task $task)
    {
        $user = Auth::user();
        abort_unless($user->canViewTask($task), 403);

        $task->load([
            'assignee:id,name,department_id',
            'assignee.department:id,name',
            'creator:id,name,department_id',
            'creator.department:id,name',
            'document:id,number,subject,status,deadline,doc_date',
            'document.files:id,document_id,original_name,stored_name,mime_type,file_size,path,uploaded_by',
            'comments.user:id,name',
            'taskFiles.uploader:id,name',
            'statusHistory.user:id,name',
        ]);

        return view('tasks.show', compact('task'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->canCreateTasks(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'required|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'nullable|date',
            'document_id' => 'nullable|exists:documents,id',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'new';

        if (!empty($validated['document_id']) && !Document::query()->visibleTo(Auth::user())->whereKey($validated['document_id'])->exists()) {
            abort(403);
        }

        $task = Task::create($validated);

        TaskStatusHistory::create([
            'task_id'     => $task->id,
            'user_id'     => Auth::id(),
            'from_status' => null,
            'to_status'   => 'new',
        ]);

        if ($task->assignee_id !== Auth::id()) {
            $task->assignee->notify(new AppNotification(
                'Вам назначена задача',
                $task->title,
                route('tasks.show', $task),
            ));
        }

        Cache::forget('all_docs_simple');

        return back()->with('success', 'Задача создана.');
    }

    public function addComment(Request $request, Task $task)
    {
        $user = Auth::user();
        abort_unless($user->canViewTask($task), 403);
        abort_unless($user->canChangeTask($task) && $task->status === 'in_progress', 403);

        $request->validate(['body' => 'required|string|max:2000']);

        $task->comments()->create([
            'user_id' => $user->id,
            'body' => $request->body,
        ]);

        $task->loadMissing(['creator', 'assignee']);
        collect([$task->creator, $task->assignee])
            ->filter()
            ->unique('id')
            ->reject(fn ($u) => $u->id === $user->id)
            ->each(fn ($u) => $u->notify(new AppNotification(
                'Новый комментарий к задаче',
                $task->title . ': ' . mb_strimwidth($request->body, 0, 80, '…'),
                route('tasks.show', $task),
            )));

        return back()->with('success', 'Комментарий добавлен.');
    }

    public function uploadFile(Request $request, Task $task)
    {
        $user = Auth::user();
        abort_unless($user->canViewTask($task), 403);
        abort_unless($user->canChangeTask($task) && $task->status === 'in_progress', 403);

        $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,webp,svg,txt,csv,json,xml,log,md,mp3,wav,ogg,m4a,mp4,webm',
        ]);

        $file = $request->file('file');
        $storedName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('task-uploads', $storedName, 'public');

        $task->taskFiles()->create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'path' => $path,
            'uploaded_by' => $user->id,
        ]);

        return back()->with('success', 'Файл загружен: ' . $file->getClientOriginalName());
    }

    public function downloadFile(TaskFile $taskFile)
    {
        $user = Auth::user();
        abort_unless($user->canViewTask($taskFile->task), 403);

        if (!Storage::disk('public')->exists($taskFile->path)) {
            abort(404, 'Файл не найден.');
        }

        return Storage::disk('public')->download($taskFile->path, $taskFile->original_name);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $user = Auth::user();
        abort_unless($user->canChangeTask($task), 403);

        $request->validate(['status' => 'required|in:new,in_progress,paused,done']);

        $fromStatus = $task->status;
        $task->update(['status' => $request->status]);

        TaskStatusHistory::create([
            'task_id'     => $task->id,
            'user_id'     => $user->id,
            'from_status' => $fromStatus,
            'to_status'   => $request->status,
        ]);

        $task->loadMissing(['creator', 'assignee']);
        collect([$task->creator, $task->assignee])
            ->filter()
            ->unique('id')
            ->reject(fn ($u) => $u->id === $user->id)
            ->each(fn ($u) => $u->notify(new AppNotification(
                'Статус задачи изменён',
                $task->title . ': ' . (Task::$statusNames[$request->status] ?? $request->status),
                route('tasks.show', $task),
            )));

        return response()->json(['ok' => true]);
    }
}
