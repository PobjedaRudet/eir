<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrder extends Model
{
    const STATUS_PENDING_PROCUREMENT = 'pending_procurement';

    const STATUS_SENT_TO_SUPPLIER = 'sent_to_supplier';

    const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'project_id', 'work_order_item_id', 'resource_type', 'resource_id', 'resource_name', 'resource_unit', 'source_label', 'quantity_sent', 'status', 'note', 'sent_at', 'forwarded_at', 'returned_at', 'created_by', 'handled_by', 'supplier_name', 'supplier_email', 'procurement_note',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'forwarded_at' => 'datetime',
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

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
