<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\StudentCourseDetailData;
use App\DTO\StudentCoursesOverviewData;
use App\Repositories\Contracts\StudentCoursesRepositoryInterface;

final class StudentCoursesService
{
    public function __construct(private readonly StudentCoursesRepositoryInterface $repository)
    {
    }

    public function overview(string $studentKey): StudentCoursesOverviewData
    {
        return $this->repository->listForStudent($studentKey);
    }

    public function detail(string $studentKey, string $courseId): StudentCourseDetailData
    {
        return $this->repository->findForStudent($studentKey, $courseId);
    }
}
