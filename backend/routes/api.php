<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\StudentAttendanceController;
use App\Controllers\StudentCoursesController;
use App\Controllers\StudentDashboardController;
use App\Controllers\StudentGradesTranscriptController;
use App\Controllers\StudentProfileController;
use App\Core\Router;
use App\Repositories\Mock\MockStudentAttendanceRepository;
use App\Repositories\Mock\MockStudentCoursesRepository;
use App\Repositories\Mock\MockStudentDashboardRepository;
use App\Repositories\Mock\MockStudentGradesTranscriptRepository;
use App\Repositories\Mock\MockStudentProfileRepository;
use App\Repositories\Mock\MockUserRepository;
use App\Services\AuthService;
use App\Services\StudentAttendanceService;
use App\Services\StudentCoursesService;
use App\Services\StudentDashboardService;
use App\Services\StudentGradesTranscriptService;
use App\Services\StudentProfileService;
use App\Validators\LoginRequestValidator;
use App\Validators\StudentProfileAvatarValidator;
use App\Validators\StudentProfileUpdateValidator;

return static function (Router $router): void {
    $router->get('/api/health', [new HealthController(), 'show']);

    $studentProfileRepository = new MockStudentProfileRepository();

    $userRepository = new MockUserRepository($studentProfileRepository);
    $authService = new AuthService($userRepository);
    $authController = new AuthController($authService, new LoginRequestValidator());

    $router->post('/api/auth/login', [$authController, 'login']);
    $router->get('/api/auth/me', [$authController, 'me']);
    $router->post('/api/auth/logout', [$authController, 'logout']);

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

    $studentAttendanceController = new StudentAttendanceController(
        new StudentAttendanceService(new MockStudentAttendanceRepository()),
    );

    $router->get('/api/student/attendance', [$studentAttendanceController, 'show']);

    $studentGradesTranscriptController = new StudentGradesTranscriptController(
        new StudentGradesTranscriptService(new MockStudentGradesTranscriptRepository()),
    );

    $router->get('/api/student/grades-transcript', [$studentGradesTranscriptController, 'show']);

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
