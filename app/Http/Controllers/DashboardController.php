<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Stats
        $listsCount = TaskList::where('user_id', $user->id)->count();
        $tasksQuery = Task::whereHas('list', fn($q) => $q->where('user_id', $user->id));
        $stats = [
            'totalLists' => $listsCount,
            'totalTasks' => $tasksQuery->count(),
            'completedTasks' => (clone $tasksQuery)->where('is_completed', true)->count(),
            'pendingTasks' => (clone $tasksQuery)->where('is_completed', false)->count(),
        ];

        /* =========================
           Recent Activity (Only last event)
        ========================= */

        $latestTask = $tasksQuery->latest()->first();
        $latestList = TaskList::where('user_id', $user->id)->latest()->first();

        $latestActivity = null;

        if ($latestTask && $latestList) {
            // Compare creation time, pick the most recent
            $latestActivity = $latestTask->created_at > $latestList->created_at
                ? [
                    'id' => 'task-' . $latestTask->id,
                    'title' => $latestTask->is_completed ? 'Task completed' : 'Task created',
                    'description' => $latestTask->title,
                    'type' => 'task',
                    'created_at' => $latestTask->created_at,
                ]
                : [
                    'id' => 'list-' . $latestList->id,
                    'title' => 'List created',
                    'description' => $latestList->name,
                    'type' => 'list',
                    'created_at' => $latestList->created_at,
                ];
        } elseif ($latestTask) {
            $latestActivity = [
                'id' => 'task-' . $latestTask->id,
                'title' => $latestTask->is_completed ? 'Task completed' : 'Task created',
                'description' => $latestTask->title,
                'type' => 'task',
                'created_at' => $latestTask->created_at,
            ];
        } elseif ($latestList) {
            $latestActivity = [
                'id' => 'list-' . $latestList->id,
                'title' => 'List created',
                'description' => $latestList->name,
                'type' => 'list',
                'created_at' => $latestList->created_at,
            ];
        }

        $activities = $latestActivity ? [$latestActivity] : [];

        // Render dashboard
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'activities' => $activities,
        ]);
    }
}
