<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentDashboardService;
use RuntimeException;

final class StudentDashboardController
{
    public function __construct(private readonly StudentDashboardService $service)
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

    private function studentKey(Request $request): ?string
    {
        $studentKey = (string) ($request->query()['studentKey'] ?? $request->header('x-sems-student-key', ''));
        $studentKey = trim($studentKey);

        return $studentKey === '' ? null : $studentKey;
    }
}
