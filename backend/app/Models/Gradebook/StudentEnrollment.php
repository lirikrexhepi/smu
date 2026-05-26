<?php

namespace App\Models\Gradebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Identity\Student;
use App\Models\Academic\Course;
use App\Models\Attendance\CourseAttendanceSummary;
use App\Models\Attendance\CourseAttendanceRecord;

class StudentEnrollment extends Model
{
    protected $table = 'student_enrollments';

    protected $fillable = [
        'student_id',
        'course_id',
        'semester_id',
        'status',
        'status_label',
        'enrolled_on',
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

    /**
     * @return HasOne<CourseAttendanceSummary, $this>
     */
    public function attendanceSummary(): HasOne
    {
        return $this->hasOne(CourseAttendanceSummary::class, 'student_enrollment_id');
    }

    /**
     * @return HasMany<CourseAttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(CourseAttendanceRecord::class, 'student_enrollment_id');
    }

    /**
     * @return HasMany<CourseGradeRecord, $this>
     */
    public function gradeRecords(): HasMany
    {
        return $this->hasMany(CourseGradeRecord::class, 'student_enrollment_id');
    }
}
