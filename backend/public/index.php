<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\MiddlewarePipeline;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\CorsMiddleware;

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;

    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

$config = Config::fromDirectory(__DIR__ . '/../config');
$router = new Router();

(require __DIR__ . '/../routes/api.php')($router);

$pipeline = new MiddlewarePipeline([
    new CorsMiddleware($config->get('cors', [])),
]);

try {
    $request = Request::fromGlobals();
    $response = $pipeline->handle(
        $request,
        static fn (Request $request): Response => $router->dispatch($request),
    );
} catch (Throwable) {
    $response = Response::error('Internal server error', 500);
}

$response->send();
