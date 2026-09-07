<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'task-manager-api',
        'docs' => 'Ver README.md',
        'api' => url('/api/tasks'),
    ]);
});
