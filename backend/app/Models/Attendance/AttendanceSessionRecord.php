<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Identity\Student;

class AttendanceSessionRecord extends Model
{
    protected $table = 'attendance_session_records';

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status',
        'checked_in_at',
        'method',
    ];

    /**
     * @return BelongsTo<AttendanceSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
