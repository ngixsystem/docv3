<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

        Task::create($validated);

        Cache::forget('all_docs_simple');

        return back()->with('success', 'Задача создана.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        abort_unless(Auth::user()->canChangeTask($task), 403);

        $request->validate(['status' => 'required|in:new,in_progress,paused,done']);
        $task->update(['status' => $request->status]);

        return response()->json(['ok' => true]);
    }
}
