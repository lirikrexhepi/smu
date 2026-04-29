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

    /**
     * @param array{search?: string|null, semester?: string|null, status?: string|null, sort?: string|null} $filters
     */
    public function overview(string $studentKey, array $filters = []): StudentCoursesOverviewData
    {
        return $this->repository->listForStudent($studentKey, $filters);
    }

    public function detail(string $studentKey, string $courseId): StudentCourseDetailData
    {
        return $this->repository->findForStudent($studentKey, $courseId);
    }
}
