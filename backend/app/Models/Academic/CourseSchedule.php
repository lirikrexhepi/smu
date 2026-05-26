<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSchedule extends Model
{
    protected $table = 'course_schedules';

    protected $fillable = [
        'course_id',
        'days_label',
        'days',
        'time_label',
        'starts_at',
        'ends_at',
        'room',
        'label',
    ];

    protected $casts = [
        'days' => 'array',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
