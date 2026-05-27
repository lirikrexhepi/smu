<?php

namespace App\Http\Controllers\Admin;

use App\Http\Responses\ApiResponse;
use App\Models\Identity\Faculty;
use App\Models\Identity\Department;
use App\Models\Academic\Program;
use App\Models\Academic\Semester;
use App\Models\Identity\Professor;
use Illuminate\Http\JsonResponse;

final class AdminReferenceController
{
    public function getOptions(): JsonResponse
    {
        $faculties = Faculty::select('id', 'name')->orderBy('name')->get();
        $departments = Department::select('id', 'faculty_id', 'name')->orderBy('name')->get();
        $programs = Program::select('id', 'department_id', 'name')->orderBy('name')->get();
        $semesters = Semester::select('id', 'name', 'code')->orderBy('id', 'desc')->get();
        
        $professors = Professor::join('users', 'users.id', '=', 'professors.user_id')
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
