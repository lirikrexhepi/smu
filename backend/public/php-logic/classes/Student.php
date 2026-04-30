<?php

declare(strict_types=1);

namespace Demo\Classes;

class Student extends Person
{
    public function __construct(
        string $id,
        string $fullName,
        string $email,
        protected string $program,
        protected int $year,
        protected float $gpa,
    ) {
        parent::__construct($id, $fullName, $email);
    }

    public function getProgram(): string
    {
        return $this->program;
    }

    public function setProgram(string $program): void
    {
        $this->program = trim($program);
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        $this->year = max(1, min(4, $year));
    }

    public function getGpa(): float
    {
        return $this->gpa;
    }

    public function setGpa(float $gpa): void
    {
        $this->gpa = max(0, min(10, $gpa));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getFullName(),
            'email' => $this->getEmail(),
            'program' => $this->getProgram(),
            'year' => $this->getYear(),
            'gpa' => number_format($this->getGpa(), 2),
        ];
    }
}
