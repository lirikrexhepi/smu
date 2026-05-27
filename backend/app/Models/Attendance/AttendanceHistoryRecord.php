<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Identity\Student;
use App\Models\Academic\Course;

class AttendanceHistoryRecord extends Model
{
    protected $table = 'attendance_history_records';

    protected $fillable = [
        'student_id',
        'course_id',
        'record_key',
        'recorded_on',
        'date_label',
        'time_label',
        'type',
        'professor_name',
        'result',
        'result_label',
    ];

    protected $casts = [
        'recorded_on' => 'date',
    ];

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
