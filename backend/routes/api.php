<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\StudentCoursesController;
use App\Controllers\StudentDashboardController;
use App\Controllers\StudentProfileController;
use App\Core\Router;
use App\Repositories\Mock\MockStudentCoursesRepository;
use App\Repositories\Mock\MockStudentDashboardRepository;
use App\Repositories\Mock\MockStudentProfileRepository;
use App\Repositories\Mock\MockUserRepository;
use App\Services\AuthService;
use App\Services\StudentCoursesService;
use App\Services\StudentDashboardService;
use App\Services\StudentProfileService;
use App\Validators\LoginRequestValidator;
use App\Validators\StudentProfileAvatarValidator;
use App\Validators\StudentProfileUpdateValidator;

return static function (Router $router): void {
    $router->get('/api/health', [new HealthController(), 'show']);

    $studentProfileRepository = new MockStudentProfileRepository();

    $userRepository = new MockUserRepository($studentProfileRepository);
    $authService = new AuthService($userRepository);
    $router->post('/api/auth/login', [
        new AuthController($authService, new LoginRequestValidator()),
        'login',
    ]);

    $studentDashboardRepository = new MockStudentDashboardRepository($studentProfileRepository);
    $studentDashboardService = new StudentDashboardService($studentDashboardRepository);

    $studentDashboardController = new StudentDashboardController($studentDashboardService);

    $router->get('/api/student/dashboard', [
        $studentDashboardController,
        'show',
    ]);

    $studentCoursesRepository = new MockStudentCoursesRepository();
    $studentCoursesController = new StudentCoursesController(new StudentCoursesService($studentCoursesRepository));

    $router->get('/api/student/courses', [$studentCoursesController, 'index']);
    $router->get('/api/student/courses/{courseId}', [$studentCoursesController, 'show']);

    $studentProfileService = new StudentProfileService($studentProfileRepository);
    $studentProfileController = new StudentProfileController(
        $studentProfileService,
        new StudentProfileUpdateValidator(),
        new StudentProfileAvatarValidator(),
    );

    $router->get('/api/student/profile', [$studentProfileController, 'show']);
    $router->patch('/api/student/profile', [$studentProfileController, 'update']);
    $router->post('/api/student/profile/avatar', [$studentProfileController, 'uploadAvatar']);
};
