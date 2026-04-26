<?php

declare(strict_types=1);

namespace App\Repositories\Mock;

use App\Data\MockData\SemsMockData;
use App\DTO\StudentDashboardSummaryData;
use App\Repositories\Contracts\StudentDashboardRepositoryInterface;
use App\Repositories\Contracts\StudentProfileRepositoryInterface;

final class MockStudentDashboardRepository implements StudentDashboardRepositoryInterface
{
    public function __construct(private readonly StudentProfileRepositoryInterface $studentProfiles)
    {
    }

    public function getSummary(string $studentKey): StudentDashboardSummaryData
    {
        $dashboard = SemsMockData::studentDashboard($studentKey);
        $profile = $this->studentProfiles->findByStudentKey($studentKey)->toArray();

        $dashboard['studentName'] = (string) $profile['fullName'];
        $dashboard['semester'] = (string) $profile['semester'];
        $dashboard['academicTerm'] = (string) $profile['academicYear'];

        return new StudentDashboardSummaryData($dashboard);
    }
}
