<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Notification;

class PurchaseOrderCreatedNotification extends Notification
{
    public function __construct(
        private PurchaseOrder $purchaseOrder,
        private string $orderNames,
        private int $itemsCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'purchase_order_created',
            'purchase_order_id' => $this->purchaseOrder->id,
            'order_names' => $this->orderNames,
            'items_count' => $this->itemsCount,
            'message' => "Kreirana narudžbenica za nalog(e): {$this->orderNames} ({$this->itemsCount} stavki).",
        ];
    }
}
