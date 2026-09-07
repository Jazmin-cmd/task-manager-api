<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_tasks_with_the_assigned_user(): void
    {
        $user = User::factory()->create(['name' => 'Ana Gómez']);

        Task::factory()->create([
            'title' => 'Preparar el informe mensual de facturación',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => '2030-01-15',
            'assigned_user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/tasks')->assertOk();

        $task = $response->json('data.0');

        $this->assertSame('Preparar el informe mensual de facturación', $task['title']);
        $this->assertSame('pending', $task['status']);
        $this->assertSame('high', $task['priority']);
        $this->assertSame('2030-01-15', $task['due_date']);
        $this->assertSame('Ana Gómez', $task['assigned_user']['name']);
    }

    public function test_it_returns_tasks_without_an_assigned_user(): void
    {
        Task::factory()->create(['assigned_user_id' => null]);

        $response = $this->getJson('/api/tasks')->assertOk();

        $this->assertNull($response->json('data.0.assigned_user'));
    }

    public function test_it_filters_tasks_by_status(): void
    {
        Task::factory()->create(['title' => 'Pendiente una', 'status' => 'pending']);
        Task::factory()->create(['title' => 'Finalizada una', 'status' => 'completed']);

        $titles = array_column($this->getJson('/api/tasks?status=completed')->assertOk()->json('data'), 'title');

        $this->assertSame(['Finalizada una'], $titles);
    }

    public function test_it_filters_tasks_by_priority(): void
    {
        Task::factory()->create(['title' => 'Prioridad baja', 'priority' => 'low']);
        Task::factory()->create(['title' => 'Prioridad alta', 'priority' => 'high']);

        $titles = array_column($this->getJson('/api/tasks?priority=high')->assertOk()->json('data'), 'title');

        $this->assertSame(['Prioridad alta'], $titles);
    }

    public function test_it_searches_tasks_by_title(): void
    {
        Task::factory()->create(['title' => 'Corregir el bucle de redirección en el login']);
        Task::factory()->create(['title' => 'Redactar la documentación de bienvenida']);

        $titles = array_column($this->getJson('/api/tasks?search=login')->assertOk()->json('data'), 'title');

        $this->assertSame(['Corregir el bucle de redirección en el login'], $titles);
    }
}
