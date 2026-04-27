<?php

declare(strict_types=1);

namespace Demo\Classes;

final class HonorStudent extends Student
{
    public function __construct(
        string $id,
        string $fullName,
        string $email,
        string $program,
        int $year,
        float $gpa,
        private float $scholarship,
    ) {
        parent::__construct($id, $fullName, $email, $program, $year, $gpa);
    }

    public function getScholarship(): float
    {
        return $this->scholarship;
    }

    public function setScholarship(float $scholarship): void
    {
        $this->scholarship = max(0, $scholarship);
    }

    public function isEligibleForDeanList(): bool
    {
        return $this->getGpa() >= 9.0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['scholarship'] = number_format($this->getScholarship(), 2);
        $data['deanList'] = $this->isEligibleForDeanList() ? 'Yes' : 'No';

        return $data;
    }
}
