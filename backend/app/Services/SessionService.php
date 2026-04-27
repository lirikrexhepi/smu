<?php

declare(strict_types=1);

namespace App\Services;

final class SessionService
{
    public function __construct(
        private readonly string $storagePath,
    ) {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_save_path($this->storagePath);
            session_start();
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    public function login(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}