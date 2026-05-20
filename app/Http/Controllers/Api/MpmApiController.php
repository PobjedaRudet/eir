<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectService;
use App\Models\ResourcePlan;
use App\Models\ResourcePlanHistory;
use App\Models\Street;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MpmApiController extends Controller
{
    public function projects(): JsonResponse
    {
        $projects = Project::with(['city', 'streets', 'workEntries', 'workers'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function (Project $p) {
                $latestPlan = ResourcePlan::where('project_id', $p->id)
                    ->with('reviewer')
                    ->orderBy('version', 'desc')
                    ->first();

                return [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'date'             => $p->date->format('d.m.Y.'),
                    'city'             => $p->city->name,
                    'streets'          => $p->streets->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'entries_count'    => $p->workEntries->count(),
                    'workers_count'    => $p->workers->count(),
                    'plan_status'      => $latestPlan?->status,
                    'plan_version'     => $latestPlan?->version,
                    'plan_reviewed_at' => $latestPlan?->reviewed_at?->format('d.m.Y. H:i'),
                    'plan_reviewed_by' => $latestPlan?->reviewer?->name,
                    'plan_review_note' => $latestPlan?->review_note,
                ];
            });

        return response()->json($projects);
    }

    public function projectFormConfig(): JsonResponse
    {
        $cities = City::query()
            ->selectRaw('MIN(id) as id, name')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        return response()->json(['cities' => $cities]);
    }

    public function streetsByCity(int $cityId): JsonResponse
    {
        $streets = Street::query()
            ->where('city_id', $cityId)
            ->selectRaw('MIN(id) as id, name')
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        return response()->json($streets);
    }

    public function storeProject(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'date'         => 'required|date',
            'city_id'      => 'required|exists:cities,id',
            'street_ids'   => 'required|array|min:1',
            'street_ids.*' => 'exists:streets,id',
        ]);

        $project = Project::create([
            'name'    => $data['name'],
            'date'    => $data['date'],
            'city_id' => $data['city_id'],
            'user_id' => Auth::id(),
        ]);

        $project->streets()->attach($data['street_ids']);

        return response()->json(['message' => 'Projekat je uspješno kreiran.', 'id' => $project->id], 201);
    }

    public function projectWorkers(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $assigned = $project->workers()->orderBy('name')->get(['id', 'name', 'email']);
        $available = User::where('role', 'radnik')->orderBy('name')->get(['id', 'name', 'email']);

        return response()->json([
            'project'   => ['id' => $project->id, 'name' => $project->name],
            'assigned'  => $assigned,
            'available' => $available,
        ]);
    }

    public function syncProjectWorkers(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'user_ids'   => 'present|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Ensure only radnik users are synced
        $validIds = User::whereIn('id', $data['user_ids'])
            ->where('role', 'radnik')
            ->pluck('id')
            ->all();

        $project->workers()->sync($validIds);

        return response()->json(['message' => 'Lista radnika je ažurirana.']);
    }

    public function equipmentList(): JsonResponse
    {
        $items = Equipment::orderBy('category')->orderBy('name')->get()
            ->map(fn ($e) => [
                'id'             => $e->id,
                'name'           => $e->name,
                'category'       => $e->category,
                'category_label' => Equipment::CATEGORIES[$e->category] ?? $e->category,
                'description'    => $e->description,
            ]);

        return response()->json([
            'items'      => $items,
            'categories' => collect(Equipment::CATEGORIES)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
        ]);
    }

    public function storeEquipment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(Equipment::CATEGORIES)),
            'description' => 'nullable|string|max:255',
        ]);

        $equipment = Equipment::create($data);

        return response()->json(['message' => 'Resurs je kreiran.', 'id' => $equipment->id], 201);
    }

    public function updateEquipment(Request $request, Equipment $equipment): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(Equipment::CATEGORIES)),
            'description' => 'nullable|string|max:255',
        ]);

        $equipment->update($data);

        return response()->json(['message' => 'Resurs je ažuriran.']);
    }

    public function destroyEquipment(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json(['message' => 'Resurs je obrisan.']);
    }

    // ─── Resource Plan Approvals ──────────────────────────────────────────────

    public function pendingPlans(): JsonResponse
    {
        $plans = ResourcePlan::where('status', ResourcePlan::STATUS_SUBMITTED)
            ->with(['project.city', 'creator', 'items'])
            ->orderBy('submitted_at')
            ->get()
            ->map(fn ($plan) => [
                'id'           => $plan->id,
                'version'      => $plan->version,
                'status'       => $plan->status,
                'notes'        => $plan->notes,
                'submitted_at' => $plan->submitted_at?->format('d.m.Y. H:i'),
                'created_by'   => $plan->creator->name,
                'project'      => [
                    'id'   => $plan->project->id,
                    'name' => $plan->project->name,
                    'city' => $plan->project->city->name,
                ],
                'items' => $plan->items->map(fn ($item) => [
                    'id'            => $item->id,
                    'resource_type' => $item->resource_type,
                    'resource_name' => $item->resource_name,
                    'quantity'      => (float) $item->quantity,
                    'unit'          => $item->unit,
                    'start_date'    => $item->start_date?->format('d.m.Y.'),
                    'end_date'      => $item->end_date?->format('d.m.Y.'),
                    'notes'         => $item->notes,
                ])->values(),
                'items_count' => $plan->items->count(),
            ]);

        return response()->json(['plans' => $plans, 'count' => $plans->count()]);
    }

    public function approvePlan(Request $request, ResourcePlan $plan): JsonResponse
    {
        if ($plan->status !== ResourcePlan::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Plan nije podnesen na odobrenje.'], 422);
        }

        $data = $request->validate(['note' => 'nullable|string|max:500']);

        $plan->update([
            'status'      => ResourcePlan::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $data['note'] ?? null,
        ]);

        ResourcePlanHistory::create([
            'plan_id'    => $plan->id,
            'user_id'    => Auth::id(),
            'action'     => 'approved',
            'data'       => isset($data['note']) ? ['note' => $data['note']] : null,
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Plan je odobren.']);
    }

    public function rejectPlan(Request $request, ResourcePlan $plan): JsonResponse
    {
        if ($plan->status !== ResourcePlan::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Plan nije podnesen na odobrenje.'], 422);
        }

        $data = $request->validate(['note' => 'required|string|max:500']);

        $plan->update([
            'status'      => ResourcePlan::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $data['note'],
        ]);

        ResourcePlanHistory::create([
            'plan_id'    => $plan->id,
            'user_id'    => Auth::id(),
            'action'     => 'rejected',
            'data'       => ['note' => $data['note']],
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Plan je odbijen.']);
    }

    // ─── Materials CRUD ───────────────────────────────────────────────────────

    public function materialList(): JsonResponse
    {
        $items = Material::orderBy('category')->orderBy('name')->get()
            ->map(fn ($m) => [
                'id'             => $m->id,
                'name'           => $m->name,
                'category'       => $m->category,
                'category_label' => Material::CATEGORIES[$m->category] ?? $m->category,
                'unit'           => $m->unit,
                'description'    => $m->description,
            ])->values();

        return response()->json([
            'items'      => $items,
            'categories' => collect(Material::CATEGORIES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'units'      => Material::UNITS,
        ]);
    }

    public function storeMaterial(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(Material::CATEGORIES)),
            'unit'        => 'required|in:' . implode(',', Material::UNITS),
            'description' => 'nullable|string|max:255',
        ]);

        $material = Material::create($data);

        return response()->json(['message' => 'Materijal je kreiran.', 'id' => $material->id], 201);
    }

    public function updateMaterial(Request $request, Material $material): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(Material::CATEGORIES)),
            'unit'        => 'required|in:' . implode(',', Material::UNITS),
            'description' => 'nullable|string|max:255',
        ]);

        $material->update($data);

        return response()->json(['message' => 'Materijal je ažuriran.']);
    }

    public function destroyMaterial(Material $material): JsonResponse
    {
        $material->delete();

        return response()->json(['message' => 'Materijal je obrisan.']);
    }

    // ─── Services CRUD ────────────────────────────────────────────────────────

    public function serviceList(): JsonResponse
    {
        $items = ProjectService::orderBy('category')->orderBy('name')->get()
            ->map(fn ($s) => [
                'id'             => $s->id,
                'name'           => $s->name,
                'category'       => $s->category,
                'category_label' => ProjectService::CATEGORIES[$s->category] ?? $s->category,
                'unit'           => $s->unit,
                'description'    => $s->description,
            ])->values();

        return response()->json([
            'items'      => $items,
            'categories' => collect(ProjectService::CATEGORIES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'units'      => ProjectService::UNITS,
        ]);
    }

    public function storeService(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(ProjectService::CATEGORIES)),
            'unit'        => 'required|in:' . implode(',', ProjectService::UNITS),
            'description' => 'nullable|string|max:255',
        ]);

        $service = ProjectService::create($data);

        return response()->json(['message' => 'Servis je kreiran.', 'id' => $service->id], 201);
    }

    public function updateService(Request $request, ProjectService $projectService): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(ProjectService::CATEGORIES)),
            'unit'        => 'required|in:' . implode(',', ProjectService::UNITS),
            'description' => 'nullable|string|max:255',
        ]);

        $projectService->update($data);

        return response()->json(['message' => 'Servis je ažuriran.']);
    }

    public function destroyService(ProjectService $projectService): JsonResponse
    {
        $projectService->delete();

        return response()->json(['message' => 'Servis je obrisan.']);
    }

    private function authorizeProject(Project $project): void
    {
        if ($project->user_id !== Auth::id()) {
            abort(403, 'Nemate pristup ovom projektu.');
        }
    }

    // ─── MPM Plan Creation ────────────────────────────────────────────────────

    public function projectPlan(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $plan = ResourcePlan::where('project_id', $project->id)
            ->where('status', ResourcePlan::STATUS_APPROVED)
            ->with(['teams.workers'])
            ->orderBy('version', 'desc')
            ->first();

        $projectWorkers = $project->workers()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values();

        if (! $plan) {
            return response()->json([
                'project'         => ['id' => $project->id, 'name' => $project->name],
                'plan'            => null,
                'project_workers' => $projectWorkers,
            ]);
        }

        return response()->json([
            'project' => ['id' => $project->id, 'name' => $project->name],
            'plan'    => $this->formatPlan($plan),
            'project_workers' => $projectWorkers,
        ]);
    }

    public function createProjectPlan(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'description'    => 'nullable|string|max:1000',
            'teams'          => 'present|array|min:1',
            'teams.*.name'   => 'required|string|max:200',
            'teams.*.worker_ids'   => 'present|array',
            'teams.*.worker_ids.*' => 'exists:users,id',
        ]);

        $version = (ResourcePlan::where('project_id', $project->id)->max('version') ?? 0) + 1;

        $plan = ResourcePlan::create([
            'project_id'  => $project->id,
            'created_by'  => Auth::id(),
            'version'     => $version,
            'status'      => ResourcePlan::STATUS_APPROVED,
            'notes'       => $data['description'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        foreach ($data['teams'] as $teamData) {
            $team = $plan->teams()->create(['name' => $teamData['name']]);
            if (! empty($teamData['worker_ids'])) {
                $team->workers()->attach($teamData['worker_ids']);
            }
        }

        $plan->load('teams.workers');

        return response()->json([
            'message' => 'Radni plan je kreiran.',
            'plan'    => $this->formatPlan($plan),
        ], 201);
    }

    public function syncPlanTeams(Request $request, ResourcePlan $plan): JsonResponse
    {
        $this->authorizeProject($plan->project);

        $data = $request->validate([
            'teams'                => 'present|array|min:1',
            'teams.*.name'         => 'required|string|max:200',
            'teams.*.worker_ids'   => 'present|array',
            'teams.*.worker_ids.*' => 'exists:users,id',
        ]);

        // Delete all existing teams (cascade deletes team_workers)
        $plan->teams()->delete();

        foreach ($data['teams'] as $teamData) {
            $team = $plan->teams()->create(['name' => $teamData['name']]);
            if (! empty($teamData['worker_ids'])) {
                $team->workers()->attach($teamData['worker_ids']);
            }
        }

        $plan->load('teams.workers');

        return response()->json([
            'teams' => $this->formatTeams($plan->teams),
        ]);
    }

    private function formatPlan(ResourcePlan $plan): array
    {
        return [
            'id'          => $plan->id,
            'version'     => $plan->version,
            'description' => $plan->notes,
            'created_at'  => $plan->created_at->format('d.m.Y. H:i'),
            'teams'       => $this->formatTeams($plan->teams),
        ];
    }

    private function formatTeams($teams): array
    {
        return $teams->map(fn ($t) => [
            'id'      => $t->id,
            'name'    => $t->name,
            'workers' => $t->workers->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(),
        ])->values()->all();
    }

    // ─── Work Order Approvals ─────────────────────────────────────────────────

    public function pendingOrders(): JsonResponse
    {
        $orders = \App\Models\WorkOrder::where('status', \App\Models\WorkOrder::STATUS_SUBMITTED)
            ->with(['project.city', 'creator', 'plan', 'items'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($o) => [
                'id'          => $o->id,
                'name'        => $o->order_label,
                'description' => $o->description,
                'date'        => $o->date->format('d.m.Y.'),
                'created_by'  => $o->creator?->name,
                'plan_version'=> $o->plan?->version,
                'project'     => [
                    'id'   => $o->project->id,
                    'name' => $o->project->name,
                    'city' => $o->project->city?->name,
                ],
                'items_count' => $o->items->count(),
                'items'       => $o->items->map(fn ($i) => [
                    'id'            => $i->id,
                    'resource_type' => $i->resource_type,
                    'resource_name' => $i->resource_name,
                    'quantity'      => (float) $i->quantity,
                    'unit'          => $i->unit,
                ]),
            ]);

        return response()->json(['orders' => $orders, 'count' => $orders->count()]);
    }

    public function approveOrder(\App\Models\WorkOrder $order): JsonResponse
    {
        if ($order->status !== \App\Models\WorkOrder::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Nalog nije podnesen na odobrenje.'], 422);
        }

        $order->update([
            'status'      => \App\Models\WorkOrder::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        return response()->json(['message' => 'Nalog je odobren.']);
    }

    public function rejectOrder(Request $request, \App\Models\WorkOrder $order): JsonResponse
    {
        if ($order->status !== \App\Models\WorkOrder::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Nalog nije podnesen na odobrenje.'], 422);
        }

        $data = $request->validate(['note' => 'required|string|max:500']);

        $order->update([
            'status'      => \App\Models\WorkOrder::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $data['note'],
        ]);

        return response()->json(['message' => 'Nalog je odbijen.']);
    }
}

