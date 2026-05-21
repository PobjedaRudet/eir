<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Notifications\Notification;

class OrderApprovedNotification extends Notification
{
    public function __construct(private WorkOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order_approved',
            'order_id' => $this->order->id,
            'order_name' => $this->order->order_label,
            'project_name' => $this->order->project->name,
            'reviewed_by' => $this->order->reviewer->name,
            'message' => "Nalog {$this->order->order_label} ({$this->order->project->name}) je odobren.",
        ];
    }
}
