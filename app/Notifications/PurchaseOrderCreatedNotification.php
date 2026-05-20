<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Notification;

class PurchaseOrderCreatedNotification extends Notification
{
    public function __construct(private PurchaseOrder $purchaseOrder) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $order = $this->purchaseOrder->workOrder;

        return [
            'type'              => 'purchase_order_created',
            'purchase_order_id' => $this->purchaseOrder->id,
            'order_id'          => $order->id,
            'order_name'        => $order->order_label,
            'project_name'      => $order->project->name,
            'items_count'       => $order->items->count(),
            'message'           => "Nova narudžbenica kreirana za nalog {$order->order_label} ({$order->project->name}).",
        ];
    }
}
