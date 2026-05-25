<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\StudentAttendanceData;

interface StudentAttendanceRepositoryInterface
{
    public function getForStudent(
        string $studentKey,
        ?string $courseId = null,
        ?string $semester = null,
        ?string $week = null,
    ): StudentAttendanceData;
}
