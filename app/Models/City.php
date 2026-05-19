<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['name'];

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
