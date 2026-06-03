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

    public function gradilista(): BelongsToMany
    {
        return $this->belongsToMany(Gradiliste::class, 'gradiliste_equipment')
            ->withPivot('quantity');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(ProjectTeam::class, 'project_team_equipment')
            ->withPivot('quantity');
    }
}
