<?php

namespace App\Models\Identity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Gradebook\StudentEnrollment;

class Student extends Model
{
    /**
     * @return HasMany<StudentEmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(StudentEmergencyContact::class);
    }

    protected $fillable = [
        'user_id',
        'program_id',
        'student_number',
        'student_key',
        'status',
        'status_label',
        'year_of_study',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'nationality',
        'personal_number',
        'profile_updated_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
