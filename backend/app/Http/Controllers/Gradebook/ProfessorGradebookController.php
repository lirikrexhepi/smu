<?php

namespace App\Http\Controllers\Gradebook;

use App\Http\Responses\ApiResponse;
use App\Services\Gradebook\ProfessorGradebookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final readonly class ProfessorGradebookController
{
    public function __construct(
        private ProfessorGradebookService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'professor') {
            return ApiResponse::error('Unauthorized access.', status: 403);
        }

        $data = $this->service->getGradebookData($user);

        return ApiResponse::success($data, 'Gradebook details loaded.');
    }

    public function storeGrade(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'professor') {
            return ApiResponse::error('Unauthorized access.', status: 403);
        }

        $validator = Validator::make($request->all(), [
            'studentId' => ['required', 'string'],
            'courseCode' => ['required', 'string'],
            'component' => ['required', 'string', 'in:midterm,project,final'],
            'grade' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed.', $validator->errors()->toArray(), 422);
        }

        try {
            $this->service->saveGrade($user, $request->all());
            return ApiResponse::success(null, 'Grade recorded successfully.');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), status: 400);
        }
    }
}
