<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentAttendanceService;
use RuntimeException;

final class StudentAttendanceController
{
    public function __construct(private readonly StudentAttendanceService $service)
    {
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): Response
    {
        $studentKey = $this->studentKey($request);

        if ($studentKey === null) {
            return Response::error('Student key is required', 422, [
                'studentKey' => ['Login as a student or provide studentKey.'],
            ]);
        }

        $courseId = $this->queryValue($request, 'courseId');
        $semester = $this->queryValue($request, 'semester');
        $week = $this->queryValue($request, 'week');

        try {
            $attendance = $this->service->overview($studentKey, $courseId, $semester, $week)->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student attendance data not found', 404, [
                'studentKey' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $attendance,
            'Student attendance loaded',
            [
                'source' => 'json-mock-repository',
                'studentKey' => $studentKey,
                'courseId' => $courseId,
                'semester' => $semester,
                'week' => $week,
            ],
        );
    }

    private function studentKey(Request $request): ?string
    {
        $studentKey = (string) ($request->query()['studentKey'] ?? $request->header('x-sems-student-key', ''));
        $studentKey = trim($studentKey);

        return $studentKey === '' ? null : $studentKey;
    }

    private function queryValue(Request $request, string $key): ?string
    {
        $value = trim((string) ($request->query()[$key] ?? ''));

        if ($value === '' || strtolower($value) === 'all') {
            return null;
        }

        return $value;
    }
}
