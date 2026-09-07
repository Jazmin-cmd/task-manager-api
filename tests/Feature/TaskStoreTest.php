<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_task(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Rotar las credenciales de API vencidas',
            'description' => 'Coordinar con el equipo de plataforma.',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => '2030-03-01',
            'assigned_user_id' => $user->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Rotar las credenciales de API vencidas')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_date', '2030-03-01');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Rotar las credenciales de API vencidas',
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_it_rejects_a_task_without_a_title(): void
    {
        $this->postJson('/api/tasks', ['description' => 'Sin título'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }
}
