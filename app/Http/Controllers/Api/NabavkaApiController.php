<?php

namespace App\Http\Controllers\Api;

use App\Models\PurchaseOrder;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class NabavkaApiController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = PurchaseOrder::with([
            'workOrder.project.city',
            'workOrder.items',
            'workOrder.creator',
        ])
            ->orderByRaw("FIELD(status, 'kreirana', 'narucena', 'isporucena')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($po) => $this->format($po));

        return response()->json(['orders' => $orders]);
    }

    public function markOrdered(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_KREIRANA) {
            return response()->json(['message' => 'Narudžbenica nije u statusu "Kreirana".'], 422);
        }

        $data = $request->validate(['notes' => 'nullable|string|max:1000']);

        $purchaseOrder->update([
            'status'     => PurchaseOrder::STATUS_NARUCENA,
            'notes'      => $data['notes'] ?? $purchaseOrder->notes,
            'ordered_at' => now(),
        ]);

        return response()->json(['order' => $this->format($purchaseOrder->fresh(['workOrder.project.city', 'workOrder.items', 'workOrder.creator']))]);
    }

    public function markDelivered(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_NARUCENA) {
            return response()->json(['message' => 'Narudžbenica nije u statusu "Naručena".'], 422);
        }

        $data = $request->validate(['notes' => 'nullable|string|max:1000']);

        $purchaseOrder->update([
            'status'       => PurchaseOrder::STATUS_ISPORUCENA,
            'notes'        => $data['notes'] ?? $purchaseOrder->notes,
            'delivered_at' => now(),
        ]);

        // Notify vodja (work order creator)
        $purchaseOrder->load(['workOrder.project', 'workOrder.creator']);
        $purchaseOrder->workOrder->creator?->notify(new OrderDeliveredNotification($purchaseOrder));

        return response()->json(['order' => $this->format($purchaseOrder->fresh(['workOrder.project.city', 'workOrder.items', 'workOrder.creator']))]);
    }

    private function format(PurchaseOrder $po): array
    {
        $wo = $po->workOrder;

        return [
            'id'           => $po->id,
            'status'       => $po->status,
            'notes'        => $po->notes,
            'ordered_at'   => $po->ordered_at?->format('d.m.Y. H:i'),
            'delivered_at' => $po->delivered_at?->format('d.m.Y. H:i'),
            'created_at'   => $po->created_at->format('d.m.Y. H:i'),
            'work_order'   => [
                'id'         => $wo->id,
                'name'       => $wo->order_label,
                'date'       => $wo->date->format('d.m.Y.'),
                'created_by' => $wo->creator?->name,
                'project'    => [
                    'id'   => $wo->project->id,
                    'name' => $wo->project->name,
                    'city' => $wo->project->city?->name,
                ],
                'items' => $wo->items->map(fn ($i) => [
                    'id'            => $i->id,
                    'resource_type' => $i->resource_type,
                    'resource_name' => $i->resource_name,
                    'quantity'      => $i->quantity,
                    'unit'          => $i->unit,
                ]),
            ],
        ];
    }
}
