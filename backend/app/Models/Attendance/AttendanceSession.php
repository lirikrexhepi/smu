<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Academic\Course;
use App\Models\Academic\CourseSchedule;
use App\Models\Identity\Professor;

class AttendanceSession extends Model
{
    protected $table = 'attendance_sessions';

    protected $fillable = [
        'course_id',
        'professor_id',
        'course_schedule_id',
        'code',
        'qr_token',
        'starts_at',
        'ends_at',
        'late_after_at',
        'closed_at',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Professor, $this>
     */
    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    /**
     * @return BelongsTo<CourseSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CourseSchedule::class, 'course_schedule_id');
    }

    /**
     * @return HasMany<AttendanceSessionRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(AttendanceSessionRecord::class);
    }
}
