<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Gradebook\StudentEnrollment;

class CourseAttendanceSummary extends Model
{
    protected $table = 'course_attendance_summaries';

    protected $fillable = [
        'student_enrollment_id',
        'required_percentage',
        'sessions_held',
        'sessions_attended',
        'status',
        'summary_items',
    ];

    protected $casts = [
        'summary_items' => 'array',
    ];

    /**
     * @return BelongsTo<StudentEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
