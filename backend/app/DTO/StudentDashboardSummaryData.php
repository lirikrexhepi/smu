<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class StudentDashboardSummaryData
{
    /**
     * @param list<array{label: string, value: string, tone: string}> $metrics
     */
    public function __construct(
        public string $studentName,
        public string $semester,
        public array $metrics,
    ) {
    }

    /**
     * @return array{studentName: string, semester: string, metrics: list<array{label: string, value: string, tone: string}>}
     */
    public function toArray(): array
    {
        return [
            'studentName' => $this->studentName,
            'semester' => $this->semester,
            'metrics' => $this->metrics,
        ];
    }
}
