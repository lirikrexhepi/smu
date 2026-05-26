<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Gradebook\StudentEnrollment;

class CourseAttendanceRecord extends Model
{
    protected $table = 'course_attendance_records';

    protected $fillable = [
        'student_enrollment_id',
        'record_key',
        'held_on',
        'date_label',
        'type',
        'status',
        'status_label',
    ];

    /**
     * @return BelongsTo<StudentEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
