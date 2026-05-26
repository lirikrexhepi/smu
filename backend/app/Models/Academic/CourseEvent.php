<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEvent extends Model
{
    protected $table = 'course_events';

    protected $fillable = [
        'course_id',
        'event_key',
        'category',
        'title',
        'type',
        'event_date',
        'event_time',
        'date_label',
        'time_label',
        'status_label',
        'tone',
        'mode',
        'duration',
        'room',
        'description',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
