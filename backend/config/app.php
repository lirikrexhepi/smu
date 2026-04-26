<?php

declare(strict_types=1);

return [
    'name' => 'SEMS API',
    'environment' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOL),
];
