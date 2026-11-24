<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSeekerProfile extends Model
{
    protected $table = 'job_seekers';
    protected $primaryKey = 'seeker_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'education_level',
        'years_of_experience',
        'current_job_title',
        'expected_salary',
        'profile_summary',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'resume_file',
        'profile_completed',
        'availability',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
