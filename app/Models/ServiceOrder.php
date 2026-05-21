<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrder extends Model
{
    const STATUS_SENT = 'sent';

    const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'project_id', 'work_order_item_id', 'quantity_sent', 'status', 'note', 'sent_at', 'returned_at', 'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'returned_at' => 'datetime',
        'quantity_sent' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class, 'work_order_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
