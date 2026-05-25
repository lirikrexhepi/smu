<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentCoursesController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentGradesTranscriptController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'show']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/session', [AuthController::class, 'session']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::get('/student/dashboard', [StudentDashboardController::class, 'show']);
Route::get('/student/courses', [StudentCoursesController::class, 'index']);
Route::get('/student/courses/{courseId}', [StudentCoursesController::class, 'show']);
Route::get('/student/attendance', [StudentAttendanceController::class, 'show']);
Route::get('/student/grades-transcript', [StudentGradesTranscriptController::class, 'show']);
Route::get('/student/profile', [StudentProfileController::class, 'show']);
Route::patch('/student/profile', [StudentProfileController::class, 'update']);
Route::post('/student/profile/avatar', [StudentProfileController::class, 'uploadAvatar']);
