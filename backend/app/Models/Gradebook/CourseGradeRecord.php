<?php

namespace App\Models\Gradebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseGradeRecord extends Model
{
    protected $table = 'course_grade_records';

    protected $fillable = [
        'student_enrollment_id',
        'grade_key',
        'title',
        'type',
        'score',
        'weight_label',
        'grade',
        'weight',
        'graded_on',
        'date_label',
        'status',
    ];

    /**
     * @return BelongsTo<StudentEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }
}
