<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectNtv extends Model
{
    protected $fillable = ['project_id', 'ntv_id', 'project_team_id'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function ntv(): BelongsTo
    {
        return $this->belongsTo(Ntv::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ProjectTeam::class, 'project_team_id');
    }

    public function streets(): BelongsToMany
    {
        return $this->belongsToMany(Street::class, 'project_ntv_streets', 'project_ntv_id', 'street_id');
    }
}
