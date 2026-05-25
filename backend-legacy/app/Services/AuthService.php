<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;

final class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attemptLogin(string $identifier, string $password): ?array
    {
        $user = $this->users->findByIdentifier($identifier);

        if ($user === null || $user->password !== $password) {
            return null;
        }

        $redirectPath = match ($user->role) {
            'professor' => '/professor/dashboard',
            'student' => '/student/dashboard',
            default => '/login',
        };

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'email' => $user->email,
                'institutionId' => $user->institutionId,
                'faculty' => $user->faculty,
                'department' => $user->department,
                'avatarUrl' => $user->avatarUrl,
            ],
            'redirectPath' => $redirectPath,
        ];
    }
}
