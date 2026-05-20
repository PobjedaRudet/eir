<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Operation;
use App\Models\Project;
use App\Models\ProjectService;
use App\Models\ResourcePlan;
use App\Models\ResourcePlanHistory;
use App\Models\ResourcePlanItem;
use App\Models\ServiceOrder;
use App\Models\Street;
use App\Models\WorkEntry;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VodjaApiController extends Controller
{
    // â”€â”€â”€ Projects â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function projects(): JsonResponse
    {
        $projects = Project::with(['city', 'streets', 'workEntries'])
            ->latest()
            ->get()
            ->map(function (Project $p) {
                $latestPlan = ResourcePlan::where('project_id', $p->id)
                    ->orderBy('version', 'desc')
                    ->first();

                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'date'          => $p->date->format('d.m.Y.'),
                    'city'          => $p->city->name,
                    'streets'       => $p->streets->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'entries_count' => $p->workEntries->count(),
                    'plan_status'   => $latestPlan?->status,
                    'plan_version'  => $latestPlan?->version,
                ];
            });

        return response()->json($projects);
    }

    // Resource Plans

    public function projectPlans(Project $project): JsonResponse
    {
        $plans = ResourcePlan::where('project_id', $project->id)
            ->with(['creator', 'teams.workers'])
            ->where('status', ResourcePlan::STATUS_APPROVED)
            ->orderBy('version', 'desc')
            ->get()
            ->map(fn ($plan) => [
                'id'          => $plan->id,
                'version'     => $plan->version,
                'status'      => $plan->status,
                'description' => $plan->notes,
                'created_at'  => $plan->created_at->format('d.m.Y. H:i'),
                'created_by'  => $plan->creator?->name,
                'teams'       => $plan->teams->map(fn ($t) => [
                    'id'      => $t->id,
                    'name'    => $t->name,
                    'workers' => $t->workers->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(),
                ])->values(),
            ]);

        return response()->json([
            'project'      => ['id' => $project->id, 'name' => $project->name],
            'plans'        => $plans,
            'active'       => $plans->first(),
            'orders_count' => WorkOrder::where('project_id', $project->id)->count(),
        ]);
    }

    public function catalog(): JsonResponse
    {
        return response()->json($this->buildCatalog());
    }

    public function createPlan(Request $request, Project $project): JsonResponse
    {
        $existing = ResourcePlan::where('project_id', $project->id)
            ->whereIn('status', [ResourcePlan::STATUS_DRAFT, ResourcePlan::STATUS_SUBMITTED])
            ->first();

        if ($existing) {
            $label = $existing->status === ResourcePlan::STATUS_DRAFT ? 'nacrt' : 'plan na ÄŤekanju odobrenja';
            return response()->json(['message' => "Postoji aktivan {$label}. Podnijeti ili obrisati ga."], 422);
        }

        $data    = $request->validate(['notes' => 'nullable|string|max:1000']);
        $version = (ResourcePlan::where('project_id', $project->id)->max('version') ?? 0) + 1;

        $plan = ResourcePlan::create([
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'version'    => $version,
            'status'     => ResourcePlan::STATUS_DRAFT,
            'notes'      => $data['notes'] ?? null,
        ]);

        ResourcePlanHistory::create([
            'plan_id'    => $plan->id,
            'user_id'    => Auth::id(),
            'action'     => 'created',
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Plan je kreiran.', 'id' => $plan->id], 201);
    }

    public function planDetail(ResourcePlan $plan): JsonResponse
    {
        return response()->json([
            'project' => ['id' => $plan->project->id, 'name' => $plan->project->name],
            'plan'    => [
                'id'           => $plan->id,
                'version'      => $plan->version,
                'status'       => $plan->status,
                'notes'        => $plan->notes,
                'submitted_at' => $plan->submitted_at?->format('d.m.Y. H:i'),
                'reviewed_at'  => $plan->reviewed_at?->format('d.m.Y. H:i'),
                'review_note'  => $plan->review_note,
                'reviewed_by'  => $plan->reviewer?->name,
            ],
            'items'   => $plan->items->map(fn ($item) => [
                'id'            => $item->id,
                'resource_type' => $item->resource_type,
                'resource_id'   => $item->resource_id,
                'resource_name' => $item->resource_name,
                'quantity'      => (float) $item->quantity,
                'unit'          => $item->unit,
                'start_date'    => $item->start_date?->format('Y-m-d'),
                'end_date'      => $item->end_date?->format('Y-m-d'),
                'notes'         => $item->notes,
            ])->values(),
            'catalog' => $this->buildCatalog(),
        ]);
    }

    private function buildCatalog(): array
    {
        return [
            'equipment' => Equipment::orderBy('category')->orderBy('name')->get()
                ->map(fn ($e) => [
                    'id'       => $e->id,
                    'name'     => $e->name,
                    'category' => Equipment::CATEGORIES[$e->category] ?? $e->category,
                    'unit'     => 'kom',
                ])->values(),
            'materials' => Material::orderBy('category')->orderBy('name')->get()
                ->map(fn ($m) => [
                    'id'       => $m->id,
                    'name'     => $m->name,
                    'category' => Material::CATEGORIES[$m->category] ?? $m->category,
                    'unit'     => $m->unit,
                ])->values(),
            'services' => ProjectService::orderBy('category')->orderBy('name')->get()
                ->map(fn ($s) => [
                    'id'       => $s->id,
                    'name'     => $s->name,
                    'category' => ProjectService::CATEGORIES[$s->category] ?? $s->category,
                    'unit'     => $s->unit,
                ])->values(),
        ];
    }

    public function addPlanItem(Request $request, ResourcePlan $plan): JsonResponse
    {
        if ($plan->status !== ResourcePlan::STATUS_DRAFT) {
            return response()->json(['message' => 'Plan nije u statusu nacrta.'], 422);
        }

        $data = $request->validate([
            'resource_type' => 'required|in:equipment,material,service',
            'resource_id'   => 'required|integer|min:1',
            'quantity'      => 'required|numeric|min:0.01|max:99999',
            'unit'          => 'nullable|string|max:20',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
            'notes'         => 'nullable|string|max:500',
        ]);

        $name = match ($data['resource_type']) {
            'equipment' => Equipment::find($data['resource_id'])?->name,
            'material'  => Material::find($data['resource_id'])?->name,
            'service'   => ProjectService::find($data['resource_id'])?->name,
            default     => null,
        };

        if (!$name) {
            return response()->json(['message' => 'Resurs nije pronaÄ‘en.'], 422);
        }

        if ($plan->items()->where('resource_type', $data['resource_type'])->where('resource_id', $data['resource_id'])->exists()) {
            return response()->json(['message' => 'Ovaj resurs je veÄ‡ u planu.'], 422);
        }

        $item = $plan->items()->create([
            'resource_type' => $data['resource_type'],
            'resource_id'   => $data['resource_id'],
            'resource_name' => $name,
            'quantity'      => $data['quantity'],
            'unit'          => $data['unit'] ?? null,
            'start_date'    => $data['start_date'] ?? null,
            'end_date'      => $data['end_date'] ?? null,
            'notes'         => $data['notes'] ?? null,
        ]);

        ResourcePlanHistory::create([
            'plan_id'    => $plan->id,
            'user_id'    => Auth::id(),
            'action'     => 'item_added',
            'data'       => ['name' => $name, 'qty' => $data['quantity'], 'type' => $data['resource_type']],
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Stavka je dodana.', 'id' => $item->id], 201);
    }

    public function updatePlanItem(Request $request, ResourcePlan $plan, ResourcePlanItem $item): JsonResponse
    {
        if ($plan->status !== ResourcePlan::STATUS_DRAFT || $item->plan_id !== $plan->id) {
            abort(403);
        }

        $data = $request->validate([
            'quantity'   => 'sometimes|numeric|min:0.01|max:99999',
            'unit'       => 'sometimes|nullable|string|max:20',
            'start_date' => 'sometimes|nullable|date',
            'end_date'   => 'sometimes|nullable|date',
            'notes'      => 'sometimes|nullable|string|max:500',
        ]);

        $item->update($data);

        return response()->json(['message' => 'Stavka je aĹľurirana.']);
    }

    public function removePlanItem(ResourcePlan $plan, ResourcePlanItem $item): JsonResponse
    {
        if ($plan->status !== ResourcePlan::STATUS_DRAFT || $item->plan_id !== $plan->id) {
            abort(403);
        }

        ResourcePlanHistory::create([
            'plan_id'    => $plan->id,
            'user_id'    => Auth::id(),
            'action'     => 'item_removed',
            'data'       => ['name' => $item->resource_name],
            'created_at' => now(),
        ]);

        $item->delete();

        return response()->json(['message' => 'Stavka je uklonjena.']);
    }

    public function submitPlan(Request $request, ResourcePlan $plan): JsonResponse
    {
        if ($plan->status !== ResourcePlan::STATUS_DRAFT) {
            return response()->json(['message' => 'Plan nije u statusu nacrta.'], 422);
        }
        if ($plan->items()->count() === 0) {
            return response()->json(['message' => 'Plan mora imati barem jednu stavku.'], 422);
        }

        $data = $request->validate(['notes' => 'nullable|string|max:1000']);

        $plan->update([
            'status'       => ResourcePlan::STATUS_SUBMITTED,
            'notes'        => $data['notes'] ?? $plan->notes,
            'submitted_at' => now(),
        ]);

        ResourcePlanHistory::create([
            'plan_id'    => $plan->id,
            'user_id'    => Auth::id(),
            'action'     => 'submitted',
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Plan je podnesen na odobrenje.']);
    }

    public function discardPlan(ResourcePlan $plan): JsonResponse
    {
        if ($plan->status !== ResourcePlan::STATUS_DRAFT) {
            return response()->json(['message' => 'MoĹľe se obrisati samo nacrt.'], 422);
        }

        $plan->delete();

        return response()->json(['message' => 'Nacrt plana je obrisan.']);
    }

    // â”€â”€â”€ Report (unchanged) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function report(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id');
        $dateFrom  = $request->query('date_from');
        $dateTo    = $request->query('date_to');

        $query = WorkEntry::with([
            'worker',
            'project.city',
            'street',
            'enclosure',
            'operations.images',
        ]);

        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $entries = $query->orderBy('date', 'desc')->orderBy('created_at', 'desc')->get();

        $grouped = $entries
            ->groupBy(fn ($e) => $e->date->format('Y-m-d'))
            ->map(fn ($dayEntries, $dateKey) => [
                'date'    => $dateKey,
                'entries' => $dayEntries->map(fn (WorkEntry $entry) => [
                    'id'         => $entry->id,
                    'worker'     => $entry->worker->name,
                    'project'    => $entry->project->name,
                    'city'       => $entry->project->city->name,
                    'street'     => $entry->street?->name,
                    'enclosure'  => $entry->enclosure?->name,
                    'cable_type' => $entry->cable_type,
                    'work_types' => $entry->work_types,
                    'operations' => $entry->operations->map(fn (Operation $op) => [
                        'id'              => $op->id,
                        'kind'            => $op->kind,
                        'streets'         => Street::whereIn('id', $op->street_ids ?? [])->orderBy('name')->pluck('name')->values(),
                        'excavation_type' => $op->excavation_type,
                        'dimensions'      => $op->dimensions,
                        'meterage'        => $op->meterage,
                        'address'         => $op->address,
                        'splajsovano'     => $op->splajsovano,
                        'aktivirano'      => $op->aktivirano,
                        'sub_operations'  => $op->sub_operations ?? [],
                        'images'          => $op->images->map(fn ($img) => [
                            'url'  => asset('storage/' . $img->path),
                            'name' => $img->original_name,
                        ]),
                    ]),
                ]),
            ])
            ->values();

        $projects = Project::with('city')->latest()->get()
            ->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name, 'city' => $p->city->name]);

        return response()->json([
            'days'             => $grouped,
            'projects'         => $projects,
            'excavation_types' => Operation::EXCAVATION_TYPES,
            'work_types'       => WorkEntry::WORK_TYPES,
        ]);
    }

    // ─── Work Orders ──────────────────────────────────────────────────────────

    public function projectOrders(Project $project): JsonResponse
    {
        $orders = WorkOrder::where('project_id', $project->id)
            ->with(['creator', 'reviewer', 'items.serviceOrders'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($o) => [
                'id'          => $o->id,
                'name'        => $o->order_label,
                'description' => $o->description,
                'date'        => $o->date->format('d.m.Y.'),
                'created_by'  => $o->creator?->name,
                'status'      => $o->status,
                'review_note' => $o->review_note,
                'reviewed_by' => $o->reviewer?->name,
                'reviewed_at' => $o->reviewed_at?->format('d.m.Y. H:i'),
                'items'       => $o->items->map(fn ($i) => [
                    'id'              => $i->id,
                    'resource_type'   => $i->resource_type,
                    'resource_name'   => $i->resource_name,
                    'quantity'        => $i->quantity,
                    'unit'            => $i->unit,
                    'notes'           => $i->notes,
                    'service_qty_sent' => $i->serviceOrders->where('status', 'sent')->sum('quantity_sent'),
                    'service_status'  => $i->serviceOrders->where('status', 'sent')->isNotEmpty() ? 'sent'
                                        : ($i->serviceOrders->isNotEmpty() ? 'returned' : null),
                    'service_order_id' => $i->serviceOrders->where('status', 'sent')->first()?->id,
                ]),
            ]);

        return response()->json(['orders' => $orders]);
    }

    public function createOrder(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'date'        => 'required|date',
            'description' => 'nullable|string',
            'plan_id'     => 'required|exists:resource_plans,id',
        ]);

        $year       = now()->year;
        $nextNumber = (WorkOrder::where('order_year', $year)->max('order_number') ?? 0) + 1;
        $label      = $nextNumber . '/' . substr((string) $year, -2);

        $order = WorkOrder::create([
            ...$validated,
            'name'         => $label,
            'order_number' => $nextNumber,
            'order_year'   => $year,
            'project_id'   => $project->id,
            'created_by'   => Auth::id(),
            'status'       => WorkOrder::STATUS_DRAFT,
        ]);

        return response()->json([
            'id'          => $order->id,
            'name'        => $order->order_label,
            'description' => $order->description,
            'date'        => $order->date->format('d.m.Y.'),
            'created_by'  => Auth::user()->name,
            'status'      => $order->status,
            'review_note' => null,
            'reviewed_by' => null,
            'items'       => [],
        ], 201);
    }

    public function submitOrder(WorkOrder $order): JsonResponse
    {
        if ($order->status !== WorkOrder::STATUS_DRAFT) {
            return response()->json(['message' => 'Nalog nije u statusu nacrta.'], 422);
        }

        $order->update(['status' => WorkOrder::STATUS_SUBMITTED]);

        return response()->json(['message' => 'Nalog je podnesen na odobrenje.']);
    }

    public function deleteOrder(WorkOrder $order): JsonResponse
    {
        if (! in_array($order->status, [WorkOrder::STATUS_DRAFT, WorkOrder::STATUS_REJECTED])) {
            return response()->json(['message' => 'Nije moguće obrisati nalog koji je podnesen ili odobren.'], 422);
        }
        $order->delete();
        return response()->json(['ok' => true]);
    }

    public function addOrderItem(Request $request, WorkOrder $order): JsonResponse
    {
        $validated = $request->validate([
            'resource_type' => 'required|in:equipment,material,service',
            'resource_id'   => 'required|integer',
            'quantity'      => 'required|numeric|min:0.01',
            'unit'          => 'nullable|string|max:20',
            'notes'         => 'nullable|string',
        ]);

        $name = match ($validated['resource_type']) {
            'equipment' => Equipment::find($validated['resource_id'])?->name,
            'material'  => Material::find($validated['resource_id'])?->name,
            'service'   => ProjectService::find($validated['resource_id'])?->name,
        } ?? 'Nepoznat resurs';

        $item = WorkOrderItem::create([
            ...$validated,
            'work_order_id' => $order->id,
            'resource_name' => $name,
        ]);

        return response()->json([
            'id'            => $item->id,
            'resource_type' => $item->resource_type,
            'resource_name' => $item->resource_name,
            'quantity'      => $item->quantity,
            'unit'          => $item->unit,
            'notes'         => $item->notes,
        ], 201);
    }

    public function removeOrderItem(WorkOrder $order, WorkOrderItem $orderItem): JsonResponse
    {
        $orderItem->delete();
        return response()->json(['ok' => true]);
    }

    // ─── Service Orders ──────────────────────────────────────────────────────

    public function projectServiceOrders(Project $project): JsonResponse
    {
        $orders = ServiceOrder::with(['workOrderItem.workOrder', 'creator'])
            ->where('project_id', $project->id)
            ->latest()
            ->get()
            ->map(fn (ServiceOrder $s) => $this->formatServiceOrder($s));

        return response()->json($orders);
    }

    public function allServiceOrders(): JsonResponse
    {
        $orders = ServiceOrder::with(['workOrderItem.workOrder', 'creator', 'project'])
            ->latest()
            ->get()
            ->map(function (ServiceOrder $s) {
                return array_merge($this->formatServiceOrder($s), [
                    'project_id'   => $s->project_id,
                    'project_name' => $s->project?->name,
                ]);
            });

        return response()->json($orders);
    }

    public function createServiceOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_order_item_id' => 'required|integer|exists:work_order_items,id',
            'quantity_sent'      => 'required|numeric|min:0.01',
            'note'               => 'nullable|string|max:1000',
        ]);

        $item = WorkOrderItem::with('workOrder')->findOrFail($validated['work_order_item_id']);

        // Only equipment can go to service
        if ($item->resource_type !== 'equipment') {
            return response()->json(['message' => 'Na servis se može poslati samo oprema.'], 422);
        }

        // Cannot send more than available (item quantity minus already-sent)
        $qtySent = ServiceOrder::where('work_order_item_id', $item->id)
            ->where('status', ServiceOrder::STATUS_SENT)
            ->sum('quantity_sent');

        if ($qtySent + $validated['quantity_sent'] > $item->quantity) {
            return response()->json(['message' => 'Tražena količina prelazi dostupnu količinu.'], 422);
        }

        $so = ServiceOrder::create([
            'project_id'         => $item->workOrder->project_id,
            'work_order_item_id' => $item->id,
            'quantity_sent'      => $validated['quantity_sent'],
            'status'             => ServiceOrder::STATUS_SENT,
            'note'               => $validated['note'] ?? null,
            'sent_at'            => now(),
            'created_by'         => Auth::id(),
        ]);

        $so->load(['workOrderItem.workOrder', 'creator']);

        return response()->json($this->formatServiceOrder($so), 201);
    }

    public function returnServiceOrder(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ($serviceOrder->status !== ServiceOrder::STATUS_SENT) {
            return response()->json(['message' => 'Servisni nalog nije u statusu "Na servisu".'], 422);
        }

        $serviceOrder->update([
            'status'      => ServiceOrder::STATUS_RETURNED,
            'returned_at' => now(),
            'note'        => $request->input('note', $serviceOrder->note),
        ]);

        return response()->json($this->formatServiceOrder($serviceOrder->fresh(['workOrderItem.workOrder', 'creator'])));
    }

    private function formatServiceOrder(ServiceOrder $s): array
    {
        return [
            'id'                 => $s->id,
            'status'             => $s->status,
            'note'               => $s->note,
            'quantity_sent'      => $s->quantity_sent,
            'sent_at'            => $s->sent_at?->format('d.m.Y.'),
            'returned_at'        => $s->returned_at?->format('d.m.Y.'),
            'created_by'         => $s->creator?->name,
            'item_id'            => $s->workOrderItem?->id,
            'item_name'          => $s->workOrderItem?->resource_name,
            'item_quantity'      => $s->workOrderItem?->quantity,
            'item_unit'          => $s->workOrderItem?->unit,
            'work_order_label'   => $s->workOrderItem?->workOrder?->order_label,
            'work_order_id'      => $s->workOrderItem?->workOrder?->id,
        ];
    }
}
