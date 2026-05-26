# SEMS Laravel Backend

`backend/` is now the Laravel API foundation for SEMS. The previous vanilla PHP backend has been preserved at `../backend-legacy/` for reference.

## Database

Laravel is configured for the existing SEMS MySQL database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sems
DB_USERNAME=sems
DB_PASSWORD=sems
```

Do not run destructive migrations against this database. The default Laravel migrations were removed so Eloquent models can be mapped to the existing schema later.

<<<<<<< Updated upstream
=======
## Demo Data

The default database seeder loads deterministic student portal demo data:

```bash
cd backend
php artisan db:seed
```

Demo student logins:

- `student1@example.com` / `password` or `STU-1001` / `password`
- `student2@example.com` / `password` or `STU-1002` / `password`

The demo dataset includes one faculty, one department, one bachelor program, the `2025/2026` academic year, 3rd and 4th semester courses, enrollments, schedules, grades, transcript support rows, attendance support rows, and student dashboard rows. The seeder uses stable keys and updates existing demo rows when run again.

>>>>>>> Stashed changes
## Install

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

The local `.env` in this checkout is already set to the SEMS database and local session/cookie defaults.

## Run

```bash
cd backend
composer serve
```

This runs Laravel at `http://127.0.0.1:8080`, matching the current Vite proxy target for `/api` and `/uploads`.

## Current API Foundation

Placeholder routes return the existing frontend envelope shape:

```json
{
  "success": true,
  "data": {},
  "message": null,
  "errors": null,
  "meta": {}
}
```

Registered routes:

- `GET /api/health`
- `POST /api/auth/login`
- `GET /api/auth/session`
- `POST /api/auth/logout`
- `GET /api/student/dashboard`
- `GET /api/student/courses`
- `GET /api/student/courses/{courseId}`
- `GET /api/student/attendance`
- `GET /api/student/grades-transcript`
- `GET /api/student/profile`
- `PATCH /api/student/profile`
- `POST /api/student/profile/avatar`

Uploads are reserved under `public/uploads`, so `/uploads/...` can continue to be served from the same backend origin when avatar storage is implemented.
