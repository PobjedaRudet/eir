<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderItem extends Model
{
    protected $fillable = [
        'work_order_id', 'resource_type', 'resource_id',
        'resource_name', 'quantity', 'unit', 'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'work_order_item_id');
    }
}
