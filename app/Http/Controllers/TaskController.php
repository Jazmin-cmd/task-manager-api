<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tasks = $query->get();

        if ($request->filled('priority')) {
            $priority = $request->input('priority');
            $tasks = $tasks->filter(function (Task $task) use ($priority) {
                return $task->priority === $priority;
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $tasks = $tasks->filter(function (Task $task) use ($search) {
                return str_contains(strtolower($task->title), $search);
            });
        }

        $data = [];

        foreach ($tasks as $task) {
            $data[] = $this->formatTask($task);
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    public function show(string $id)
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json([
                'message' => 'Tarea no encontrada',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatTask($task, true),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'assigned_user_id' => 'nullable|integer',
        ]);

        $task = Task::create($request->all());

        return response()->json([
            'data' => $this->formatTask($task),
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json([
                'message' => 'Tarea no encontrada',
            ]);
        }

        $request->validate([
            'title' => 'required',
        ]);

        $previousStatus = $task->status;

        $task->fill($request->all());
        $task->save();

        if ($task->status === 'completed' && $previousStatus !== 'completed') {
            $this->recordCompletion($task, $previousStatus);
        }

        return response()->json([
            'data' => $this->formatTask($task),
        ]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $task = Task::find($id);

        if (! $task) {
            return response()->json([
                'message' => 'Tarea no encontrada',
            ]);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $previousStatus = $task->status;

        $task->status = $request->input('status');
        $task->save();

        if ($task->status === 'completed' && $previousStatus !== 'completed') {
            $this->recordCompletion($task, $previousStatus);
        }

        return response()->json([
            'data' => $this->formatTask($task),
        ]);
    }

    private function recordCompletion(Task $task, ?string $previousStatus): void
    {
        $task->completed_at = now();
        $task->save();

        TaskHistory::create([
            'task_id' => $task->id,
            'from_status' => $previousStatus,
            'to_status' => $task->status,
            'note' => 'Tarea marcada como finalizada',
        ]);
    }

    private function formatTask(Task $task, bool $withHistory = false): array
    {
        $assignedUser = $task->assignedUser;

        $payload = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'assigned_user_id' => $task->assigned_user_id,
            'assigned_user' => $assignedUser ? [
                'id' => $assignedUser->id,
                'name' => $assignedUser->name,
            ] : null,
            'completed_at' => $task->completed_at?->toIso8601String(),
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];

        if ($withHistory) {
            $payload['histories'] = $task->histories()
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map(fn (TaskHistory $history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toIso8601String(),
                ])
                ->values();
        }

        return $payload;
    }
}
