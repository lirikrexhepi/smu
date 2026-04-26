<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentGradesTranscriptService;
use RuntimeException;

final class StudentGradesTranscriptController
{
    public function __construct(private readonly StudentGradesTranscriptService $service)
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

        $semester = $this->queryValue($request, 'semester');
        $courseId = $this->queryValue($request, 'courseId');

        try {
            $gradesTranscript = $this->service->overview($studentKey, $semester, $courseId)->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student grades transcript data not found', 404, [
                'studentKey' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $gradesTranscript,
            'Student grades transcript loaded',
            [
                'source' => 'json-mock-repository',
                'studentKey' => $studentKey,
                'semester' => $semester,
                'courseId' => $courseId,
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
