<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResourcePlanTeam extends Model
{
    protected $fillable = ['plan_id', 'name'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ResourcePlan::class, 'plan_id');
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resource_plan_team_workers', 'team_id', 'user_id');
    }
}
