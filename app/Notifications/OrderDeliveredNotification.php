<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Notification;

class OrderDeliveredNotification extends Notification
{
    public function __construct(
        private PurchaseOrder $purchaseOrder,
        private string $orderName,
        private string $projectName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order_delivered',
            'purchase_order_id' => $this->purchaseOrder->id,
            'order_name' => $this->orderName,
            'project_name' => $this->projectName,
            'message' => "Narudžbenica za nalog {$this->orderName} ({$this->projectName}) je isporučena.",
        ];
    }
}
