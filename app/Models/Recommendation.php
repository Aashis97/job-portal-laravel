<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    protected $table = 'recommendations';
    protected $primaryKey = 'recommendation_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'job_id',
        'algorithm',
        'matching_score',
        'cosine_similarity',
        'tfidf_score',
        'collaborative_score',
        'cluster_id',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id', 'job_id');
    }
}
