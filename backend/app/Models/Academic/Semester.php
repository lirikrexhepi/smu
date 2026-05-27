<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $fillable = [
        'academic_year_id',
        'code',
        'name',
        'number',
        'starts_on',
        'ends_on',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'number' => 'integer',
    ];

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
