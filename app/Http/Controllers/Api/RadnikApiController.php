<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use App\Models\Operation;
use App\Models\OperationImage;
use App\Models\Project;
use App\Models\Street;
use App\Models\WorkEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RadnikApiController extends Controller
{
    public function entries(): JsonResponse
    {
        $entries = WorkEntry::with(['project.city', 'enclosure', 'street', 'operations.images'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function (WorkEntry $entry) {
                return [
                    'id'          => $entry->id,
                    'date'        => $entry->date->format('d.m.Y.'),
                    'cable_type'  => $entry->cable_type,
                    'work_types'  => $entry->work_types,
                    'project'     => [
                        'id'   => $entry->project->id,
                        'name' => $entry->project->name,
                        'city' => $entry->project->city->name,
                    ],
                    'street'      => $entry->street?->name,
                    'enclosure'   => $entry->enclosure?->name,
                    'operations'  => $entry->operations->map(function (Operation $op) {
                        $streetNames = Street::query()
                            ->whereIn('id', $op->street_ids ?? [])
                            ->orderBy('name')
                            ->pluck('name')
                            ->values();

                        $subOps = collect($op->sub_operations ?? [])->map(function ($sub) {
                            if (!empty($sub['photos'])) {
                                $sub['photos'] = collect($sub['photos'])
                                    ->map(fn ($p) => url('storage/' . $p))
                                    ->values()
                                    ->toArray();
                            }
                            return $sub;
                        })->toArray();

                        return [
                            'id'             => $op->id,
                            'kind'           => $op->kind,
                            'streets'        => $streetNames,
                            'excavation_type'=> $op->excavation_type,
                            'dimensions'     => $op->dimensions,
                            'meterage'       => $op->meterage,
                            'address'        => $op->address,
                            'splajsovano'    => $op->splajsovano,
                            'aktivirano'     => $op->aktivirano,
                            'sub_operations' => $subOps,
                            'images'         => $op->images->map(fn ($img) => [
                                'url'  => url('storage/' . $img->path),
                                'name' => $img->original_name,
                            ])->values(),
                        ];
                    }),
                ];
            });

        return response()->json($entries);
    }

    public function formConfig(): JsonResponse
    {
        $projects = Project::with(['city', 'streets'])
            ->latest()
            ->get()
            ->map(fn (Project $p) => [
                'id'      => $p->id,
                'name'    => $p->name,
                'city'    => $p->city->name,
                'streets' => $p->streets->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            ]);

        $enclosures = Enclosure::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'projects'   => $projects,
            'enclosures' => $enclosures,
            'cable_types' => WorkEntry::CABLE_TYPES,
            'work_types'  => WorkEntry::WORK_TYPES,
        ]);
    }

    public function storeEntry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'                                  => 'required|exists:projects,id',
            'cable_type'                                  => 'required|string|max:50',
            'work_types'                                  => 'required|array|min:1',
            'work_types.*'                                => Rule::in(array_keys(WorkEntry::WORK_TYPES)),
            'enclosure_id'                                => 'required|exists:enclosures,id',
            'street_ids'                                  => 'required|array|min:1',
            'street_ids.*'                                => 'exists:streets,id',
            'date'                                        => 'required|date',
            'operations'                                  => 'required|array|min:1',
            'operations.*.kind'                           => 'required|in:iskop,upuhivanje',
            'operations.*.excavation_type'                => 'nullable|in:iskop,iskop_flaster,iskop_asfalt,raketa',
            'operations.*.dimensions'                     => 'nullable|in:15x45,15x60,30x45,30x60',
            'operations.*.meterage'                       => 'nullable|numeric|min:0.01',
            'operations.*.address'                        => 'nullable|string|max:255',
            'operations.*.splajsovano'                    => 'nullable|boolean',
            'operations.*.aktivirano'                     => 'nullable|boolean',
            'operations.*.sub_operations'                 => 'nullable|array',
            'operations.*.sub_operations.*.type'          => 'in:HP+',
            'operations.*.sub_operations.*.meterage'      => 'nullable|numeric|min:0.01',
            'operations.*.sub_operations.*.broj_kucice'   => 'nullable|string|max:50',
        ]);

        // Per-operation kind validation
        foreach ($data['operations'] as $i => $op) {
            if ($op['kind'] === 'iskop') {
                if (empty($op['excavation_type'])) {
                    return response()->json(['errors' => ["operations.{$i}.excavation_type" => ['Vrsta iskopa je obavezna.']]], 422);
                }
                if (empty($op['dimensions'])) {
                    return response()->json(['errors' => ["operations.{$i}.dimensions" => ['Dimenzije su obavezne.']]], 422);
                }
                if (empty($op['meterage'])) {
                    return response()->json(['errors' => ["operations.{$i}.meterage" => ['Metraža je obavezna.']]], 422);
                }
            } elseif ($op['kind'] === 'upuhivanje') {
                if (empty($op['address'])) {
                    return response()->json(['errors' => ["operations.{$i}.address" => ['Adresa je obavezna.']]], 422);
                }
            }
        }

        $entry = WorkEntry::create([
            'project_id'   => $data['project_id'],
            'user_id'      => Auth::id(),
            'cable_type'   => $data['cable_type'],
            'work_types'   => $data['work_types'],
            'enclosure_id' => $data['enclosure_id'],
            'street_id'    => $data['street_ids'][0],
            'date'         => $data['date'],
        ]);

        foreach ($data['operations'] as $i => $opData) {
            $operationPayload = [
                'work_entry_id' => $entry->id,
                'kind' => $opData['kind'],
                'street_ids' => array_values($data['street_ids']),
            ];

            if ($opData['kind'] === 'iskop') {
                $subOps = $opData['sub_operations'] ?? [];

                // Handle sub-operation file uploads
                foreach ($subOps as $j => $sub) {
                    $fileKey = "sub_photos_{$i}_{$j}";
                    if ($request->hasFile($fileKey)) {
                        $paths = [];
                        foreach ((array) $request->file($fileKey) as $file) {
                            $paths[] = $file->store('operation-images', 'public');
                        }
                        $subOps[$j]['photos'] = $paths;
                    }
                }

                $operationPayload += [
                    'excavation_type' => $opData['excavation_type'],
                    'dimensions'      => $opData['dimensions'],
                    'meterage'        => $opData['meterage'],
                    'sub_operations'  => $subOps,
                ];
            } else {
                $operationPayload += [
                    'address'     => $opData['address'] ?? null,
                    'splajsovano' => $opData['splajsovano'] ?? false,
                    'aktivirano'  => $opData['aktivirano'] ?? false,
                ];
            }

            $operation = Operation::create($operationPayload);

            // Operation-level photo uploads
            $photoKey = "photos_{$i}";
            if ($request->hasFile($photoKey)) {
                foreach ((array) $request->file($photoKey) as $file) {
                    $path = $file->store('operation-images', 'public');
                    OperationImage::create([
                        'operation_id'  => $operation->id,
                        'path'          => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Unos je uspješno sačuvan.', 'id' => $entry->id], 201);
    }
}
