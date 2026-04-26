<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\StudentGradesTranscriptData;
use App\Repositories\Contracts\StudentGradesTranscriptRepositoryInterface;

final class StudentGradesTranscriptService
{
    public function __construct(private readonly StudentGradesTranscriptRepositoryInterface $repository)
    {
    }

    public function overview(
        string $studentKey,
        ?string $semester = null,
        ?string $courseId = null,
    ): StudentGradesTranscriptData {
        return $this->repository->getForStudent($studentKey, $semester, $courseId);
    }
}
