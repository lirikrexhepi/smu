<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class StudentGradesTranscriptData
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
