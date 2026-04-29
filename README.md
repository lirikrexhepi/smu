# SEMS - Student Education Management System

Phase I university project built without a database. Data is simulated through static mock files and PHP arrays.

## Requirements

- PHP 8.1+
- Composer
- Node.js and npm

## Run Backend

```bash
cd backend
composer install
composer serve
```

Backend runs at:

```text
http://127.0.0.1:8080
```

PHP requirement demo pages:

```text
http://127.0.0.1:8080/php-demo/index.php
http://127.0.0.1:8080/php-demo/arrays.php
http://127.0.0.1:8080/php-demo/sorting.php
http://127.0.0.1:8080/php-demo/inheritance.php
```

## Run Frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend runs at:

```text
http://localhost:5173
```

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

## Video e Prezantimit

Ne kete link gjendet video e prezantimit te projektit. Video eshte hostuar ne Streamable pasi qe madhesia e videos ka qene shume e madhe per ta derguar si attachment:

```text
https://streamable.com/xgiw2i
```

## Phase I Coverage

- No database is used.
- Login/logout uses static mock credentials from `backend/app/Data/MockData/users.json`.
- Session state is stored through PHP sessions.
- Cookies are used for a signed `user_token` personalization cookie.
- Student and professor roles are protected separately by role middleware.
- PHP includes are demonstrated in `backend/public/php-demo/includes`.
- PHP arrays, loops, functions and sorting are demonstrated in `backend/public/php-demo`.
- OOP, constructors, getters/setters, encapsulation and inheritance are demonstrated with `Person`, `Student` and `HonorStudent`.
- Server-side validation uses RegEx for identifiers and phone numbers.

## Notes

Professor pages are placeholders and are planned for the next phase. The Phase I functional scope should be assessed mainly through the student app and the PHP demo pages.
