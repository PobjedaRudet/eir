<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Enclosure;
use App\Models\Equipment;
use App\Models\Gradiliste;
use App\Models\Material;
use App\Models\Ntv;
use App\Models\Operation;
use App\Models\Project;
use App\Models\ProjectNtv;
use App\Models\ProjectService;
use App\Models\ProjectTeam;
use App\Models\ResourcePlan;
use App\Models\ResourcePlanHistory;
use App\Models\ResourcePlanItem;
use App\Models\ServiceOrder;
use App\Models\Street;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkDayComment;
use App\Models\WorkEntry;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Notifications\OrderSubmittedNotification;
use App\Notifications\ProjectSubmittedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VodjaApiController extends Controller
{
    // ─── Projects ────────────────────────────────────────────────────────────

    public function projects(): JsonResponse
    {
        $projects = Project::with(['city', 'streets', 'workEntries', 'projectNtvs.ntv', 'projectNtvs.streets', 'projectNtvs.team', 'teams'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function (Project $p) {
                $latestPlan = ResourcePlan::where('project_id', $p->id)
                    ->orderBy('version', 'desc')
                    ->first();

                return [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'date'            => $p->date->format('d.m.Y.'),
                    'city'            => $p->city->name,
                    'streets'         => $p->streets->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'entries_count'   => $p->workEntries->count(),
                    'status'          => $p->status,
                    'rejection_note'  => $p->rejection_note,
                    'cable_type'      => $p->cable_type,
                    'ntv_count'       => $p->projectNtvs->count(),
                    'team_count'      => $p->teams->count(),
                    'teams'           => $p->teams
                        ->map(fn ($team) => [
                            'id' => $team->id,
                            'name' => $team->name,
                            'ntvs' => $p->projectNtvs
                                ->where('project_team_id', $team->id)
                                ->map(fn ($projectNtv) => [
                                    'id' => $projectNtv->id,
                                    'name' => $projectNtv->ntv?->name,
                                    'streets' => $projectNtv->streets
                                        ->map(fn ($street) => $street->name)
                                        ->filter()
                                        ->values(),
                                ])
                                ->filter(fn ($projectNtv) => ! empty($projectNtv['name']))
                                ->values(),
                        ])
                        ->filter(fn ($team) => ! empty($team['name']))
                        ->values(),
                    'plan_status'     => $latestPlan?->status,
                    'plan_version'    => $latestPlan?->version,
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

        $ntvs    = Ntv::orderBy('name')->get(['id', 'name']);
        $teams   = Team::orderBy('name')->get(['id', 'name']);
        $workers = User::where('role', 'radnik')->orderBy('name')->get(['id', 'name']);

        $busyWorkerIds = DB::table('project_team_users')
            ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
            ->join('projects', 'project_teams.project_id', '=', 'projects.id')
            ->whereIn('projects.status', [Project::STATUS_NA_CEKANJU, Project::STATUS_AKTIVAN])
            ->whereNull('project_teams.finished_at')
            ->pluck('project_team_users.user_id')
            ->unique()
            ->values()
            ->toArray();

        return response()->json(['cities' => $cities, 'ntvs' => $ntvs, 'teams' => $teams, 'workers' => $workers, 'busy_worker_ids' => $busyWorkerIds]);
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

    public function destroyProject(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        if ($project->status !== Project::STATUS_ZAKLJUCEN) {
            return response()->json([
                'message' => 'Moguće je obrisati samo zaključene projekte.',
            ], 422);
        }

        $data = $request->validate([
            'confirmation_code' => ['required', 'string', Rule::in(['0000'])],
        ], [
            'confirmation_code.in' => 'Za brisanje projekta morate unijeti kod 0000.',
        ]);

        DB::transaction(function () use ($project, $data) {
            $project->delete();
        });

        return response()->json([
            'message' => 'Projekat je uspješno obrisan.',
        ]);
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

    public function storeCity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($data['name']);

        $city = City::query()->firstOrCreate([
            'name' => $name,
        ]);

        return response()->json([
            'message' => 'Grad je uspješno sačuvan.',
            'city'    => [
                'id'   => $city->id,
                'name' => $city->name,
            ],
        ], 201);
    }

    public function storeStreet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name'    => 'required|string|max:255',
        ]);

        $name = trim($data['name']);

        $street = Street::query()->firstOrCreate([
            'city_id' => $data['city_id'],
            'name'    => $name,
        ]);

        return response()->json([
            'message' => 'Ulica je uspješno sačuvana.',
            'street'  => [
                'id'   => $street->id,
                'name' => $street->name,
            ],
        ], 201);
    }

    public function storeProject(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                          => 'required|string|max:255',
            'date'                          => 'required|date',
            'city_id'                       => 'required|exists:cities,id',
            'street_ids'                    => 'required|array|min:1',
            'street_ids.*'                  => 'exists:streets,id',
            'cable_type'                    => 'required|in:8Y0001_1,8Y0001_2,8Y0001_3',
            'teams'                         => 'array',
            'teams.*.catalog_team_id'       => 'required_with:teams|exists:teams,id',
            'teams.*.worker_ids'            => 'array',
            'teams.*.worker_ids.*'          => 'exists:users,id',
            'teams.*.ntvs'                  => 'array',
            'teams.*.ntvs.*.ntv_id'         => 'required_with:teams.*.ntvs|exists:ntvs,id',
            'teams.*.ntvs.*.street_ids'     => 'array',
            'teams.*.ntvs.*.street_ids.*'   => 'exists:streets,id',
        ]);

        // A worker cannot be in multiple teams within the same request
        $requestedWorkerIds = collect($data['teams'] ?? [])
            ->flatMap(fn ($t) => $t['worker_ids'] ?? [])
            ->toArray();
        if (count($requestedWorkerIds) !== count(array_unique($requestedWorkerIds))) {
            return response()->json(['message' => 'Radnik ne može biti dodijeljen u više timova istovremeno.'], 422);
        }

        // A worker cannot be in a team on another active/pending project
        if (!empty($requestedWorkerIds)) {
            $busyIds = DB::table('project_team_users')
                ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
                ->join('projects', 'project_teams.project_id', '=', 'projects.id')
                ->whereIn('projects.status', [Project::STATUS_NA_CEKANJU, Project::STATUS_AKTIVAN])
                ->whereNull('project_teams.finished_at')
                ->whereIn('project_team_users.user_id', $requestedWorkerIds)
                ->pluck('project_team_users.user_id')
                ->unique()
                ->toArray();

            if (!empty($busyIds)) {
                $names = User::whereIn('id', $busyIds)->pluck('name')->join(', ');
                return response()->json(['message' => "Sljedeći radnici su već zauzeti na aktivnom projektu: {$names}."], 422);
            }
        }

        $project = DB::transaction(function () use ($data) {
            $project = Project::create([
                'name'       => $data['name'],
                'date'       => $data['date'],
                'city_id'    => $data['city_id'],
                'user_id'    => Auth::id(),
                'status'     => Project::STATUS_NA_CEKANJU,
                'cable_type' => $data['cable_type'],
            ]);

            $project->streets()->attach($data['street_ids']);

            $allWorkerIds = [];
            foreach ($data['teams'] ?? [] as $teamData) {
                $catalogTeam = Team::find($teamData['catalog_team_id']);
                if (!$catalogTeam) continue;

                $projectTeam = ProjectTeam::create([
                    'project_id' => $project->id,
                    'name'       => $catalogTeam->name,
                ]);

                $workerIds = $teamData['worker_ids'] ?? [];
                if (!empty($workerIds)) {
                    $projectTeam->workers()->sync($workerIds);
                    $allWorkerIds = array_merge($allWorkerIds, $workerIds);
                }

                foreach ($teamData['ntvs'] ?? [] as $ntvData) {
                    $projectNtv = ProjectNtv::create([
                        'project_id'      => $project->id,
                        'ntv_id'          => $ntvData['ntv_id'],
                        'project_team_id' => $projectTeam->id,
                    ]);

                    if (!empty($ntvData['street_ids'])) {
                        $projectNtv->streets()->attach($ntvData['street_ids']);
                    }
                }
            }

            if (!empty($allWorkerIds)) {
                $project->workers()->sync(array_unique($allWorkerIds));
            }

            return $project;
        });

        $project->load(['city', 'leader']);

        User::where('role', 'mpm')->get()
            ->each(fn ($mpm) => $mpm->notify(new ProjectSubmittedNotification($project)));

        return response()->json(['message' => 'Projekat je uspješno kreiran i čeka odobrenje PM-a.'], 201);
    }

    public function projectSetup(Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $project->load(['teams', 'projectNtvs.ntv', 'projectNtvs.streets', 'projectNtvs.team']);

        return response()->json([
            'teams' => $project->teams->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'ntvs'  => $project->projectNtvs->map(fn ($pn) => [
                'id'         => $pn->id,
                'ntv_id'     => $pn->ntv_id,
                'ntv_name'   => $pn->ntv->name,
                'team_id'    => $pn->project_team_id,
                'team_name'  => $pn->team?->name,
                'street_ids' => $pn->streets->pluck('id')->values(),
                'street_names' => $pn->streets->pluck('name')->values(),
            ])->values(),
        ]);
    }

    public function updateProjectSetup(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($project->status, [Project::STATUS_NA_CEKANJU, Project::STATUS_ODBIJEN])) {
            return response()->json(['message' => 'Postavljanje nije dozvoljeno za ovaj status projekta.'], 422);
        }

        $data = $request->validate([
            'teams'               => 'required|array|min:1',
            'teams.*.name'        => 'required|string|max:200',
            'ntvs'                => 'required|array|min:1',
            'ntvs.*.ntv_id'       => 'required|exists:ntvs,id',
            'ntvs.*.team_index'   => 'nullable|integer',
            'ntvs.*.street_ids'   => 'array',
            'ntvs.*.street_ids.*' => 'exists:streets,id',
        ]);

        DB::transaction(function () use ($data, $project) {
            // Replace teams
            $project->teams()->delete();
            $createdTeams = [];
            foreach ($data['teams'] as $teamData) {
                $createdTeams[] = ProjectTeam::create([
                    'project_id' => $project->id,
                    'name'       => $teamData['name'],
                ]);
            }

            // Replace NTV assignments
            $project->projectNtvs()->each(fn ($pn) => $pn->streets()->detach());
            $project->projectNtvs()->delete();

            foreach ($data['ntvs'] as $ntvData) {
                $teamId = null;
                $idx = $ntvData['team_index'] ?? null;
                if ($idx !== null && isset($createdTeams[$idx])) {
                    $teamId = $createdTeams[$idx]->id;
                }

                $projectNtv = ProjectNtv::create([
                    'project_id'      => $project->id,
                    'ntv_id'          => $ntvData['ntv_id'],
                    'project_team_id' => $teamId,
                ]);

                if (!empty($ntvData['street_ids'])) {
                    $projectNtv->streets()->attach($ntvData['street_ids']);
                }
            }
        });

        return response()->json(['message' => 'Postavljanje projekta je ažurirano.']);
    }

    public function resubmitProject(Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        if ($project->status !== Project::STATUS_ODBIJEN) {
            return response()->json(['message' => 'Samo odbijeni projekat se može ponovo podnijeti.'], 422);
        }

        $project->update([
            'status'         => Project::STATUS_NA_CEKANJU,
            'rejection_note' => null,
        ]);

        $project->loadMissing(['city', 'leader']);

        User::where('role', 'mpm')->get()
            ->each(fn ($mpm) => $mpm->notify(new ProjectSubmittedNotification($project)));

        return response()->json(['message' => 'Projekat je ponovo podnesen na odobrenje.']);
    }

    public function updateCableType(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($project->status, [Project::STATUS_NA_CEKANJU, Project::STATUS_ODBIJEN])) {
            return response()->json(['message' => 'Tip kabla se može mijenjati samo za projekte koji nisu još odobreni.'], 422);
        }

        $data = $request->validate([
            'cable_type' => 'required|in:8Y0001_1,8Y0001_2,8Y0001_3',
        ]);

        $project->update(['cable_type' => $data['cable_type']]);

        return response()->json(['message' => 'Tip kabla je ažuriran.']);
    }

    // ─── Worker management ───────────────────────────────────────────────────

    public function workers(): JsonResponse
    {
        $workers = User::where('role', 'radnik')->orderBy('name')->get(['id', 'name', 'email']);

        return response()->json(['workers' => $workers]);
    }

    public function storeWorker(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $worker = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => 'radnik',
            'email_verified_at'  => now(),
        ]);

        return response()->json([
            'message' => 'Radnik je uspješno kreiran.',
            'worker'  => ['id' => $worker->id, 'name' => $worker->name, 'email' => $worker->email],
        ], 201);
    }

    public function syncVodjaProjectWorkers(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'user_ids'   => 'present|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $validIds = User::whereIn('id', $data['user_ids'])
            ->where('role', 'radnik')
            ->pluck('id')
            ->all();

        $project->workers()->sync($validIds);

        return response()->json(['message' => 'Radnici projekta su ažurirani.']);
    }

    public function projectTeamWorkers(Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $project->load([
            'teams' => fn ($q) => $q->with('workers'),
        ]);

        $activeTeams    = $project->teams->whereNull('finished_at')->values();
        $dismissedTeams = $project->teams->whereNotNull('finished_at')->sortByDesc('finished_at')->values();

        // Busy worker IDs: in any active team on any active/pending project
        $busyWorkerIds = DB::table('project_team_users')
            ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
            ->join('projects', 'project_teams.project_id', '=', 'projects.id')
            ->whereIn('projects.status', [Project::STATUS_NA_CEKANJU, Project::STATUS_AKTIVAN])
            ->whereNull('project_teams.finished_at')
            ->pluck('project_team_users.user_id')
            ->unique()
            ->values()
            ->toArray();

        // Catalog teams not already active on this project (match by name)
        $activeNames = $activeTeams->pluck('name')->toArray();
        $availableCatalogTeams = Team::orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn ($t) => ! in_array($t->name, $activeNames))
            ->values();

        $allWorkers = User::where('role', 'radnik')->orderBy('name')->get(['id', 'name']);

        $mapTeam = fn ($t) => [
            'id'          => $t->id,
            'name'        => $t->name,
            'created_at'  => $t->created_at,
            'finished_at' => $t->finished_at,
            'workers'     => $t->workers->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
            ])->values(),
        ];

        return response()->json([
            'project_name'           => $project->name,
            'project_status'         => $project->status,
            'active_teams'           => $activeTeams->map($mapTeam)->values(),
            'dismissed_teams'        => $dismissedTeams->map($mapTeam)->values(),
            'all_workers'            => $allWorkers,
            'busy_worker_ids'        => $busyWorkerIds,
            'available_catalog_teams' => $availableCatalogTeams,
        ]);
    }

    public function addTeamToProject(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'catalog_team_id' => 'required|exists:teams,id',
            'worker_ids'      => 'array',
            'worker_ids.*'    => 'exists:users,id',
        ]);

        $catalogTeam = Team::findOrFail($data['catalog_team_id']);

        // Prevent duplicate active team by name
        $exists = $project->teams()->whereNull('finished_at')->where('name', $catalogTeam->name)->exists();
        if ($exists) {
            return response()->json(['message' => 'Tim "' . $catalogTeam->name . '" je već aktivan na ovom projektu.'], 422);
        }

        $workerIds = $data['worker_ids'] ?? [];
        if (! empty($workerIds)) {
            $busyIds = DB::table('project_team_users')
                ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
                ->join('projects', 'project_teams.project_id', '=', 'projects.id')
                ->whereIn('projects.status', [Project::STATUS_NA_CEKANJU, Project::STATUS_AKTIVAN])
                ->whereNull('project_teams.finished_at')
                ->whereIn('project_team_users.user_id', $workerIds)
                ->pluck('project_team_users.user_id')
                ->unique()
                ->toArray();

            if (! empty($busyIds)) {
                $names = User::whereIn('id', $busyIds)->pluck('name')->join(', ');
                return response()->json(['message' => "Sljedeći radnici su već zauzeti: {$names}."], 422);
            }
        }

        $projectTeam = DB::transaction(function () use ($project, $catalogTeam, $workerIds) {
            $pt = ProjectTeam::create([
                'project_id' => $project->id,
                'name'       => $catalogTeam->name,
            ]);
            if (! empty($workerIds)) {
                $pt->workers()->sync($workerIds);
                $project->workers()->syncWithoutDetaching($workerIds);
            }
            return $pt->load('workers');
        });

        return response()->json([
            'message' => 'Tim je dodan projektu.',
            'team'    => [
                'id'          => $projectTeam->id,
                'name'        => $projectTeam->name,
                'created_at'  => $projectTeam->created_at,
                'finished_at' => null,
                'workers'     => $projectTeam->workers->map(fn ($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                ])->values(),
            ],
        ], 201);
    }

    public function dismissTeam(ProjectTeam $team): JsonResponse
    {
        if ($team->project->user_id !== Auth::id()) {
            abort(403);
        }

        if ($team->finished_at !== null) {
            return response()->json(['message' => 'Tim je već raspušten.'], 422);
        }

        $team->update(['finished_at' => now()]);

        // Remove from project_worker anyone no longer in any active team on this project
        $stillActiveWorkerIds = DB::table('project_team_users')
            ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
            ->where('project_teams.project_id', $team->project_id)
            ->whereNull('project_teams.finished_at')
            ->pluck('project_team_users.user_id')
            ->unique()
            ->toArray();

        $team->project->workers()->sync($stillActiveWorkerIds);

        return response()->json([
            'message'     => 'Tim je uspješno raspušten.',
            'finished_at' => $team->finished_at,
        ]);
    }

    public function syncTeamWorkers(Request $request, ProjectTeam $team): JsonResponse
    {
        if ($team->project->user_id !== Auth::id()) {
            abort(403);
        }

        if ($team->finished_at !== null) {
            return response()->json(['message' => 'Raspušteni tim se ne može mijenjati.'], 422);
        }

        $data = $request->validate([
            'user_ids'   => 'present|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $newWorkerIds = $data['user_ids'];

        // Check that newly added workers aren't already in another active team on any project
        $currentWorkerIds = $team->workers()->pluck('users.id')->toArray();
        $addedIds         = array_diff($newWorkerIds, $currentWorkerIds);

        if (! empty($addedIds)) {
            $busyIds = DB::table('project_team_users')
                ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
                ->join('projects', 'project_teams.project_id', '=', 'projects.id')
                ->whereIn('projects.status', [Project::STATUS_NA_CEKANJU, Project::STATUS_AKTIVAN])
                ->whereNull('project_teams.finished_at')
                ->where('project_team_users.project_team_id', '!=', $team->id)
                ->whereIn('project_team_users.user_id', $addedIds)
                ->pluck('project_team_users.user_id')
                ->unique()
                ->toArray();

            if (! empty($busyIds)) {
                $names = User::whereIn('id', $busyIds)->pluck('name')->join(', ');
                return response()->json(['message' => "Radnici su već zauzeti: {$names}."], 422);
            }
        }

        DB::transaction(function () use ($team, $newWorkerIds) {
            $team->workers()->sync($newWorkerIds);

            // Sync project-level workers: all workers in any active team on this project
            $activeWorkerIds = DB::table('project_team_users')
                ->join('project_teams', 'project_team_users.project_team_id', '=', 'project_teams.id')
                ->where('project_teams.project_id', $team->project_id)
                ->whereNull('project_teams.finished_at')
                ->pluck('project_team_users.user_id')
                ->unique()
                ->toArray();

            $team->project->workers()->sync($activeWorkerIds);
        });

        return response()->json(['message' => 'Radnici tima su ažurirani.']);
    }

    // ─── Teams catalog ───────────────────────────────────────────────────────

    public function teamsCatalog(): JsonResponse
    {
        return response()->json(['teams' => Team::orderBy('name')->get(['id', 'name'])]);
    }

    public function storeTeam(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:200|unique:teams,name']);
        $team = Team::create($data);

        return response()->json(['message' => 'Tim je uspješno kreiran.', 'team' => $team], 201);
    }

    public function destroyTeam(Team $team): JsonResponse
    {
        $team->delete();

        return response()->json(['message' => 'Tim je obrisan.']);
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
            $label = $existing->status === ResourcePlan::STATUS_DRAFT ? 'nacrt' : 'plan na čekanju odobrenja';
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
            return response()->json(['message' => 'Resurs nije pronađen.'], 422);
        }

        if ($plan->items()->where('resource_type', $data['resource_type'])->where('resource_id', $data['resource_id'])->exists()) {
            return response()->json(['message' => 'Ovaj resurs je već u planu.'], 422);
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

        return response()->json(['message' => 'Stavka je aLlurirana.']);
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
            return response()->json(['message' => 'MoLle se obrisati samo nacrt.'], 422);
        }

        $plan->delete();

        return response()->json(['message' => 'Nacrt plana je obrisan.']);
    }

    // ─── Report (unchanged) ───────────────────────────────────────────────────

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

        $dayComments = WorkDayComment::query()
            ->whereIn('user_id', $entries->pluck('user_id')->unique()->values())
            ->whereIn('date', $entries->pluck('date')->map(fn ($date) => $date->format('Y-m-d'))->unique()->values())
            ->get()
            ->keyBy(fn (WorkDayComment $comment) => $comment->user_id . '|' . $comment->date->format('Y-m-d'));

        $grouped = $entries
            ->groupBy(fn ($e) => $e->date->format('Y-m-d'))
            ->map(fn ($dayEntries, $dateKey) => [
                'date'    => $dateKey,
                'entries' => $dayEntries->map(function (WorkEntry $entry) use ($dateKey, $dayComments) {
                    $dayComment = $dayComments->get($entry->user_id . '|' . $dateKey);

                    return [
                        'id'         => $entry->id,
                        'worker'     => $entry->worker?->name ?? 'Nepoznat radnik',
                        'project'    => $entry->project?->name ?? 'Obrisan projekat',
                        'city'       => $entry->project?->city?->name ?? 'Nepoznat grad',
                        'street'     => $entry->street?->name,
                        'enclosure'  => $entry->enclosure?->name,
                        'cable_type' => $entry->cable_type,
                        'work_types' => $entry->work_types,
                        'day_comment' => $dayComment?->comment,
                        'day_comment_updated_at' => $dayComment?->updated_at?->format('d.m.Y. H:i'),
                        'operations' => $entry->operations->map(fn (Operation $op) => [
                            'id'              => $op->id,
                            'kind'            => $op->kind,
                            'streets'         => Street::whereIn('id', $op->street_ids ?? [])->orderBy('name')->pluck('name')->values(),
                            'excavation_type' => $op->excavation_type,
                            'dimensions'      => $op->dimensions,
                            'meterage'        => $op->meterage,
                            'address'         => $op->address,
                            'upuhano'         => $op->upuhano,
                            'splajsovano'     => $op->splajsovano,
                            'aktivirano'      => $op->aktivirano,
                            'sub_operations'  => $op->sub_operations ?? [],
                            'images'          => $op->images->map(fn ($img) => [
                                'url'  => asset('storage/' . $img->path),
                                'name' => $img->original_name,
                            ]),
                        ]),
                    ];
                }),
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

    // --- Work Orders ----------------------------------------------------------

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

        // Notify all MPM users
        $order->load(['creator', 'project']);
        \App\Models\User::where('role', 'mpm')->get()
            ->each(fn ($mpm) => $mpm->notify(new OrderSubmittedNotification($order)));

        return response()->json(['message' => 'Nalog je podnesen na odobrenje.']);
    }

    public function deleteOrder(WorkOrder $order): JsonResponse
    {
        if (! in_array($order->status, [WorkOrder::STATUS_DRAFT, WorkOrder::STATUS_REJECTED])) {
            return response()->json(['message' => 'Nije moguce obrisati nalog koji je podnesen ili odobren.'], 422);
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

    // --- Service Orders ------------------------------------------------------

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
            return response()->json(['message' => 'Tražena kolicina prelazi dostupnu kolicinu.'], 422);
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

    // ── Gradilište ──────────────────────────────────────────────────────────

    public function gradilisteData(Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) abort(403);

        $gradiliste = $project->gradiliste
            ?? Gradiliste::create(['project_id' => $project->id]);
        $gradiliste->load('equipment', 'materials');

        $activeTeams = $project->teams()
            ->whereNull('finished_at')
            ->with(['workers', 'equipment'])
            ->get();

        $equipmentCatalog = Equipment::orderBy('category')->orderBy('name')->get();
        $materialCatalog  = Material::orderBy('category')->orderBy('name')->get();

        return response()->json([
            'project_name'         => $project->name,
            'project_status'       => $project->status,
            'gradiliste_id'        => $gradiliste->id,
            'gradiliste_equipment' => $gradiliste->equipment->map(fn ($e) => [
                'id'             => $e->id,
                'name'           => $e->name,
                'category'       => $e->category,
                'category_label' => Equipment::CATEGORIES[$e->category] ?? $e->category,
                'quantity'       => $e->pivot->quantity,
            ])->values(),
            'gradiliste_materials' => $gradiliste->materials->map(fn ($m) => [
                'id'             => $m->id,
                'name'           => $m->name,
                'category'       => $m->category,
                'category_label' => Material::CATEGORIES[$m->category] ?? $m->category,
                'unit'           => $m->unit,
                'quantity'       => $m->pivot->quantity,
            ])->values(),
            'active_teams' => $activeTeams->map(fn ($t) => [
                'id'        => $t->id,
                'name'      => $t->name,
                'workers'   => $t->workers->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(),
                'equipment' => $t->equipment->map(fn ($e) => [
                    'id'             => $e->id,
                    'name'           => $e->name,
                    'category'       => $e->category,
                    'category_label' => Equipment::CATEGORIES[$e->category] ?? $e->category,
                    'quantity'       => $e->pivot->quantity,
                ])->values(),
            ])->values(),
            'equipment_catalog' => $equipmentCatalog->map(fn ($e) => [
                'id'             => $e->id,
                'name'           => $e->name,
                'category'       => $e->category,
                'category_label' => Equipment::CATEGORIES[$e->category] ?? $e->category,
            ])->values(),
            'material_catalog' => $materialCatalog->map(fn ($m) => [
                'id'             => $m->id,
                'name'           => $m->name,
                'category'       => $m->category,
                'category_label' => Material::CATEGORIES[$m->category] ?? $m->category,
                'unit'           => $m->unit,
            ])->values(),
        ]);
    }

    public function syncGradilisteEquipment(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) abort(403);

        $data = $request->validate([
            'equipment'                => 'present|array',
            'equipment.*.equipment_id' => 'required|exists:equipment,id',
            'equipment.*.quantity'     => 'required|integer|min:1',
        ]);

        $gradiliste = $project->gradiliste
            ?? Gradiliste::create(['project_id' => $project->id]);

        $sync = collect($data['equipment'])->mapWithKeys(
            fn ($e) => [$e['equipment_id'] => ['quantity' => $e['quantity']]]
        )->toArray();

        $gradiliste->equipment()->sync($sync);

        return response()->json(['message' => 'Oprema gradilišta je ažurirana.']);
    }

    public function syncGradillisteMaterials(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== Auth::id()) abort(403);

        $data = $request->validate([
            'materials'                => 'present|array',
            'materials.*.material_id'  => 'required|exists:materials,id',
            'materials.*.quantity'     => 'required|numeric|min:0.01',
        ]);

        $gradiliste = $project->gradiliste
            ?? Gradiliste::create(['project_id' => $project->id]);

        $sync = collect($data['materials'])->mapWithKeys(
            fn ($m) => [$m['material_id'] => ['quantity' => $m['quantity']]]
        )->toArray();

        $gradiliste->materials()->sync($sync);

        return response()->json(['message' => 'Materijal gradilišta je ažuriran.']);
    }

    public function syncTeamEquipment(Request $request, ProjectTeam $team): JsonResponse
    {
        if ($team->project->user_id !== Auth::id()) abort(403);

        if ($team->finished_at !== null) {
            return response()->json(['message' => 'Raspušteni tim se ne može mijenjati.'], 422);
        }

        $data = $request->validate([
            'equipment'                => 'present|array',
            'equipment.*.equipment_id' => 'required|exists:equipment,id',
            'equipment.*.quantity'     => 'required|integer|min:1',
        ]);

        $sync = collect($data['equipment'])->mapWithKeys(
            fn ($e) => [$e['equipment_id'] => ['quantity' => $e['quantity']]]
        )->toArray();

        $team->equipment()->sync($sync);

        return response()->json(['message' => 'Oprema tima je ažurirana.']);
    }
}
