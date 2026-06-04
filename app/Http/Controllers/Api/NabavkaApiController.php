<?php

namespace App\Http\Controllers\Api;

use App\Mail\ServiceOrderMail;
use App\Mail\PurchaseOrderMail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\PurchaseOrderCreatedNotification;
use App\Notifications\ServiceOrderForwardedNotification;
use App\Notifications\ServiceOrderReturnedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class NabavkaApiController extends Controller
{
    public function serviceOrders(): JsonResponse
    {
        $orders = ServiceOrder::with(['project.city', 'creator', 'handler'])
            ->orderByRaw("FIELD(status, 'pending_procurement', 'sent_to_supplier', 'returned')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (ServiceOrder $serviceOrder) => $this->formatServiceOrder($serviceOrder));

        return response()->json(['orders' => $orders]);
    }

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
                    'id'   => $wo->project?->id,
                    'name' => $wo->project?->name ?? 'Obrisan projekat',
                    'city' => $wo->project?->city?->name,
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

    public function sendToSupplier(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_KREIRANA) {
            return response()->json(['message' => 'Narudžbenica nije u statusu "Kreirana".'], 422);
        }

        $data = $request->validate([
            'supplier_name'  => 'nullable|string|max:200',
            'supplier_email' => 'required|email|max:200',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $purchaseOrder->update([
            'supplier_name'  => $data['supplier_name'] ?? null,
            'supplier_email' => $data['supplier_email'],
            'notes'          => $data['notes'] ?? $purchaseOrder->notes,
        ]);

        $purchaseOrder->load('items.workOrderItem.workOrder.project.city');

        Mail::to($data['supplier_email'])->send(new PurchaseOrderMail($purchaseOrder));

        $purchaseOrder->update([
            'status'     => PurchaseOrder::STATUS_NARUCENA,
            'ordered_at' => now(),
        ]);

        $purchaseOrder->refresh()->load('items.workOrderItem.workOrder.project.city');

        return response()->json(['order' => $this->format($purchaseOrder)]);
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load('items.workOrderItem.workOrder.project.city');

        $pdf = Pdf::loadView('pdf.purchase-order', ['po' => $purchaseOrder]);

        return $pdf->download("narudzbenica-{$purchaseOrder->id}.pdf");
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
                $wo->project?->name ?? 'Obrisan projekat',
            ));
        }

        return response()->json(['order' => $this->format($purchaseOrder)]);
    }

    public function sendServiceOrderToSupplier(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ($serviceOrder->status !== ServiceOrder::STATUS_PENDING_PROCUREMENT) {
            return response()->json(['message' => 'Servisni nalog ne čeka obradu nabavke.'], 422);
        }

        $data = $request->validate([
            'supplier_name' => 'required|string|max:200',
            'supplier_email' => 'nullable|email|max:200',
            'procurement_note' => 'nullable|string|max:1000',
        ]);

        $serviceOrder->update([
            'status' => ServiceOrder::STATUS_SENT_TO_SUPPLIER,
            'supplier_name' => $data['supplier_name'],
            'supplier_email' => $data['supplier_email'] ?? null,
            'procurement_note' => $data['procurement_note'] ?? null,
            'forwarded_at' => now(),
            'handled_by' => Auth::id(),
        ]);

        $serviceOrder->load(['project.city', 'creator', 'handler']);

        if (!empty($data['supplier_email'])) {
            Mail::to($data['supplier_email'])->send(new ServiceOrderMail($serviceOrder));
        }

        $serviceOrder->creator?->notify(new ServiceOrderForwardedNotification($serviceOrder));

        return response()->json(['order' => $this->formatServiceOrder($serviceOrder)]);
    }

    public function returnServiceOrder(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ($serviceOrder->status !== ServiceOrder::STATUS_SENT_TO_SUPPLIER) {
            return response()->json(['message' => 'Servisni nalog nije kod dobavljača.'], 422);
        }

        $data = $request->validate([
            'procurement_note' => 'nullable|string|max:1000',
        ]);

        $serviceOrder->update([
            'status' => ServiceOrder::STATUS_RETURNED,
            'returned_at' => now(),
            'procurement_note' => $data['procurement_note'] ?? $serviceOrder->procurement_note,
            'handled_by' => Auth::id(),
        ]);

        $serviceOrder->load(['project', 'creator', 'handler']);
        $serviceOrder->creator?->notify(new ServiceOrderReturnedNotification($serviceOrder));

        return response()->json(['order' => $this->formatServiceOrder($serviceOrder)]);
    }

    private function format(PurchaseOrder $po): array
    {
        // Collect distinct work orders from items
        $workOrders = $po->items
            ->map(fn ($i) => $i->workOrderItem?->workOrder)
            ->filter()
            ->unique('id');

        return [
            'id'             => $po->id,
            'status'         => $po->status,
            'notes'          => $po->notes,
            'supplier_name'  => $po->supplier_name,
            'supplier_email' => $po->supplier_email,
            'ordered_at'     => $po->ordered_at?->format('d.m.Y. H:i'),
            'delivered_at'   => $po->delivered_at?->format('d.m.Y. H:i'),
            'created_at'     => $po->created_at->format('d.m.Y. H:i'),
            'work_orders'  => $workOrders->values()->map(fn ($wo) => [
                'id'      => $wo->id,
                'name'    => $wo->order_label,
                'project' => [
                    'id'   => $wo->project?->id,
                    'name' => $wo->project?->name ?? 'Obrisan projekat',
                    'city' => $wo->project?->city?->name,
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

    private function formatServiceOrder(ServiceOrder $serviceOrder): array
    {
        return [
            'id' => $serviceOrder->id,
            'status' => $serviceOrder->status,
            'status_label' => match ($serviceOrder->status) {
                ServiceOrder::STATUS_PENDING_PROCUREMENT => 'Čeka slanje na servis',
                ServiceOrder::STATUS_SENT_TO_SUPPLIER => 'Kod dobavljača',
                ServiceOrder::STATUS_RETURNED => 'Vraćeno',
                default => $serviceOrder->status,
            },
            'project' => [
                'id' => $serviceOrder->project?->id,
                'name' => $serviceOrder->project?->name ?? 'Obrisan projekat',
                'city' => $serviceOrder->project?->city?->name,
            ],
            'item_name' => $serviceOrder->resource_name,
            'quantity_sent' => (float) $serviceOrder->quantity_sent,
            'item_unit' => $serviceOrder->resource_unit,
            'source_label' => $serviceOrder->source_label,
            'note' => $serviceOrder->note,
            'procurement_note' => $serviceOrder->procurement_note,
            'created_by' => $serviceOrder->creator?->name,
            'handled_by' => $serviceOrder->handler?->name,
            'supplier_name' => $serviceOrder->supplier_name,
            'supplier_email' => $serviceOrder->supplier_email,
            'sent_at' => $serviceOrder->sent_at?->format('d.m.Y. H:i'),
            'forwarded_at' => $serviceOrder->forwarded_at?->format('d.m.Y. H:i'),
            'returned_at' => $serviceOrder->returned_at?->format('d.m.Y. H:i'),
        ];
    }
}
