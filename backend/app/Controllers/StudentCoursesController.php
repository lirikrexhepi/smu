<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentCoursesService;
use RuntimeException;

final class StudentCoursesController
{
    public function __construct(private readonly StudentCoursesService $service)
    {
    }

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        $studentKey = $this->studentKey($request);

        if ($studentKey === null) {
            return Response::error('Student key is required', 422, [
                'studentKey' => ['Login as a student or provide studentKey.'],
            ]);
        }

        try {
            $courses = $this->service->overview($studentKey)->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student course data not found', 404, [
                'studentKey' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $courses,
            'Student courses loaded',
            ['source' => 'json-mock-repository', 'studentKey' => $studentKey],
        );
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): Response
    {
        $studentKey = $this->studentKey($request);
        $courseId = rawurldecode((string) ($params['courseId'] ?? ''));

        if ($studentKey === null) {
            return Response::error('Student key is required', 422, [
                'studentKey' => ['Login as a student or provide studentKey.'],
            ]);
        }

        if (trim($courseId) === '') {
            return Response::error('Course id is required', 422, [
                'courseId' => ['Provide a course id.'],
            ]);
        }

        try {
            $course = $this->service->detail($studentKey, $courseId)->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student course data not found', 404, [
                'courseId' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $course,
            'Student course loaded',
            ['source' => 'json-mock-repository', 'studentKey' => $studentKey, 'courseId' => $courseId],
        );
    }

    private function studentKey(Request $request): ?string
    {
        $studentKey = (string) ($request->query()['studentKey'] ?? $request->header('x-sems-student-key', ''));
        $studentKey = trim($studentKey);

        return $studentKey === '' ? null : $studentKey;
    }
}
