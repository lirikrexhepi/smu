<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\StudentDashboardSummaryData;
use App\Repositories\Contracts\StudentDashboardRepositoryInterface;

final class StudentDashboardService
{
    public function __construct(private readonly StudentDashboardRepositoryInterface $repository)
    {
    }

    public function summary(string $studentKey): StudentDashboardSummaryData
    {
        return $this->repository->getSummary($studentKey);
    }
}
