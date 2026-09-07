<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return response()->json([
            'data' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->values(),
        ]);
    }
}
