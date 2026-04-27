<?php

declare(strict_types=1);

use Demo\Classes\Student;

/**
 * @param array<int, Student> $students
 * @return array<int, Student>
 */
function sortStudentsByGpa(array $students, string $direction = 'desc'): array
{
    usort(
        $students,
        static function (Student $left, Student $right) use ($direction): int {
            $result = $left->getGpa() <=> $right->getGpa();

            return $direction === 'asc' ? $result : -$result;
        },
    );

    return $students;
}

/**
 * @param array<int, Student> $students
 * @return array<string, int>
 */
function countByProgram(array $students): array
{
    $totals = [];

    foreach ($students as $student) {
        $program = $student->getProgram();

        if (!isset($totals[$program])) {
            $totals[$program] = 0;
        }

        $totals[$program]++;
    }

    ksort($totals);

    return $totals;
}

/**
 * @param array<int, Student> $students
 * @return array<int, float>
 */
function getGpaValues(array $students): array
{
    $gpas = [];

    foreach ($students as $student) {
        $gpas[] = $student->getGpa();
    }

    sort($gpas);

    return $gpas;
}
