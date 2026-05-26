<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Identity\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class JwtAuthenticate
{
    public function __construct(private JwtService $jwt) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $claims = $this->jwt->validate($request->bearerToken() ?? '');

        if ($claims === null) {
            return ApiResponse::error('Unauthenticated.', status: 401);
        }

        $user = User::query()
            ->with(['faculty', 'department'])
            ->find($claims['sub']);

        if (! $user instanceof User || $user->public_id !== $claims['pid'] || $user->role !== $claims['role']) {
            return ApiResponse::error('Unauthenticated.', status: 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }
}
