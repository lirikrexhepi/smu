<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Request;
use App\Core\Response;

final class Auth
{
    public static function userId(Request $request): ?string
    {
        return $request->getAuthenticatedUserId();
    }

    public static function requireUser(Request $request): Response|string
    {
        $userId = self::userId($request);

        if (!$userId) {
            return Response::error('Unauthorized', 401);
        }

        return $userId;
    }
}