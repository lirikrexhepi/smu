<?php

namespace App\Models\Gradebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Academic\Course;

class CourseGradeComponent extends Model
{
    protected $table = 'course_grade_components';

    protected $fillable = [
        'course_id',
        'component',
        'weight',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
