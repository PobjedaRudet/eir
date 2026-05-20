<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    const STATUS_KREIRANA   = 'kreirana';
    const STATUS_NARUCENA   = 'narucena';
    const STATUS_ISPORUCENA = 'isporucena';

    protected $fillable = [
        'work_order_id', 'status', 'notes', 'ordered_at', 'delivered_at', 'created_by',
    ];

    protected $casts = [
        'ordered_at'  => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
