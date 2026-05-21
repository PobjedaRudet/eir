<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePlanItem extends Model
{
    protected $fillable = [
        'plan_id', 'resource_type', 'resource_id', 'resource_name',
        'quantity', 'unit', 'start_date', 'end_date', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'quantity' => 'float',
    ];

    const TYPE_EQUIPMENT = 'equipment';

    const TYPE_MATERIAL = 'material';

    const TYPE_SERVICE = 'service';

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ResourcePlan::class, 'plan_id');
    }
}
