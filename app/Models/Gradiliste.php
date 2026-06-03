<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gradiliste extends Model
{
    protected $table = 'gradilista';

    protected $fillable = ['project_id'];
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'gradiliste_equipment')
            ->withPivot('quantity');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'gradiliste_materials')
            ->withPivot('quantity');
    }
}
