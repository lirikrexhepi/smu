# SEMS - Student Education Management System

SEMS is a demo Student Education Management System with a React/Vite frontend and a lightweight PHP API backend. The app includes role-based portal shells for students, professors, and admins, with mock data for dashboards, courses, attendance, grades, transcripts, profiles, and authentication.

## Demo Video

Project demo: https://streamable.com/xgiw2i

## Tech Stack

- Frontend: React 19, TypeScript, Vite, React Router, Tailwind CSS
- Backend: PHP 8.1+, custom router, middleware pipeline, PSR-4 autoloading
- Data: JSON mock data under `backend/app/Data/MockData`
- Auth: mock login with PHP session state and a signed `user_token` personalization cookie

## Project Structure

```text
backend/
  app/                 PHP controllers, services, repositories, DTOs, validators, middleware
  config/              Application and CORS config
  public/              PHP public entry point, demo pages, and uploaded assets
  routes/api.php       API route registration
  storage/             Runtime storage for logs and sessions

frontend/
  src/app/             App shell, providers, and router
  src/components/      Shared layout and UI components
  src/features/        Feature pages for auth, student, professor, and admin portals
  src/lib/             API clients, auth session helpers, and utilities
  src/types/           Shared TypeScript types
```

## Requirements

- PHP 8.1 or newer
- Composer
- Node.js and npm

## Setup

Install backend dependencies:

```bash
cd backend
composer install
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

The frontend runs at the Vite dev server URL, usually `http://127.0.0.1:5173`. Vite proxies `/api` and `/uploads` requests to the PHP API server.

## Demo Accounts

Student:

```text
ID: luri
Password: Student@123
```

Professor:

```text
ID: P2001
Password: Teacher@123
```

Additional identifiers are available in `backend/app/Data/MockData/users.json`. The login form accepts an institution ID or email where the mock user includes one.

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

## PHP Requirement Demo Pages

```text
http://127.0.0.1:8080/php-demo/index.php
http://127.0.0.1:8080/php-demo/arrays.php
http://127.0.0.1:8080/php-demo/sorting.php
http://127.0.0.1:8080/php-demo/inheritance.php
```

## Phase I Coverage

- No database is used.
- Login/logout uses static mock credentials from `backend/app/Data/MockData/users.json`.
- Session state is stored through PHP sessions.
- Cookies are used for a signed `user_token` personalization cookie.
- Student and professor roles are protected separately by role middleware.
- PHP includes are demonstrated in `backend/public/php-demo/includes`.
- PHP arrays, loops, functions, and sorting are demonstrated in `backend/public/php-demo`.
- OOP, constructors, getters/setters, encapsulation, and inheritance are demonstrated with `Person`, `Student`, and `HonorStudent`.
- Server-side validation uses RegEx for identifiers and phone numbers.

## Build

Build the frontend:

```bash
cd frontend
npm run build
```

## Notes

- The backend uses mock repositories, so no database is required for the current demo flow.
- Uploaded profile avatars are stored under `backend/public/uploads/profiles`.
- Session files are stored under `backend/storage/sessions` at runtime.
