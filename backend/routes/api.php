<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\MockStudentDashboardController;
use App\Core\Router;
use App\Repositories\Mock\MockStudentDashboardRepository;
use App\Repositories\Mock\MockUserRepository;
use App\Services\AuthService;
use App\Services\StudentDashboardService;
use App\Validators\LoginRequestValidator;

return static function (Router $router): void {
    $router->get('/api/health', [new HealthController(), 'show']);

    $userRepository = new MockUserRepository();
    $authService = new AuthService($userRepository);
    $router->post('/api/auth/login', [
        new AuthController($authService, new LoginRequestValidator()),
        'login',
    ]);

    $studentDashboardRepository = new MockStudentDashboardRepository();
    $studentDashboardService = new StudentDashboardService($studentDashboardRepository);

    $router->get('/api/mock/student/dashboard', [
        new MockStudentDashboardController($studentDashboardService),
        'show',
    ]);
};
