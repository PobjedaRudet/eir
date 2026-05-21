<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Notifications\Notification;

class OrderSubmittedNotification extends Notification
{
    public function __construct(private WorkOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order_submitted',
            'order_id' => $this->order->id,
            'order_name' => $this->order->order_label,
            'project_name' => $this->order->project->name,
            'submitted_by' => $this->order->creator->name,
            'message' => "Vodja {$this->order->creator->name} je podnio nalog {$this->order->order_label} ({$this->order->project->name}) na odobrenje.",
        ];
    }
}
