<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enclosure extends Model
{
    protected $fillable = ['name'];

    public function workEntries(): HasMany
    {
        return $this->hasMany(WorkEntry::class);
    }
}
