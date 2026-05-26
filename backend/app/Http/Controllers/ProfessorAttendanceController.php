<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendanceSessionException;
use App\Http\Responses\ApiResponse;
use App\Services\AttendanceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final readonly class ProfessorAttendanceController
{
    public function __construct(private AttendanceSessionService $attendanceSessions) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->attendanceSessions->professorOverview($request->user()),
            'Professor attendance loaded'
        );
    }

    public function availableClasses(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->attendanceSessions->availableClasses($request->user()),
            'Available classes loaded'
        );
    }

    public function storeSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'courseId' => ['required', 'string', 'max:64'],
            'courseScheduleId' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('The given data was invalid.', $validator->errors()->toArray(), 422);
        }

        try {
            return ApiResponse::success(
                $this->attendanceSessions->startSession(
                    $request->user(),
                    (string) $request->string('courseId'),
                    (int) $request->integer('courseScheduleId')
                ),
                'Attendance session started',
                status: 201
            );
        } catch (AttendanceSessionException $exception) {
            return ApiResponse::error($exception->getMessage(), status: $exception->status());
        }
    }

    public function showSession(Request $request, int $sessionId): JsonResponse
    {
        try {
            return ApiResponse::success(
                $this->attendanceSessions->sessionForProfessor($request->user(), $sessionId),
                'Attendance session loaded'
            );
        } catch (AttendanceSessionException $exception) {
            return ApiResponse::error($exception->getMessage(), status: $exception->status());
        }
    }

    public function closeSession(Request $request, int $sessionId): JsonResponse
    {
        try {
            return ApiResponse::success(
                $this->attendanceSessions->closeSession($request->user(), $sessionId),
                'Attendance session closed'
            );
        } catch (AttendanceSessionException $exception) {
            return ApiResponse::error($exception->getMessage(), status: $exception->status());
        }
    }
}
