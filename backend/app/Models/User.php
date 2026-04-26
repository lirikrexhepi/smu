<?php

declare(strict_types=1);

namespace App\Models;

final readonly class User
{
    public function __construct(
        public string $id,
        public string $name,
        public string $role,
        public string $email,
        public string $institutionId,
        public string $password,
        public string $faculty,
        public string $department,
    ) {
    }
}
