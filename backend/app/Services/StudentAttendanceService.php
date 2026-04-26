<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\StudentAttendanceData;
use App\Repositories\Contracts\StudentAttendanceRepositoryInterface;

final class StudentAttendanceService
{
    public function __construct(private readonly StudentAttendanceRepositoryInterface $repository)
    {
    }

    public function overview(
        string $studentKey,
        ?string $courseId = null,
        ?string $semester = null,
        ?string $week = null,
    ): StudentAttendanceData {
        return $this->repository->getForStudent($studentKey, $courseId, $semester, $week);
    }
}
