<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_a_task_to_in_progress_without_writing_history(): void
    {
        $task = Task::factory()->create(['status' => 'pending', 'completed_at' => null]);

        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);
        $this->assertDatabaseCount('task_histories', 0);
    }

    public function test_completing_a_task_records_a_history_entry(): void
    {
        $task = Task::factory()->create(['status' => 'in_progress', 'completed_at' => null]);

        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $task->refresh();

        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->id,
            'from_status' => 'in_progress',
            'to_status' => 'completed',
        ]);
        $this->assertDatabaseCount('task_histories', 1);
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $this->patchJson("/api/tasks/{$task->id}/status", ['status' => 'archived'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
