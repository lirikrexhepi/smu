<?php

namespace App\Http\Controllers\Admin;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class AdminReferenceController
{
    public function getOptions(): JsonResponse
    {
        $faculties = DB::table('faculties')->select('id', 'name')->orderBy('name')->get();
        $departments = DB::table('departments')->select('id', 'faculty_id', 'name')->orderBy('name')->get();
        $programs = DB::table('programs')->select('id', 'department_id', 'name')->orderBy('name')->get();
        $semesters = DB::table('semesters')->select('id', 'name', 'code')->orderBy('id', 'desc')->get();
        
        $professors = DB::table('professors')
            ->join('users', 'users.id', '=', 'professors.user_id')
            ->select('professors.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        return ApiResponse::success([
            'faculties' => $faculties,
            'departments' => $departments,
            'programs' => $programs,
            'semesters' => $semesters,
            'professors' => $professors,
        ], 'Reference options retrieved successfully.');
    }
}
