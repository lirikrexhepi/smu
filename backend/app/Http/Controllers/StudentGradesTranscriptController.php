<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\StudentGradesTranscriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class StudentGradesTranscriptController
{
    public function __construct(private StudentGradesTranscriptService $grades) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->grades->forRequest($request), 'Student grades transcript loaded');
    }
}
