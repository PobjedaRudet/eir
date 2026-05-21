<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Notifications\Notification;

class OrderRejectedNotification extends Notification
{
    public function __construct(private WorkOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order_rejected',
            'order_id' => $this->order->id,
            'order_name' => $this->order->order_label,
            'project_name' => $this->order->project->name,
            'reviewed_by' => $this->order->reviewer->name,
            'review_note' => $this->order->review_note,
            'message' => "Nalog {$this->order->order_label} ({$this->order->project->name}) je odbijen. Razlog: {$this->order->review_note}",
        ];
    }
}
