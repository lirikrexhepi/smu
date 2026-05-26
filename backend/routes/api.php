<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentCoursesController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentGradesTranscriptController;
use App\Http\Controllers\StudentProfileController;
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
    Route::get('/grades-transcript', [StudentGradesTranscriptController::class, 'show']);
    Route::get('/profile', [StudentProfileController::class, 'show']);
    Route::patch('/profile', [StudentProfileController::class, 'update']);
    Route::post('/profile/avatar', [StudentProfileController::class, 'uploadAvatar']);
});

Route::middleware(['jwt.auth', 'role:professor'])->prefix('professor')->group(function (): void {
    Route::get('/dashboard', fn () => ApiResponse::success((object) []));
});

Route::middleware(['jwt.auth', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/dashboard', fn () => ApiResponse::success((object) []));
});
