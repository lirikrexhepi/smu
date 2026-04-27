<?php

declare(strict_types=1);

namespace App\Services;

final class StudentSessionService
{
    public function __construct(private readonly SessionService $sessions)
    {
    }

    public function studentKey(): ?string
    {
        $user = $this->sessions->user();

        if (($user['role'] ?? null) !== 'student') {
            return null;
        }

        $studentKey = trim((string) ($user['institutionId'] ?? ''));

        return $studentKey === '' ? null : $studentKey;
    }
}
