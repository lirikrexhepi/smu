<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentDashboardService;

final class MockStudentDashboardController
{
    public function __construct(private readonly StudentDashboardService $service)
    {
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): Response
    {
        return Response::success(
            $this->service->summary()->toArray(),
            'Mock student dashboard loaded',
            ['source' => 'mock-repository'],
        );
    }
}
