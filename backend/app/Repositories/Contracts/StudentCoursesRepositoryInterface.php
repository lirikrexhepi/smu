<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\StudentCourseDetailData;
use App\DTO\StudentCoursesOverviewData;

interface StudentCoursesRepositoryInterface
{
    public function listForStudent(string $studentKey): StudentCoursesOverviewData;

    public function findForStudent(string $studentKey, string $courseId): StudentCourseDetailData;
}
