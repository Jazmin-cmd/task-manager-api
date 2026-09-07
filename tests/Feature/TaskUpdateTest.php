<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_an_existing_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'title' => 'Título anterior',
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Título nuevo',
            'description' => 'Descripción actualizada',
            'priority' => 'high',
            'due_date' => '2030-06-30',
            'assigned_user_id' => $user->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título nuevo')
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Título nuevo',
            'priority' => 'high',
            'assigned_user_id' => $user->id,
        ]);
    }
}
