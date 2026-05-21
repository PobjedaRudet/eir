<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    protected $fillable = ['name', 'category', 'description'];

    // category labels
    public const CATEGORIES = [
        'masina' => 'Mašine',
        'wc' => 'WC',
        'kontejner' => 'Kontejneri',
        'ostalo' => 'Ostalo',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_equipment')
            ->withPivot('quantity');
    }
}
