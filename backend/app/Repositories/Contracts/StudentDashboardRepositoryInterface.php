<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\StudentDashboardSummaryData;

interface StudentDashboardRepositoryInterface
{
    public function getSummary(): StudentDashboardSummaryData;
}
