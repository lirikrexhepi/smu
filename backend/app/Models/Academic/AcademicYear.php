<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $table = 'academic_years';

    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    /**
     * @return HasMany<Semester, $this>
     */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }
}
