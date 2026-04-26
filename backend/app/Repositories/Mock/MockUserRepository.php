<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\Data\MockData\SemsMockData;
use App\Models\User;
use App\Repositories\Contracts\StudentProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

final class MockUserRepository implements UserRepositoryInterface
{
    /**
     * @var list<User>
     */
    private array $users;

    public function __construct(private readonly ?StudentProfileRepositoryInterface $studentProfiles = null)
    {
        $this->users = [];
        $studentProfiles = $this->studentProfiles ?? new MockStudentProfileRepository();

        foreach (SemsMockData::users() as $user) {
            if ($user['role'] === 'student') {
                $profile = $studentProfiles->findByStudentKey((string) $user['institutionId'])->toArray();

                $this->users[] = new User(
                    $user['id'],
                    (string) $profile['fullName'],
                    $user['role'],
                    (string) $profile['email'],
                    $user['institutionId'],
                    $user['password'],
                    (string) $profile['faculty'],
                    (string) $profile['department'],
                    is_string($profile['avatarUrl'] ?? null) ? $profile['avatarUrl'] : null,
                );
                continue;
            }

            $this->users[] = new User(
                $user['id'],
                $user['name'],
                $user['role'],
                $user['email'],
                $user['institutionId'],
                $user['password'],
                $user['faculty'],
                $user['department'],
            );
        }
    }

    public function findByIdentifier(string $identifier): ?User
    {
        $normalizedIdentifier = strtolower(trim($identifier));

        foreach ($this->users as $user) {
            if (
                strtolower($user->email) === $normalizedIdentifier
                || strtolower($user->institutionId) === $normalizedIdentifier
            ) {
                return $user;
            }
        }

        return null;
    }
}
