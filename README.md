# SEMS - Student Education Management System

SEMS is a demo Student Education Management System with a React/Vite frontend and a Laravel API backend foundation. The previous vanilla PHP backend is preserved in `backend-legacy/`.

## Demo Video

Project demo: https://streamable.com/xgiw2i

## Tech Stack

- Frontend: React 19, TypeScript, Vite, React Router, Tailwind CSS
- Backend: Laravel 13, PHP 8.3+
- Data: existing MySQL database `sems` on `127.0.0.1:3306`
- Legacy backend: previous vanilla PHP/mock implementation under `backend-legacy/`

## Project Structure

```text
backend/
  app/Http/Controllers Laravel placeholder API controllers
  app/Http/Responses   Shared API envelope helper
  config/              Laravel application, database, CORS, filesystem, and session config
  public/uploads       Public upload location reserved for avatars
  routes/api.php       API route registration

backend-legacy/
  app/                 Previous vanilla PHP controllers, services, repositories, DTOs, validators, middleware
  public/              Previous PHP public entry point, demo pages, and uploaded assets

frontend/
  src/app/             App shell, providers, and router
  src/components/      Shared layout and UI components
  src/features/        Feature pages for auth, student, professor, and admin portals
  src/lib/             API clients, auth session helpers, and utilities
  src/types/           Shared TypeScript types
```

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL with the existing `sems` database

## Setup

Install backend dependencies:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Install frontend dependencies:

```bash
cd frontend
npm install
```

## Running Locally

Start the API server from the backend directory:

```bash
cd backend
composer serve
```

The API runs at `http://127.0.0.1:8080`.

Start the frontend from the frontend directory:

```bash
cd frontend
npm run dev
```

The frontend runs at the Vite dev server URL, usually `http://127.0.0.1:5173`. Vite proxies `/api` and `/uploads` requests to the Laravel API server at `http://127.0.0.1:8080`.

## API Overview

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

## Backend Notes

- Laravel is configured to use `DB_DATABASE=sems`, `DB_USERNAME=sems`, and `DB_PASSWORD=sems`.
- Do not drop, reset, or recreate the existing database.
- API routes currently return placeholder JSON using the frontend envelope shape.
- Full student, professor, admin, and authentication business logic has not been ported yet.
- The old PHP demo pages and mock-data backend remain available in `backend-legacy/` for reference.

## Build

Build the frontend:

```bash
cd frontend
npm run build
```

## Notes

- Uploaded profile avatars are reserved under `backend/public/uploads/profiles`.
- Laravel session files are stored under `backend/storage/framework/sessions` during local development.
