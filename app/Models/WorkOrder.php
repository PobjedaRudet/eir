<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    const SOURCE_MANUAL = 'manual';

    const SOURCE_ASSIGNMENT_REQUEST = 'assignment_request';

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'project_id', 'plan_id', 'name', 'description', 'date', 'created_by',
        'status', 'review_note', 'reviewed_at', 'reviewed_by',
        'order_number', 'order_year',
        'source_type',
    ];

    protected $casts = [
        'date' => 'date',
        'reviewed_at' => 'datetime',
        'order_number' => 'integer',
        'order_year' => 'integer',
    ];

    public function getOrderLabelAttribute(): string
    {
        if ($this->order_number && $this->order_year) {
            return $this->order_number.'/'.substr((string) $this->order_year, -2);
        }

        return $this->name ?? '#'.$this->id;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ResourcePlan::class, 'plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }
}
