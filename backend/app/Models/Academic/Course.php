<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Identity\Professor;
use App\Models\Identity\Department;
use App\Models\Academic\Semester;
use App\Models\Gradebook\StudentEnrollment;
use App\Models\Attendance\AttendanceSession;

class Course extends Model
{
    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    protected $fillable = [
        'course_key',
        'code',
        'name',
        'department_id',
        'semester_id',
        'ects',
        'status',
        'room',
        'description',
        'learning_outcomes',
        'topics',
        'grading_breakdown',
    ];

    protected $casts = [
        'learning_outcomes' => 'array',
        'topics' => 'array',
    ];

    /**
     * @return BelongsToMany<Professor, $this>
     */
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'course_professor');
    }

    /**
     * @return HasMany<CourseSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class);
    }

    /**
     * @return HasMany<CourseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(CourseEvent::class);
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * @return HasMany<AttendanceSession, $this>
     */
    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }
}
