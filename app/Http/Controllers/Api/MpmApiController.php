<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Ntv;
use App\Models\Project;
use App\Models\ProjectService;
use App\Models\ResourcePlan;
use App\Models\ResourcePlanHistory;
use App\Models\Street;
use App\Models\User;
use App\Models\Operation;
use App\Models\WorkDayComment;
use App\Models\WorkEntry;
use App\Models\WorkOrder;
use App\Notifications\OrderApprovedNotification;
use App\Notifications\OrderRejectedNotification;
use App\Notifications\ProjectApprovedNotification;
use App\Notifications\WorkOrderReadyForProcurementNotification;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class MpmApiController extends Controller
{
    public function projects(Request $request): JsonResponse
    {
        $status = $request->query('status', Project::STATUS_AKTIVAN);

        $projects = Project::with(['city', 'streets', 'workEntries', 'workers', 'leader'])
            ->whereIn('status', [Project::STATUS_AKTIVAN, Project::STATUS_ZAKLJUCEN])
            ->where('status', $status)
            ->latest()
            ->get()
            ->map(function (Project $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'date' => $p->date->format('d.m.Y.'),
                    'city' => $p->city->name,
                    'status' => $p->status,
                    'leader' => $p->leader?->name,
                    'streets' => $p->streets->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'entries_count' => $p->workEntries->count(),
                    'workers_count' => $p->workers->count(),
                ];
            });

        return response()->json($projects);
    }

    public function pendingProjects(): JsonResponse
    {
        $projects = Project::with(['city', 'streets', 'leader', 'teams', 'projectNtvs.ntv', 'projectNtvs.streets', 'projectNtvs.team'])
            ->where('status', Project::STATUS_NA_CEKANJU)
            ->latest()
            ->get()
            ->map(fn (Project $p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'date'       => $p->date->format('d.m.Y.'),
                'city'       => $p->city->name,
                'leader'     => $p->leader?->name,
                'cable_type' => $p->cable_type,
                'streets'    => $p->streets->map(fn ($s) => $s->name)->values(),
                'teams'      => $p->teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
                'ntvs'       => $p->projectNtvs->map(fn ($pn) => [
                    'ntv_name'    => $pn->ntv->name,
                    'team_name'   => $pn->team?->name,
                    'streets'     => $pn->streets->pluck('name')->values(),
                ])->values(),
            ]);

        return response()->json(['projects' => $projects, 'count' => $projects->count()]);
    }

    public function approveProject(Project $project): JsonResponse
    {
        if ($project->status !== Project::STATUS_NA_CEKANJU) {
            return response()->json(['message' => 'Projekat nije na čekanju odobrenja.'], 422);
        }

        $project->update([
            'status'         => Project::STATUS_AKTIVAN,
            'rejection_note' => null,
        ]);

        $project->loadMissing('city', 'leader');
        $project->leader?->notify(new ProjectApprovedNotification($project, Auth::user()?->name ?? 'PM'));

        return response()->json(['message' => 'Projekat je odobren i aktivan.']);
    }

    public function rejectProject(Request $request, Project $project): JsonResponse
    {
        if ($project->status !== Project::STATUS_NA_CEKANJU) {
            return response()->json(['message' => 'Projekat nije na čekanju odobrenja.'], 422);
        }

        $data = $request->validate(['note' => 'required|string|max:500']);

        $project->update([
            'status'         => Project::STATUS_ODBIJEN,
            'rejection_note' => $data['note'],
        ]);

        return response()->json(['message' => 'Projekat je odbijen.']);
    }

    // ─── NTV Catalog ─────────────────────────────────────────────────────────

    public function ntvCatalog(): JsonResponse
    {
        return response()->json(['ntvs' => Ntv::orderBy('name')->get(['id', 'name'])]);
    }

    public function storeNtv(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:200|unique:ntvs,name']);
        $ntv  = Ntv::create($data);
        return response()->json(['ntv' => $ntv], 201);
    }

    public function destroyNtv(Ntv $ntv): JsonResponse
    {
        if ($ntv->projectNtvs()->exists()) {
            return response()->json(['message' => 'NTV je dodijeljen projektu i ne može se obrisati.'], 422);
        }
        $ntv->delete();
        return response()->json(['message' => 'NTV je obrisan.']);
    }

    public function toggleProjectStatus(Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $project->status = $project->status === Project::STATUS_AKTIVAN
            ? Project::STATUS_ZAKLJUCEN
            : Project::STATUS_AKTIVAN;

        $project->save();

        return response()->json(['status' => $project->status]);
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
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'city_id' => 'required|exists:cities,id',
            'street_ids' => 'required|array|min:1',
            'street_ids.*' => 'exists:streets,id',
        ]);

        $project = Project::create([
            'name' => $data['name'],
            'date' => $data['date'],
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
            'project' => ['id' => $project->id, 'name' => $project->name],
            'assigned' => $assigned,
            'available' => $available,
        ]);
    }

    public function syncProjectWorkers(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'user_ids' => 'present|array',
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
                'id' => $e->id,
                'name' => $e->name,
                'category' => $e->category,
                'category_label' => Equipment::CATEGORIES[$e->category] ?? $e->category,
                'description' => $e->description,
            ]);

        return response()->json([
            'items' => $items,
            'categories' => collect(Equipment::CATEGORIES)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values(),
        ]);
    }

    public function storeEquipment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:'.implode(',', array_keys(Equipment::CATEGORIES)),
            'description' => 'nullable|string|max:255',
        ]);

        $equipment = Equipment::create($data);

        return response()->json(['message' => 'Resurs je kreiran.', 'id' => $equipment->id], 201);
    }

    public function updateEquipment(Request $request, Equipment $equipment): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:'.implode(',', array_keys(Equipment::CATEGORIES)),
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
                'id' => $plan->id,
                'version' => $plan->version,
                'status' => $plan->status,
                'notes' => $plan->notes,
                'submitted_at' => $plan->submitted_at?->format('d.m.Y. H:i'),
                'created_by' => $plan->creator->name,
                'project' => [
                    'id' => $plan->project->id,
                    'name' => $plan->project->name,
                    'city' => $plan->project->city->name,
                ],
                'items' => $plan->items->map(fn ($item) => [
                    'id' => $item->id,
                    'resource_type' => $item->resource_type,
                    'resource_name' => $item->resource_name,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'start_date' => $item->start_date?->format('d.m.Y.'),
                    'end_date' => $item->end_date?->format('d.m.Y.'),
                    'notes' => $item->notes,
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
            'status' => ResourcePlan::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $data['note'] ?? null,
        ]);

        ResourcePlanHistory::create([
            'plan_id' => $plan->id,
            'user_id' => Auth::id(),
            'action' => 'approved',
            'data' => isset($data['note']) ? ['note' => $data['note']] : null,
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
            'status' => ResourcePlan::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $data['note'],
        ]);

        ResourcePlanHistory::create([
            'plan_id' => $plan->id,
            'user_id' => Auth::id(),
            'action' => 'rejected',
            'data' => ['note' => $data['note']],
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Plan je odbijen.']);
    }

    // ─── Materials CRUD ───────────────────────────────────────────────────────

    public function materialList(): JsonResponse
    {
        $items = Material::orderBy('category')->orderBy('name')->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'category' => $m->category,
                'category_label' => Material::CATEGORIES[$m->category] ?? $m->category,
                'unit' => $m->unit,
                'description' => $m->description,
            ])->values();

        return response()->json([
            'items' => $items,
            'categories' => collect(Material::CATEGORIES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'units' => Material::UNITS,
        ]);
    }

    public function storeMaterial(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:'.implode(',', array_keys(Material::CATEGORIES)),
            'unit' => 'required|in:'.implode(',', Material::UNITS),
            'description' => 'nullable|string|max:255',
        ]);

        $material = Material::create($data);

        return response()->json(['message' => 'Materijal je kreiran.', 'id' => $material->id], 201);
    }

    public function updateMaterial(Request $request, Material $material): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:'.implode(',', array_keys(Material::CATEGORIES)),
            'unit' => 'required|in:'.implode(',', Material::UNITS),
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
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category,
                'category_label' => ProjectService::CATEGORIES[$s->category] ?? $s->category,
                'unit' => $s->unit,
                'description' => $s->description,
            ])->values();

        return response()->json([
            'items' => $items,
            'categories' => collect(ProjectService::CATEGORIES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'units' => ProjectService::UNITS,
        ]);
    }

    public function storeService(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:'.implode(',', array_keys(ProjectService::CATEGORIES)),
            'unit' => 'required|in:'.implode(',', ProjectService::UNITS),
            'description' => 'nullable|string|max:255',
        ]);

        $service = ProjectService::create($data);

        return response()->json(['message' => 'Servis je kreiran.', 'id' => $service->id], 201);
    }

    public function updateService(Request $request, ProjectService $projectService): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|in:'.implode(',', array_keys(ProjectService::CATEGORIES)),
            'unit' => 'required|in:'.implode(',', ProjectService::UNITS),
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
                'project' => ['id' => $project->id, 'name' => $project->name],
                'plan' => null,
                'project_workers' => $projectWorkers,
            ]);
        }

        return response()->json([
            'project' => ['id' => $project->id, 'name' => $project->name],
            'plan' => $this->formatPlan($plan),
            'project_workers' => $projectWorkers,
        ]);
    }

    public function createProjectPlan(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'description' => 'nullable|string|max:1000',
            'teams' => 'present|array|min:1',
            'teams.*.name' => 'required|string|max:200',
            'teams.*.worker_ids' => 'present|array',
            'teams.*.worker_ids.*' => 'exists:users,id',
        ]);

        $version = (ResourcePlan::where('project_id', $project->id)->max('version') ?? 0) + 1;

        $plan = ResourcePlan::create([
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'version' => $version,
            'status' => ResourcePlan::STATUS_APPROVED,
            'notes' => $data['description'] ?? null,
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
            'plan' => $this->formatPlan($plan),
        ], 201);
    }

    public function syncPlanTeams(Request $request, ResourcePlan $plan): JsonResponse
    {
        $this->authorizeProject($plan->project);

        $data = $request->validate([
            'teams' => 'present|array|min:1',
            'teams.*.name' => 'required|string|max:200',
            'teams.*.worker_ids' => 'present|array',
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
            'id' => $plan->id,
            'version' => $plan->version,
            'description' => $plan->notes,
            'created_at' => $plan->created_at->format('d.m.Y. H:i'),
            'teams' => $this->formatTeams($plan->teams),
        ];
    }

    private function formatTeams($teams): array
    {
        return $teams->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'workers' => $t->workers->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(),
        ])->values()->all();
    }

    // ─── Work Order Approvals ─────────────────────────────────────────────────

    public function pendingOrders(): JsonResponse
    {
        $orders = WorkOrder::where('status', WorkOrder::STATUS_SUBMITTED)
            ->with(['project.city', 'creator', 'items'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'name' => $o->order_label,
                'description' => $o->description,
                'date' => $o->date->format('d.m.Y.'),
                'created_by' => $o->creator?->name,
                'project' => [
                    'id' => $o->project->id,
                    'name' => $o->project->name,
                    'city' => $o->project->city?->name,
                ],
                'items_count' => $o->items->count(),
                'items' => $o->items->map(fn ($i) => [
                    'id' => $i->id,
                    'resource_type' => $i->resource_type,
                    'resource_name' => $i->resource_name,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                ]),
            ]);

        return response()->json(['orders' => $orders, 'count' => $orders->count()]);
    }

    public function approveOrder(WorkOrder $order): JsonResponse
    {
        if ($order->status !== WorkOrder::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Nalog nije podnesen na odobrenje.'], 422);
        }

        $order->update([
            'status' => WorkOrder::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => null,
        ]);

        // Notify vodja (order creator)
        $order->load(['creator', 'project', 'reviewer', 'items']);
        $order->creator?->notify(new OrderApprovedNotification($order));

        // Notify all nabavka users about the approved work order
        User::where('role', 'nabavka')->get()
            ->each(fn ($u) => $u->notify(new WorkOrderReadyForProcurementNotification($order)));

        return response()->json(['message' => 'Nalog je odobren.']);
    }

    public function rejectOrder(Request $request, WorkOrder $order): JsonResponse
    {
        if ($order->status !== WorkOrder::STATUS_SUBMITTED) {
            return response()->json(['message' => 'Nalog nije podnesen na odobrenje.'], 422);
        }

        $data = $request->validate(['note' => 'required|string|max:500']);

        $order->update([
            'status' => WorkOrder::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $data['note'],
        ]);

        // Notify vodja (order creator)
        $order->load(['creator', 'project', 'reviewer']);
        $order->creator?->notify(new OrderRejectedNotification($order));

        return response()->json(['message' => 'Nalog je odbijen.']);
    }

    // ─── Excel Export ─────────────────────────────────────────────────────────

    public function exportProject(Project $project): StreamedResponse
    {
        @ini_set('memory_limit', '512M');

        $rows = $this->buildProjectExportRows($project);
        $teamLabel = $this->buildProjectExportTeamLabel($project);
        $exportPath = $this->buildProjectExportWorkbook($rows, $teamLabel);
        $filename = 'projekat-' . Str::slug($project->name) . '-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($exportPath) {
            $stream = fopen($exportPath, 'rb');

            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }

            @unlink($exportPath);
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    private function buildProjectExportRows(Project $project): array
    {
        $entries = WorkEntry::with(['worker', 'project', 'street', 'enclosure', 'operations'])
            ->where('project_id', $project->id)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $userIds = $entries->pluck('user_id')->filter()->unique()->values();
        $dates = $entries->pluck('date')->map(fn ($date) => $date->format('Y-m-d'))->unique()->values();

        $dayComments = WorkDayComment::query()
            ->when($userIds->isNotEmpty(), fn ($query) => $query->whereIn('user_id', $userIds))
            ->when($dates->isNotEmpty(), fn ($query) => $query->whereIn('date', $dates))
            ->get()
            ->keyBy(fn (WorkDayComment $comment) => $comment->user_id . '|' . $comment->date->format('Y-m-d'));

        $teamNames = DB::table('project_team_users')
            ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
            ->where('project_teams.project_id', $project->id)
            ->whereNull('project_teams.finished_at')
            ->select('project_team_users.user_id', 'project_teams.name')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->user_id => $row->name])
            ->all();

        $rows = [];

        foreach ($entries as $entry) {
            $dayKey = $entry->user_id . '|' . $entry->date->format('Y-m-d');
            $dayComment = $dayComments->get($dayKey)?->comment ?? '';
            $teamName = $teamNames[$entry->user_id] ?? '';

            $base = [
                'A' => $entry->date->format('W'),
                'B' => $teamName,
                'C' => $entry->date->format('d.m.Y.'),
                'D' => 'TTTT',
                'E' => $entry->cable_type,
                'F' => $entry->enclosure?->name ?? '',
                'H' => '',
                'K' => '',
                'L' => '',
                'M' => '',
                'O' => '',
                'Q' => '',
                'R' => '',
                'S' => '',
                'T' => '',
                'U' => '',
                'V' => '',
                'W' => '',
                'X' => '',
                'Y' => '',
                'Z' => '',
                'AA' => '',
                'AB' => '',
                'AC' => '',
                'AD' => $dayComment,
            ];

            if ($entry->operations->isEmpty()) {
                $rows[] = $base + [
                    'G' => $entry->street?->name ?? '',
                ];

                continue;
            }

            foreach ($entry->operations as $operation) {
                $operationLabel = Operation::KINDS[$operation->kind] ?? Str::headline((string) $operation->kind);
                $excavationLabel = $operation->excavation_type
                    ? (Operation::EXCAVATION_TYPES[$operation->excavation_type] ?? $operation->excavation_type)
                    : '';
                $address = trim(collect([$entry->street?->name, $operation->address])->filter()->join(' '));
                $meterage = $operation->meterage ?? '';
                $hasHp = $operation->kind === 'ha_plus' || !empty($operation->sub_operations ?? []);

                $rows[] = $base + [
                    'G' => $address !== '' ? $address : ($entry->street?->name ?? ''),
                    'I' => $operationLabel,
                    'J' => $meterage,
                    'K' => $meterage !== '' ? 'm' : '',
                    'N' => $excavationLabel,
                    'P' => $operation->dimensions ?? '',
                    'U' => $hasHp ? 'Ja' : '',
                ];
            }
        }

        return $rows;
    }

    private function buildProjectExportTeamLabel(Project $project): string
    {
        return DB::table('project_teams')
            ->where('project_id', $project->id)
            ->whereNull('finished_at')
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    private function buildProjectExportWorkbook(array $rows, string $teamLabel = ''): string
    {
        $templatePath = public_path('TemplateEIR.xlsx');
        $tempBasePath = tempnam(sys_get_temp_dir(), 'eir-export-');

        if ($tempBasePath === false) {
            throw new \RuntimeException('Ne mogu pripremiti export fajl.');
        }

        $exportPath = $tempBasePath . '.xlsx';
        @unlink($tempBasePath);
        @unlink($exportPath);

        if (!copy($templatePath, $exportPath)) {
            throw new \RuntimeException('Ne mogu kopirati export template.');
        }

        $zip = new ZipArchive();

        if ($zip->open($exportPath) !== true) {
            throw new \RuntimeException('Ne mogu otvoriti export template.');
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($sheetXml === false) {
            $zip->close();
            throw new \RuntimeException('Worksheet sheet1.xml nije pronađen u template-u.');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($sheetXml);

        $xpath = new DOMXPath($dom);
        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xpath->registerNamespace('x', $namespace);

        $sheetData = $xpath->query('/x:worksheet/x:sheetData')->item(0);
        $dimension = $xpath->query('/x:worksheet/x:dimension')->item(0);
        $templateFirstRow = $xpath->query('/x:worksheet/x:sheetData/x:row[@r="3"]')->item(0);
        $templateDefaultRow = $xpath->query('/x:worksheet/x:sheetData/x:row[@r="4"]')->item(0) ?: $templateFirstRow;

        if (!$sheetData instanceof DOMElement || !$templateFirstRow instanceof DOMElement || !$templateDefaultRow instanceof DOMElement) {
            $zip->close();
            throw new \RuntimeException('Template nema očekivanu strukturu redova za export.');
        }

        $headerTeamCell = $xpath->query('/x:worksheet/x:sheetData/x:row[@r="1"]/x:c[@r="B1"]')->item(0);
        if ($headerTeamCell instanceof DOMElement) {
            $this->setInlineCellValue($dom, $headerTeamCell, $teamLabel);
        }

        $firstRowStyles = $this->extractTemplateRowStyles($templateFirstRow);
        $defaultRowStyles = $this->extractTemplateRowStyles($templateDefaultRow);

        $existingRows = [];
        foreach ($xpath->query('/x:worksheet/x:sheetData/x:row[number(@r) >= 3]') as $rowNode) {
            $existingRows[] = $rowNode;
        }

        foreach ($existingRows as $rowNode) {
            $sheetData->removeChild($rowNode);
        }

        foreach ($rows === [] ? [[]] : $rows as $index => $values) {
            $styleMap = $index === 0 ? $firstRowStyles : $defaultRowStyles;
            $sheetData->appendChild($this->buildTemplateRowNode($dom, $namespace, 3 + $index, $styleMap, $values));
        }

        if ($dimension instanceof DOMElement) {
            $dimension->setAttribute('ref', 'A1:CH' . max(2, 2 + max(1, count($rows))));
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', $dom->saveXML());
        $zip->close();

        return $exportPath;
    }

    private function extractTemplateRowStyles(DOMElement $row): array
    {
        $styles = [];

        foreach ($row->getElementsByTagName('c') as $cell) {
            $reference = $cell->getAttribute('r');
            $column = preg_replace('/\d+/', '', $reference);

            if ($column === '' || $this->columnLetterToIndex($column) > 30) {
                continue;
            }

            $styles[$column] = $cell->hasAttribute('s') ? $cell->getAttribute('s') : null;
        }

        return $styles;
    }

    private function buildTemplateRowNode(DOMDocument $dom, string $namespace, int $rowNumber, array $styles, array $values): DOMElement
    {
        $row = $dom->createElementNS($namespace, 'row');
        $row->setAttribute('r', (string) $rowNumber);

        foreach ($styles as $column => $style) {
            $cell = $dom->createElementNS($namespace, 'c');
            $cell->setAttribute('r', $column . $rowNumber);

            if ($style !== null && $style !== '') {
                $cell->setAttribute('s', $style);
            }

            $value = $values[$column] ?? null;

            if ($value !== null && $value !== '') {
                $this->setInlineCellValue($dom, $cell, (string) $value);
            }

            $row->appendChild($cell);
        }

        return $row;
    }

    private function setInlineCellValue(DOMDocument $dom, DOMElement $cell, string $value): void
    {
        while ($cell->firstChild) {
            $cell->removeChild($cell->firstChild);
        }

        if ($value === '') {
            $cell->removeAttribute('t');
            return;
        }

        $cell->setAttribute('t', 'inlineStr');
        $inlineString = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'is');
        $text = $dom->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 't');
        $text->appendChild($dom->createTextNode($value));
        $inlineString->appendChild($text);
        $cell->appendChild($inlineString);
    }

    private function columnLetterToIndex(string $column): int
    {
        $index = 0;

        foreach (str_split($column) as $character) {
            $index = ($index * 26) + (ord($character) - 64);
        }

        return $index;
    }
}
