<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\DTO\StudentDashboardSummaryData;
use App\Repositories\Contracts\StudentDashboardRepositoryInterface;

final class MockStudentDashboardRepository implements StudentDashboardRepositoryInterface
{
    public function getSummary(): StudentDashboardSummaryData
    {
        return new StudentDashboardSummaryData(
            studentName: 'Alex Morgan',
            semester: 'Spring 2026',
            metrics: [
                ['label' => 'Enrolled Courses', 'value' => '5', 'tone' => 'blue'],
                ['label' => 'Attendance Rate', 'value' => '94%', 'tone' => 'green'],
                ['label' => 'Current GPA', 'value' => '3.70', 'tone' => 'purple'],
                ['label' => 'Pending Tasks', 'value' => '3', 'tone' => 'orange'],
            ],
        );
    }
}
