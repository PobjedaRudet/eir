<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourcePlan extends Model
{
    protected $fillable = [
        'project_id', 'created_by', 'version', 'status',
        'notes', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ResourcePlanTeam::class, 'plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResourcePlanItem::class, 'plan_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ResourcePlanHistory::class, 'plan_id')->orderBy('created_at');
    }
}
