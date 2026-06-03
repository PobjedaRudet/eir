<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ntv extends Model
{
    protected $fillable = ['name'];

    public function projectNtvs(): HasMany
    {
        return $this->hasMany(ProjectNtv::class, 'ntv_id');
    }
}
