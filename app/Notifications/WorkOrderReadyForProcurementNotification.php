<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Notifications\Notification;

class WorkOrderReadyForProcurementNotification extends Notification
{
    public function __construct(private WorkOrder $workOrder) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'work_order_ready',
            'work_order_id' => $this->workOrder->id,
            'order_name' => $this->workOrder->order_label,
            'project_name' => $this->workOrder->project->name,
            'items_count' => $this->workOrder->items->count(),
            'message' => "Nalog {$this->workOrder->order_label} ({$this->workOrder->project->name}) odobren — potrebna nabavka za {$this->workOrder->items->count()} stavki.",
        ];
    }
}
