<?php

namespace App\Http\Controllers\Api;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\PurchaseOrderCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class NabavkaApiController extends Controller
{
    /**
     * List all approved work orders with item-level ordered/unordered tracking.
     */
    public function workOrders(): JsonResponse
    {
        $workOrders = WorkOrder::with(['project.city', 'items', 'creator'])
            ->where('status', WorkOrder::STATUS_APPROVED)
            ->orderBy('created_at', 'desc')
            ->get();

        // Aggregate ordered quantities per work order item
        $itemIds = $workOrders->flatMap(fn ($wo) => $wo->items->pluck('id'));
        $orderedQty = PurchaseOrderItem::whereIn('work_order_item_id', $itemIds)
            ->selectRaw('work_order_item_id, SUM(quantity) as ordered_qty')
            ->groupBy('work_order_item_id')
            ->pluck('ordered_qty', 'work_order_item_id');

        return response()->json([
            'work_orders' => $workOrders->map(fn ($wo) => [
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
                    'quantity'      => (float) $i->quantity,
                    'unit'          => $i->unit,
                    'ordered_qty'   => (float) ($orderedQty[$i->id] ?? 0),
                ]),
            ]),
        ]);
    }

    /**
     * List all purchase orders with their own items.
     */
    public function index(): JsonResponse
    {
        $orders = PurchaseOrder::with(['items.workOrderItem.workOrder.project.city'])
            ->orderByRaw("FIELD(status, 'kreirana', 'narucena', 'isporucena')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($po) => $this->format($po));

        return response()->json(['orders' => $orders]);
    }

    /**
     * Create a new purchase order from selected work order items.
     */
    public function createPurchaseOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notes'                          => 'nullable|string|max:1000',
            'items'                          => 'required|array|min:1',
            'items.*.work_order_item_id'     => 'required|integer|exists:work_order_items,id',
            'items.*.quantity'               => 'required|numeric|min:0.01',
        ]);

        $po = PurchaseOrder::create([
            'status'     => PurchaseOrder::STATUS_KREIRANA,
            'notes'      => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        foreach ($data['items'] as $itemData) {
            $woi = WorkOrderItem::findOrFail($itemData['work_order_item_id']);
            $po->items()->create([
                'work_order_item_id' => $woi->id,
                'resource_type'      => $woi->resource_type,
                'resource_name'      => $woi->resource_name,
                'quantity'           => $itemData['quantity'],
                'unit'               => $woi->unit,
            ]);
        }

        $po->load('items.workOrderItem.workOrder.project.city');

        // Notify vodjas whose work order items are in this PO
        $workOrders = $po->items
            ->map(fn ($i) => $i->workOrderItem?->workOrder)
            ->filter()
            ->unique('id');

        $orderNames = $workOrders->pluck('order_label')->implode(', ');
        $vodjaIds   = $workOrders->pluck('created_by')->filter()->unique();
        $vodje      = User::whereIn('id', $vodjaIds)->get();
        $vodje->each(fn ($u) => $u->notify(
            new PurchaseOrderCreatedNotification($po, $orderNames, $po->items->count())
        ));

        return response()->json(['order' => $this->format($po)], 201);
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

        $purchaseOrder->load('items.workOrderItem.workOrder.project.city');

        return response()->json(['order' => $this->format($purchaseOrder)]);
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

        $purchaseOrder->load('items.workOrderItem.workOrder.project.city');

        // Notify creators of all linked work orders
        $workOrders = $purchaseOrder->items
            ->map(fn ($i) => $i->workOrderItem?->workOrder)
            ->filter()
            ->unique('id');

        foreach ($workOrders as $wo) {
            $wo->creator?->notify(new OrderDeliveredNotification(
                $purchaseOrder,
                $wo->order_label,
                $wo->project->name,
            ));
        }

        return response()->json(['order' => $this->format($purchaseOrder)]);
    }

    private function format(PurchaseOrder $po): array
    {
        // Collect distinct work orders from items
        $workOrders = $po->items
            ->map(fn ($i) => $i->workOrderItem?->workOrder)
            ->filter()
            ->unique('id');

        return [
            'id'           => $po->id,
            'status'       => $po->status,
            'notes'        => $po->notes,
            'ordered_at'   => $po->ordered_at?->format('d.m.Y. H:i'),
            'delivered_at' => $po->delivered_at?->format('d.m.Y. H:i'),
            'created_at'   => $po->created_at->format('d.m.Y. H:i'),
            'work_orders'  => $workOrders->values()->map(fn ($wo) => [
                'id'      => $wo->id,
                'name'    => $wo->order_label,
                'project' => [
                    'id'   => $wo->project->id,
                    'name' => $wo->project->name,
                    'city' => $wo->project->city?->name,
                ],
            ]),
            'items' => $po->items->map(fn ($i) => [
                'id'            => $i->id,
                'resource_type' => $i->resource_type,
                'resource_name' => $i->resource_name,
                'quantity'      => (float) $i->quantity,
                'unit'          => $i->unit,
            ]),
        ];
    }
}
