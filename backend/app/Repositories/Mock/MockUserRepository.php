<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

final class MockUserRepository implements UserRepositoryInterface
{
    /**
     * @var list<User>
     */
    private array $users;

    public function __construct()
    {
        $this->users = [
            new User('stu-1001', 'Luri Morina', 'student', 'luri.morina@sems.edu', 'luri', '1234', 'Faculty of Electrical and Computer Engineering', 'Computer Engineering BScs'),
            new User('stu-1002', 'Maya Patel', 'student', 'maya.patel@sems.edu', 'S1002', 'student123', 'Faculty of Information Sciences', 'Information Systems BSc'),
            new User('stu-1003', 'Daniel Kovacs', 'student', 'daniel.kovacs@sems.edu', 'S1003', 'student123', 'Faculty of Information Sciences', 'Software Engineering BSc'),
            new User('stu-1004', 'Nora Williams', 'student', 'nora.williams@sems.edu', 'S1004', 'student123', 'Faculty of Information Sciences', 'Data Science BSc'),
            new User('stu-1005', 'Leo Schneider', 'student', 'leo.schneider@sems.edu', 'S1005', 'student123', 'Faculty of Information Sciences', 'Cybersecurity BSc'),
            new User('prof-2001', 'Dr. Evelyn Carter', 'professor', 'evelyn.carter@sems.edu', 'P2001', 'professor123', 'Faculty of Information Sciences', 'Department of Computer Science'),
            new User('prof-2002', 'Dr. Marcus Reed', 'professor', 'marcus.reed@sems.edu', 'P2002', 'professor123', 'Faculty of Information Sciences', 'Department of Information Systems'),
        ];
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
