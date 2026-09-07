# task-manager-api

API REST de un sistema chico de gestión de tareas, hecha con Laravel 12 y PostgreSQL.
La consume el cliente React `task-manager-web`.

Todo corre dentro de Docker: **no** necesitás tener instalados PHP, Composer ni
PostgreSQL en tu máquina.

---

## Requisitos

- Docker Desktop (o Docker Engine) con el plugin `docker compose`.
- Alrededor de 1 GB libre en disco para las imágenes.
- Los puertos TCP `8000` (API) y `55432` (PostgreSQL) libres. Los dos se pueden cambiar,
  ver más abajo.

Para verificar la instalación:

```bash
docker --version
docker compose version
```

---

## Configuración

Copiá el archivo de entorno de ejemplo:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Variables relevantes:

| Variable            | Valor por defecto | Descripción                                                   |
| ------------------- | ----------------- | -------------------------------------------------------------- |
| `APP_URL`           | `http://localhost:8000` | URL pública de la API.                                   |
| `APP_LOCALE`        | `es`              | Idioma de los mensajes de la aplicación.                        |
| `DB_CONNECTION`     | `pgsql`           | Driver de base de datos.                                        |
| `DB_HOST`           | `db`              | **Dejalo en `db`**: es el nombre del servicio de docker compose. |
| `DB_PORT`           | `5432`            | Puerto *dentro* de la red de Docker.                            |
| `DB_DATABASE`       | `task_manager`    | Nombre de la base (también crea el contenedor de PostgreSQL).   |
| `DB_USERNAME`       | `task_manager`    | Usuario de la base.                                             |
| `DB_PASSWORD`       | `secret`          | Contraseña local de desarrollo. No es un secreto real.          |
| `APP_EXTERNAL_PORT` | `8000`            | Puerto que se publica en tu máquina para la API.                |
| `DB_EXTERNAL_PORT`  | `55432`           | Puerto que se publica en tu máquina para PostgreSQL.            |

`APP_KEY` se genera sola la primera vez que arranca el contenedor.

`docker compose` lee este mismo `.env`, así que cambiar `DB_USERNAME` / `DB_PASSWORD` /
`DB_DATABASE` también configura el contenedor de PostgreSQL.

---

## Cómo levantar el proyecto

```bash
docker compose up -d --build
```

El primer arranque tarda un par de minutos: construye la imagen de PHP e instala las
dependencias de Composer dentro del contenedor.

Después creá el esquema y cargá los datos de ejemplo:

```bash
docker compose exec app php artisan migrate --seed
```

Para comprobar que responde:

```bash
curl http://localhost:8000/api/tasks
```

Comandos útiles:

```bash
docker compose logs -f app     # logs de la aplicación
docker compose ps              # estado de los contenedores
docker compose down            # apagar todo (conserva los datos)
docker compose down -v         # apagar todo y borrar el volumen de la base
```

Para volver la base a un estado limpio:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

---

## URLs

- API: <http://localhost:8000>
- Listado de tareas: <http://localhost:8000/api/tasks>
- Health check: <http://localhost:8000/up>
- Frontend (proyecto aparte): <http://localhost:5173>

---

## Datos de ejemplo

`php artisan db:seed` carga un conjunto fijo de datos, siempre el mismo:

- 5 usuarios.
- 42 tareas repartidas entre todos los estados y prioridades, con fechas límite pasadas
  y futuras, algunas sin fecha y algunas sin persona asignada.
- Registros de historial para las tareas que ya están finalizadas.

---

## API

Todas las respuestas son JSON y envuelven el contenido en una clave `data`.

| Método  | Ruta                      | Descripción                                             |
| ------- | ------------------------- | -------------------------------------------------------- |
| `GET`   | `/api/users`              | Usuarios disponibles para asignar.                        |
| `GET`   | `/api/tasks`              | Listado de tareas. Acepta `status`, `priority` y `search`. |
| `GET`   | `/api/tasks/{id}`         | Una tarea con su historial.                               |
| `POST`  | `/api/tasks`              | Crea una tarea.                                           |
| `PUT`   | `/api/tasks/{id}`         | Actualiza una tarea.                                      |
| `PATCH` | `/api/tasks/{id}/status`  | Cambia el estado de una tarea.                            |

Campos de una tarea (los nombres de columnas están en inglés, los contenidos en
español):

| Campo              | Tipo                                          |
| ------------------ | --------------------------------------------- |
| `id`               | entero                                        |
| `title`            | texto                                         |
| `description`      | texto, puede ser nulo                         |
| `status`           | `pending` \| `in_progress` \| `completed`     |
| `priority`         | `low` \| `medium` \| `high`                   |
| `due_date`         | `AAAA-MM-DD`, puede ser nulo                  |
| `assigned_user_id` | entero, puede ser nulo                        |
| `completed_at`     | fecha y hora ISO 8601, puede ser nulo         |
| `created_at`       | fecha y hora ISO 8601                         |
| `updated_at`       | fecha y hora ISO 8601                         |

Ejemplo:

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"Revisar el informe trimestral","priority":"high","due_date":"2030-01-15"}'
```

Los errores de validación se devuelven con HTTP `422` en el formato estándar de Laravel:

```json
{
  "message": "El campo título es obligatorio.",
  "errors": { "title": ["El campo título es obligatorio."] }
}
```

---

## Tests

La suite corre contra una base SQLite en memoria, así que no necesita servicios extra:

```bash
docker compose exec app php artisan test
```

También se puede correr un archivo puntual:

```bash
docker compose exec app php artisan test tests/Feature/TaskIndexTest.php
```

La `APP_KEY` declarada en `phpunit.xml` es un valor fijo de descarte que solo usa la
suite de tests.

---

## Estructura del proyecto

```
task-manager-api/
├── app/
│   ├── Http/Controllers/     # TaskController, UserController
│   └── Models/               # Task, TaskHistory, User
├── bootstrap/app.php         # configuración de rutas y middleware
├── database/
│   ├── factories/            # factories que usan los tests
│   ├── migrations/           # users, tasks, task_histories
│   └── seeders/              # UserSeeder, TaskSeeder
├── docker/php/               # imagen y entrypoint del contenedor de la API
├── lang/es/                  # mensajes de validación en español
├── routes/
│   ├── api.php               # endpoints REST (con prefijo /api)
│   └── web.php               # ruta raíz informativa
├── tests/Feature/            # tests de la API
├── docker-compose.yml        # servicios app + db
└── phpunit.xml
```

Tablas de la base de datos:

- `users`: `id`, `name`, `email`, timestamps.
- `tasks`: los campos de la tarea más `assigned_user_id` (FK a `users`) y `completed_at`.
- `task_histories`: `id`, `task_id` (FK a `tasks`), `from_status`, `to_status`, `note`,
  timestamps.

---

## Problemas comunes de configuración

**`port is already allocated` al levantar los contenedores.**
Hay algo más usando el puerto 8000 o el 55432. Cambiá `APP_EXTERNAL_PORT` o
`DB_EXTERNAL_PORT` en el `.env` y volvé a correr `docker compose up -d`.

**`SQLSTATE[08006] ... could not translate host name "db"`.**
`DB_HOST` tiene que ser `db` cuando la API corre dentro de Docker. Sería `localhost`
solo si corrieras PHP directo en tu máquina, cosa que este proyecto no necesita.

**`could not find driver`.**
Estás corriendo artisan en tu máquina en vez de dentro del contenedor. Poné siempre el
prefijo `docker compose exec app`.

**La API responde pero las tablas no existen.**
Faltó correr las migraciones: `docker compose exec app php artisan migrate --seed`.

**Cambiaste el `.env` y no pasó nada.**
El contenedor lee el archivo al arrancar. Reinicialo: `docker compose restart app`.

**La base quedó rara después de experimentar.**
`docker compose down -v && docker compose up -d --build` y volvé a correr las
migraciones.

**El primer arranque tarda mucho.**
El primer `docker compose up -d --build` construye la imagen de PHP y después instala
las dependencias de Composer dentro del contenedor. En Windows y macOS, donde la
carpeta del proyecto es un bind mount, esto tarda entre 2 y 4 minutos. La API recién
responde cuando termina; podés seguir el avance con `docker compose logs -f app`. Los
arranques siguientes reutilizan `vendor/` y tardan segundos.
