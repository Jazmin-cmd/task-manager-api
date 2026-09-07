<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    /**
     * Catálogo fijo para que todas las personas candidatas y quien entrevista
     * vean exactamente el mismo conjunto de datos.
     */
    private array $titles = [
        'Corregir el bucle de redirección en el login',
        'Redactar la documentación de bienvenida',
        'Revisar el formato de exportación de facturas',
        'Migrar el generador de informes antiguo',
        'Agregar paginación al listado de clientes',
        'Investigar las consultas lentas del panel',
        'Actualizar las versiones de las dependencias',
        'Diseñar el estado vacío del listado de tareas',
        'Preparar el informe mensual de facturación',
        'Refactorizar el servicio de notificaciones',
        'Corregir la carga de fotos de perfil',
        'Agregar filtros a la pantalla de facturas',
        'Eliminar índices sin uso en la base de datos',
        'Mejorar los mensajes de error del registro',
        'Programar la copia de seguridad de la base',
        'Auditar el uso de las APIs de terceros',
        'Reescribir el correo de recuperación de contraseña',
        'Reducir el tamaño del paquete del cliente web',
        'Documentar la lista de pasos del despliegue',
        'Corregir el error de zona horaria en la exportación',
        'Agregar indicador de carga al buscador',
        'Reemplazar el símbolo de moneda fijo en el código',
        'Revisar la accesibilidad del formulario principal',
        'Archivar las cuentas de clientes inactivas',
        'Dividir la pantalla de ajustes en pestañas',
        'Agregar reintentos al webhook de pagos',
        'Traducir el panel al portugués',
        'Eliminar el endpoint de informes obsoleto',
        'Registrar los intentos fallidos de inicio de sesión',
        'Crear datos de prueba para el entorno de staging',
        'Corregir filas duplicadas en el listado de facturas',
        'Agregar la columna de fecha límite a la exportación',
        'Revisar la configuración del monitoreo de errores',
        'Simplificar el asistente de bienvenida',
        'Unificar registros duplicados de clientes',
        'Agregar atajos de teclado al tablero de tareas',
        'Recuperar los adjuntos faltantes de las facturas',
        'Medir el rendimiento del endpoint de búsqueda',
        'Rotar las credenciales de API vencidas',
        'Planificar la revisión técnica trimestral',
        'Corregir la paginación en la pantalla de informes',
        'Recopilar comentarios sobre el nuevo panel',
    ];

    private array $statuses = ['pending', 'in_progress', 'completed'];

    private array $priorities = ['low', 'medium', 'high'];

    public function run(): void
    {
        $users = User::orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::orderBy('id')->get();
        }

        $today = Carbon::today();

        foreach ($this->titles as $index => $title) {
            $status = $this->statuses[$index % 3];
            $priority = $this->priorities[intdiv($index, 2) % 3];

            // Reparte las fechas límite entre dos semanas atrás y seis semanas adelante.
            $dueDate = $today->copy()->addDays(($index * 5) % 56 - 14);

            // Algunas tareas quedan sin fecha límite y sin persona asignada.
            $hasDueDate = $index % 7 !== 3;
            $assignee = $index % 11 === 5 ? null : $users[$index % $users->count()];

            $task = Task::create([
                'title' => $title,
                'description' => $this->descriptionFor($title, $index),
                'status' => $status,
                'priority' => $priority,
                'due_date' => $hasDueDate ? $dueDate->format('Y-m-d') : null,
                'assigned_user_id' => $assignee?->id,
                'completed_at' => $status === 'completed'
                    ? $today->copy()->subDays($index % 9)->setTime(10, 30)
                    : null,
            ]);

            if ($status === 'completed') {
                TaskHistory::create([
                    'task_id' => $task->id,
                    'from_status' => 'in_progress',
                    'to_status' => 'completed',
                    'note' => 'Tarea marcada como finalizada',
                    'created_at' => $task->completed_at,
                    'updated_at' => $task->completed_at,
                ]);
            }
        }
    }

    private function descriptionFor(string $title, int $index): ?string
    {
        if ($index % 8 === 6) {
            return null;
        }

        return $title.'. Pedido por el equipo de soporte durante la última revisión de sprint. '
            .'Mirar las notas relacionadas antes de empezar y actualizar el ticket cuando esté listo.';
    }
}
