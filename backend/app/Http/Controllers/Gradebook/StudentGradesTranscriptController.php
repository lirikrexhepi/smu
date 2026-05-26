<?php

namespace App\Http\Controllers\Gradebook;

use App\Http\Responses\ApiResponse;
use App\Services\StudentGradesTranscriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class StudentGradesTranscriptController
{
    public function __construct(private StudentGradesTranscriptService $service) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->forRequest($request));
    }
}
