<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_users_ordered_by_name(): void
    {
        User::factory()->create(['name' => 'Zoe Vera', 'email' => 'zoe@example.test']);
        User::factory()->create(['name' => 'Ana Gómez', 'email' => 'ana@example.test']);

        $response = $this->getJson('/api/users')->assertOk();

        $names = array_column($response->json('data'), 'name');

        $this->assertSame(['Ana Gómez', 'Zoe Vera'], $names);
    }
}
