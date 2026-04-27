<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentCoursesService;
use App\Services\StudentSessionService;
use RuntimeException;

final class StudentCoursesController
{
    public function __construct(
        private readonly StudentCoursesService $service,
        private readonly StudentSessionService $students,
    ) {
    }

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        $studentKey = $this->students->studentKey();

        if ($studentKey === null) {
            return Response::error('Student session is required', 403, [
                'session' => ['Login as a student to access this resource.'],
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
        $studentKey = $this->students->studentKey();
        $courseId = rawurldecode((string) ($params['courseId'] ?? ''));

        if ($studentKey === null) {
            return Response::error('Student session is required', 403, [
                'session' => ['Login as a student to access this resource.'],
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
}
