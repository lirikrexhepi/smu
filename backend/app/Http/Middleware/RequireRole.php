<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response|JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Unauthenticated.', status: 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return ApiResponse::error('Forbidden.', status: 403);
        }

        return $next($request);
    }
}
