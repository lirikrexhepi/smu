<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): Response
    {
        return Response::success([
            'status' => 'ok',
            'service' => 'sems-api',
            'timestamp' => gmdate(DATE_ATOM),
        ], 'SEMS API is healthy');
    }
}
