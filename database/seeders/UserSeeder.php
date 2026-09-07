<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Ana Gómez', 'email' => 'ana.gomez@example.test'],
            ['name' => 'Bruno Díaz', 'email' => 'bruno.diaz@example.test'],
            ['name' => 'Carla Ruiz', 'email' => 'carla.ruiz@example.test'],
            ['name' => 'Diego Salas', 'email' => 'diego.salas@example.test'],
            ['name' => 'Elena Ortiz', 'email' => 'elena.ortiz@example.test'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
