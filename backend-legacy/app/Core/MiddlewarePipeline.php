<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;

final class MiddlewarePipeline
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(private readonly array $middleware)
    {
    }

    /**
     * @param callable(Request): Response $destination
     */
    public function handle(Request $request, callable $destination): Response
    {
        $runner = array_reduce(
            array_reverse($this->middleware),
            static fn (callable $next, MiddlewareInterface $middleware): callable =>
                static fn (Request $request): Response => $middleware->process($request, $next),
            $destination,
        );

        return $runner($request);
    }
}
