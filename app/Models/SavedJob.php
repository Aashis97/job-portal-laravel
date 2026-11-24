<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedJob extends Model
{
    protected $table = 'saved_jobs';
    protected $primaryKey = 'saved_job_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'job_id', 'saved_at'];
}
