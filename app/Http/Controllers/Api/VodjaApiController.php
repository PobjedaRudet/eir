<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Operation;
use App\Models\Project;
use App\Models\Street;
use App\Models\WorkEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VodjaApiController extends Controller
{
    public function projects(): JsonResponse
    {
        $projects = Project::with(['city', 'streets', 'workEntries'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(fn (Project $p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'date'         => $p->date->format('d.m.Y.'),
                'city'         => $p->city->name,
                'streets'      => $p->streets->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                'entries_count'=> $p->workEntries->count(),
            ]);

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
            ])
            ->whereHas('project', fn ($q) => $q->where('user_id', Auth::id()));

        if ($projectId) {
            $query->where('project_id', $projectId);
        }
        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $entries = $query
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

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
                        'id'             => $op->id,
                        'kind'           => $op->kind,
                        'streets'        => \App\Models\Street::query()
                            ->whereIn('id', $op->street_ids ?? [])
                            ->orderBy('name')
                            ->pluck('name')
                            ->values(),
                        'excavation_type'=> $op->excavation_type,
                        'dimensions'     => $op->dimensions,
                        'meterage'       => $op->meterage,
                        'address'        => $op->address,
                        'splajsovano'    => $op->splajsovano,
                        'aktivirano'     => $op->aktivirano,
                        'sub_operations' => $op->sub_operations ?? [],
                        'images'         => $op->images->map(fn ($img) => [
                            'url'  => asset('storage/'.$img->path),
                            'name' => $img->original_name,
                        ]),
                    ]),
                ]),
            ])
            ->values();

        $projects = Project::with('city')
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(fn (Project $p) => ['id' => $p->id, 'name' => $p->name, 'city' => $p->city->name]);

        return response()->json([
            'days'     => $grouped,
            'projects' => $projects,
            'excavation_types' => Operation::EXCAVATION_TYPES,
            'work_types'       => WorkEntry::WORK_TYPES,
        ]);
    }
}
