<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Responses\ApiResponse;
use App\Exceptions\AttendanceSessionException;
use App\Services\AttendanceSessionService;
use App\Services\StudentAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final readonly class StudentAttendanceController
{
    public function __construct(
        private StudentAttendanceService $service,
        private AttendanceSessionService $attendanceSessions
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->forRequest($request));
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['nullable', 'string', 'regex:/^\d{6}$/'],
            'qrToken' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('The given data was invalid.', $validator->errors()->toArray(), 422);
        }

        $code = trim((string) $request->input('code', ''));
        $qrToken = trim((string) $request->input('qrToken', ''));

        if ($code === '' && $qrToken === '') {
            return ApiResponse::error('Enter a 6-digit code or scan a QR token.', status: 422);
        }

        try {
            $result = $this->attendanceSessions->checkIn(
                $request->user(),
                $code === '' ? null : $code,
                $qrToken === '' ? null : $qrToken
            );

            return ApiResponse::success($result, 'Attendance check-in recorded');
        } catch (AttendanceSessionException $exception) {
            return ApiResponse::error($exception->getMessage(), status: $exception->status());
        }
    }
}
