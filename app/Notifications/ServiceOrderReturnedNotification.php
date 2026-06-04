<?php

namespace App\Notifications;

use App\Models\ServiceOrder;
use Illuminate\Notifications\Notification;

class ServiceOrderReturnedNotification extends Notification
{
    public function __construct(private ServiceOrder $serviceOrder) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'service_order_returned',
            'service_order_id' => $this->serviceOrder->id,
            'message' => "Nabavka je evidentirala povrat sa servisa za {$this->serviceOrder->resource_name} na projektu {$this->serviceOrder->project?->name}.",
        ];
    }
}
