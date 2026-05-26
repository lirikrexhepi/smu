<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\HealthController;
use App\Http\Controllers\Dashboard\StudentDashboardController;
use App\Http\Controllers\Academic\StudentCoursesController;
use App\Http\Controllers\Attendance\StudentAttendanceController;
use App\Http\Controllers\Gradebook\StudentGradesTranscriptController;
use App\Http\Controllers\Profile\StudentProfileController;
use App\Http\Controllers\Dashboard\ProfessorDashboardController;
use App\Http\Controllers\Academic\ProfessorCoursesController;
use App\Http\Controllers\Attendance\ProfessorAttendanceController;
use App\Http\Controllers\Gradebook\ProfessorGradebookController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminCourseController;
use App\Http\Controllers\Admin\AdminReferenceController;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'show']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/session', [AuthController::class, 'session']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::middleware(['jwt.auth', 'role:student'])->prefix('student')->group(function (): void {
    Route::get('/dashboard', [StudentDashboardController::class, 'show']);
    Route::get('/courses', [StudentCoursesController::class, 'index']);
    Route::get('/courses/{courseId}', [StudentCoursesController::class, 'show']);
    Route::get('/attendance', [StudentAttendanceController::class, 'show']);
    Route::post('/attendance/check-in', [StudentAttendanceController::class, 'checkIn']);
    Route::get('/grades-transcript', [StudentGradesTranscriptController::class, 'show']);
    Route::get('/profile', [StudentProfileController::class, 'show']);
    Route::patch('/profile', [StudentProfileController::class, 'update']);
    Route::post('/profile/avatar', [StudentProfileController::class, 'uploadAvatar']);
});

Route::middleware(['jwt.auth', 'role:professor'])->prefix('professor')->group(function (): void {
    Route::get('/dashboard', [ProfessorDashboardController::class, 'show']);
    Route::get('/courses', [ProfessorCoursesController::class, 'index']);
    Route::get('/attendance', [ProfessorAttendanceController::class, 'index']);
    Route::get('/attendance/available-classes', [ProfessorAttendanceController::class, 'availableClasses']);
    Route::post('/attendance/sessions', [ProfessorAttendanceController::class, 'storeSession']);
    Route::post('/attendance/session', [ProfessorAttendanceController::class, 'storeSession']);
    Route::get('/attendance/sessions/{sessionId}', [ProfessorAttendanceController::class, 'showSession']);
    Route::patch('/attendance/sessions/{sessionId}/close', [ProfessorAttendanceController::class, 'closeSession']);
    Route::post('/attendance/session/{sessionId}/record', [ProfessorAttendanceController::class, 'recordSession']);
    Route::get('/gradebook', [ProfessorGradebookController::class, 'index']);
    Route::post('/gradebook/grade', [ProfessorGradebookController::class, 'storeGrade']);
});

Route::middleware(['jwt.auth', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'show']);
    Route::get('/options', [AdminReferenceController::class, 'getOptions']);
    
    // User CRUD
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
    
    // Course CRUD
    Route::get('/courses', [AdminCourseController::class, 'index']);
    Route::get('/courses/{id}', [AdminCourseController::class, 'show']);
    Route::post('/courses', [AdminCourseController::class, 'store']);
    Route::put('/courses/{id}', [AdminCourseController::class, 'update']);
    Route::delete('/courses/{id}', [AdminCourseController::class, 'destroy']);
});

