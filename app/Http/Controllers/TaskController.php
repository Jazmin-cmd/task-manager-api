<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with('assignedUser');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $tasks = $query->orderBy('created_at')->orderBy('id')->get();

        $data = $tasks->map(fn (Task $task) => $this->formatTask($task))->values();

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
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:255', 'regex:/[\p{L}\p{N}]/u'],
            'description' => 'nullable|string|min:5|max:2000',
            'status' => 'nullable|in:pending,in_progress,completed',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:' . now()->startOfYear()->format('Y-m-d'),
                'before_or_equal:' . now()->addYear()->format('Y-m-d'),
            ],
            'assigned_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $task = Task::create($validated);

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
            ], 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:255', 'regex:/[\p{L}\p{N}]/u'],
            'description' => 'nullable|string|min:5|max:2000',
            'status' => 'nullable|in:pending,in_progress,completed',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:' . now()->startOfYear()->format('Y-m-d'),
                'before_or_equal:' . now()->addYear()->format('Y-m-d'),
            ],
            'assigned_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $previousStatus = $task->status;

        $task->fill($validated);
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
            return response()->json(['message' => 'Tarea no encontrada'], 404);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $previousStatus = $task->status;
        $newStatus = $request->input('status');

        $task->status = $newStatus;
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
        DB::transaction(function () use ($task, $previousStatus) {
            $task->completed_at = now();
            $task->save();

            TaskHistory::create([
                'task_id' => $task->id,
                'from_status' => $previousStatus,
                'to_status' => $task->status,
                'note' => 'Tarea marcada como finalizada',
            ]);
        });
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
    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En progreso',
            'completed' => 'Finalizada',
            default => $status,
        };
    }
}

