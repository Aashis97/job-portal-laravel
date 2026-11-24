<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'company_id';
    public $timestamps = false;

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'company_id', 'company_id');
    }
}
