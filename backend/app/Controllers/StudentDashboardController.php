<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentDashboardService;
use App\Services\StudentSessionService;
use RuntimeException;

final class StudentDashboardController
{
    public function __construct(
        private readonly StudentDashboardService $service,
        private readonly StudentSessionService $students,
    ) {
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): Response
    {
        $studentKey = $this->students->studentKey();

        if ($studentKey === null) {
            return Response::error('Student session is required', 403, [
                'session' => ['Login as a student to access this resource.'],
            ]);
        }

        try {
            $dashboard = $this->service->summary($studentKey)->toArray();
        } catch (RuntimeException $exception) {
            return Response::error('Student dashboard data not found', 404, [
                'studentKey' => [$exception->getMessage()],
            ]);
        }

        return Response::success(
            $dashboard,
            'Student dashboard loaded',
            ['source' => 'json-mock-repository', 'studentKey' => $studentKey],
        );
    }
}
