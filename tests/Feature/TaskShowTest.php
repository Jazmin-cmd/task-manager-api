<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_single_task_with_its_history(): void
    {
        $task = Task::factory()->create(['title' => 'Auditar el uso de las APIs de terceros']);

        TaskHistory::create([
            'task_id' => $task->id,
            'from_status' => 'in_progress',
            'to_status' => 'completed',
            'note' => 'Task marked as completed',
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}")->assertOk();

        $this->assertSame('Auditar el uso de las APIs de terceros', $response->json('data.title'));
        $this->assertCount(1, $response->json('data.histories'));
        $this->assertSame('completed', $response->json('data.histories.0.to_status'));
    }

    public function test_it_returns_404_for_an_unknown_task(): void
    {
        $this->getJson('/api/tasks/999999')->assertNotFound();
    }
}
