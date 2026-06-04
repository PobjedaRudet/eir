<?php

namespace App\Notifications;

use App\Models\ServiceOrder;
use Illuminate\Notifications\Notification;

class ServiceOrderForwardedNotification extends Notification
{
    public function __construct(private ServiceOrder $serviceOrder) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'service_order_forwarded',
            'service_order_id' => $this->serviceOrder->id,
            'supplier_name' => $this->serviceOrder->supplier_name,
            'message' => "Nabavka je proslijedila {$this->serviceOrder->resource_name} dobavljaču {$this->serviceOrder->supplier_name} za servis.",
        ];
    }
}
