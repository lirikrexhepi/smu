<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\StudentCourseDetailData;
use App\DTO\StudentCoursesOverviewData;

interface StudentCoursesRepositoryInterface
{
    /**
     * @param array{search?: string|null, semester?: string|null, status?: string|null, sort?: string|null} $filters
     */
    public function listForStudent(string $studentKey, array $filters = []): StudentCoursesOverviewData;

    public function findForStudent(string $studentKey, string $courseId): StudentCourseDetailData;
}
