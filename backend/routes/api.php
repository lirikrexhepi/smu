<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\MockStudentDashboardController;
use App\Controllers\StudentProfileController;
use App\Core\Router;
use App\Repositories\Mock\MockStudentDashboardRepository;
use App\Repositories\Mock\MockStudentProfileRepository;
use App\Repositories\Mock\MockUserRepository;
use App\Services\AuthService;
use App\Services\StudentDashboardService;
use App\Services\StudentProfileService;
use App\Validators\LoginRequestValidator;
use App\Validators\StudentProfileAvatarValidator;
use App\Validators\StudentProfileUpdateValidator;

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

    $studentProfileService = new StudentProfileService(new MockStudentProfileRepository());
    $studentProfileController = new StudentProfileController(
        $studentProfileService,
        new StudentProfileUpdateValidator(),
        new StudentProfileAvatarValidator(),
    );

    $router->get('/api/student/profile', [$studentProfileController, 'show']);
    $router->patch('/api/student/profile', [$studentProfileController, 'update']);
    $router->post('/api/student/profile/avatar', [$studentProfileController, 'uploadAvatar']);
};
