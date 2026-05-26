<?php

namespace App\Http\Controllers\Attendance;

use App\Exceptions\AttendanceSessionException;
use App\Http\Responses\ApiResponse;
use App\Services\AttendanceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final readonly class ProfessorAttendanceController
{
    public function __construct(
        private AttendanceSessionService $attendanceSessions
    ) {}

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
            'courseId' => ['nullable', 'string', 'max:64'],
            'courseKey' => ['nullable', 'string', 'max:64'],
            'courseScheduleId' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('The given data was invalid.', $validator->errors()->toArray(), 422);
        }

        $courseId = (string) ($request->input('courseId') ?? $request->input('courseKey') ?? '');
        $courseScheduleId = $request->has('courseScheduleId') ? (int) $request->integer('courseScheduleId') : null;

        if ($courseId === '') {
            return ApiResponse::error('The courseId or courseKey field is required.', status: 422);
        }

        try {
            return ApiResponse::success(
                $this->attendanceSessions->startSession(
                    $request->user(),
                    $courseId,
                    $courseScheduleId
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

    public function recordSession(Request $request, int $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'records'               => ['required', 'array'],
            'records.*.studentId'   => ['required', 'string'],
            'records.*.status'      => ['required', 'string', 'in:present,late,absent,pending'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('The given data was invalid.', $validator->errors()->toArray(), 422);
        }

        try {
            $this->attendanceSessions->recordAttendanceManually(
                $request->user(),
                $sessionId,
                $request->input('records', [])
            );

            return ApiResponse::success(null, 'Attendance recorded successfully');
        } catch (AttendanceSessionException $exception) {
            return ApiResponse::error($exception->getMessage(), status: $exception->status());
        }
    }
}
