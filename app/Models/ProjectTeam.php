<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTeam extends Model
{
    protected $fillable = ['project_id', 'name', 'finished_at'];

    protected $casts = [
        'finished_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return is_null($this->finished_at);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('finished_at');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectNtvs(): HasMany
    {
        return $this->hasMany(ProjectNtv::class, 'project_team_id');
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_team_users');
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'project_team_equipment')
            ->withPivot('quantity');
    }
}
