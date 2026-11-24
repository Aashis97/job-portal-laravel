<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'category_id', 'category_id');
    }
}
