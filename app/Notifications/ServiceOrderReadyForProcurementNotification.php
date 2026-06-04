<?php

namespace App\Notifications;

use App\Models\ServiceOrder;
use Illuminate\Notifications\Notification;

class ServiceOrderReadyForProcurementNotification extends Notification
{
    public function __construct(private ServiceOrder $serviceOrder) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'service_order_ready_for_procurement',
            'service_order_id' => $this->serviceOrder->id,
            'project_name' => $this->serviceOrder->project?->name,
            'item_name' => $this->serviceOrder->resource_name,
            'message' => "Vodja je otvorio servisni zahtjev za {$this->serviceOrder->resource_name} na projektu {$this->serviceOrder->project?->name}. Nabavka treba organizovati slanje dobavljaču.",
        ];
    }
}
