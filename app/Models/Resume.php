<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $table = 'resume';
    protected $primaryKey = 'resume_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'resume_title',
        'resume_file',
        'resume_text',
        'extracted_skills',
        'highest_education',
        'total_experience',
        'parser_status',
        'uploaded_at',
        'updated_at',
    ];
}
