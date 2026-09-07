<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('tasks:summary', function () {
    $total = \App\Models\Task::count();
    $completed = \App\Models\Task::where('status', 'completed')->count();

    $this->info("Tareas cargadas: {$total} ({$completed} finalizadas)");
})->purpose('Muestra un resumen de las tareas cargadas');
