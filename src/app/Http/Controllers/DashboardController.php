<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $documents = Document::query()->visibleTo($user);
        $tasks = Task::query()->visibleTo($user);

        $stats = [
            'total' => (clone $documents)->count(),
            'review' => (clone $documents)->where('status', 'review')->count(),
            'tasks' => (clone $tasks)->where('status', '!=', 'done')->count(),
            'overdue' => (clone $tasks)
                ->where('status', '!=', 'done')
                ->whereDate('deadline', '<', now()->toDateString())
                ->count(),
        ];

        $recentDocs = Document::query()
            ->visibleTo($user)
            ->with([
                'sender:id,name',
                'recipient:id,name',
                'createdBy:id,name',
            ])
            ->select([
                'id', 'number', 'type', 'subject', 'status', 'doc_date',
                'sender_id', 'recipient_id', 'created_by', 'sender_org', 'recipient_org',
            ])
            ->latest()
            ->limit(8)
            ->get();

        $urgentTasks = Task::query()
            ->visibleTo($user)
            ->with([
                'assignee:id,name',
                'document:id,number',
            ])
            ->select(['id', 'title', 'status', 'priority', 'deadline', 'assignee_id', 'document_id'])
            ->whereNotIn('status', ['done'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->limit(6)
            ->get();

        $documentTypeStats = collect(Document::$typeNames)
            ->map(fn ($label, $type) => [
                'key' => $type,
                'label' => $label,
                'count' => (clone $documents)->where('type', $type)->count(),
            ])
            ->values();

        $documentStatusStats = collect(Document::$statusNames)
            ->map(fn ($label, $status) => [
                'key' => $status,
                'label' => $label,
                'count' => (clone $documents)->where('status', $status)->count(),
            ])
            ->values();

        $taskStatusStats = collect(Task::$statusNames)
            ->map(fn ($label, $status) => [
                'key' => $status,
                'label' => $label,
                'count' => (clone $tasks)->where('status', $status)->count(),
            ])
            ->values();

        $activityDays = collect(range(29, 0))
            ->map(function (int $offset) use ($documents, $tasks) {
                $date = now()->subDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'label' => now()->subDays($offset)->translatedFormat('d M'),
                    'documents' => (clone $documents)->whereDate('created_at', $date)->count(),
                    'tasks' => (clone $tasks)->whereDate('created_at', $date)->count(),
                ];
            })
            ->values();

        return view('dashboard.index', compact(
            'stats',
            'recentDocs',
            'urgentTasks',
            'documentTypeStats',
            'documentStatusStats',
            'taskStatusStats',
            'activityDays',
        ));
    }
}
