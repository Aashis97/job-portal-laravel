<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $table = 'applications';
    protected $primaryKey = 'application_id';
    public $timestamps = false;
    protected $fillable = [
        'job_id',
        'user_id',
        'cover_letter',
        'cover_letter_file',
        'application_status',
        'skill_match_score',
        'ai_rank',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }
}
