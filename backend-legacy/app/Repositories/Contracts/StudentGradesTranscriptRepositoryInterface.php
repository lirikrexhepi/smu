<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTO\StudentGradesTranscriptData;

interface StudentGradesTranscriptRepositoryInterface
{
    public function getForStudent(
        string $studentKey,
        ?string $semester = null,
        ?string $courseId = null,
    ): StudentGradesTranscriptData;
}
