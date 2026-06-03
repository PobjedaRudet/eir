<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use App\Models\Operation;
use App\Models\OperationImage;
use App\Models\Project;
use App\Models\Street;
use App\Models\WorkDayComment;
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
            ->map(fn (WorkEntry $entry) => $this->serializeEntry($entry));

        return response()->json($entries);
    }

    public function showEntry(WorkEntry $entry): JsonResponse
    {
        $this->ensureEditableEntry($entry);

        $entry->loadMissing(['project.city', 'project.streets', 'enclosure', 'street', 'operations.images']);

        return response()->json($this->serializeEntry($entry, true));
    }

    public function dayComments(): JsonResponse
    {
        $comments = WorkDayComment::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('date')
            ->get()
            ->mapWithKeys(fn (WorkDayComment $comment) => [
                $comment->date->format('Y-m-d') => [
                    'date' => $comment->date->format('Y-m-d'),
                    'comment' => $comment->comment,
                    'updated_at' => optional($comment->updated_at)?->format('d.m.Y. H:i'),
                    'can_edit' => $comment->date->isToday(),
                ],
            ]);

        return response()->json($comments);
    }

    public function upsertDayComment(Request $request, string $date): JsonResponse
    {
        $parsedDate = now()->createFromFormat('Y-m-d', $date);

        if (! $parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            return response()->json([
                'message' => 'Datum komentara nije ispravan.',
            ], 422);
        }

        if (! $parsedDate->isToday()) {
            return response()->json([
                'message' => 'Komentar za dnevni rad možete unositi samo za današnji datum.',
            ], 422);
        }

        $hasEntries = WorkEntry::query()
            ->where('user_id', Auth::id())
            ->whereDate('date', $parsedDate)
            ->exists();

        if (! $hasEntries) {
            return response()->json([
                'message' => 'Ne možete unijeti komentar za dan bez ijednog unosa radova.',
            ], 422);
        }

        $data = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $comment = WorkDayComment::query()->updateOrCreate(
            [
                'user_id' => Auth::id(),
                'date' => $parsedDate->format('Y-m-d'),
            ],
            [
                'comment' => trim($data['comment']),
            ]
        );

        return response()->json([
            'message' => 'Dnevni komentar je sačuvan.',
            'comment' => [
                'date' => $comment->date->format('Y-m-d'),
                'comment' => $comment->comment,
                'updated_at' => optional($comment->updated_at)?->format('d.m.Y. H:i'),
                'can_edit' => $comment->date->isToday(),
            ],
        ]);
    }

    public function formConfig(): JsonResponse
    {
        $projects = Project::with([
            'city',
            'streets',
            'teams' => fn ($q) => $q
                ->whereNull('finished_at')
                ->whereHas('workers', fn ($workerQuery) => $workerQuery->where('users.id', Auth::id()))
                ->with(['projectNtvs.ntv']),
        ])
            ->where('status', Project::STATUS_AKTIVAN)
            ->whereHas('teams', fn ($q) => $q
                ->whereNull('finished_at')
                ->whereHas('workers', fn ($workerQuery) => $workerQuery->where('users.id', Auth::id())))
            ->latest()
            ->get()
            ->map(function (Project $p) {
                $allowedOptions = $this->resolveProjectWorkerOptions($p);

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'city' => $p->city->name,
                    'streets' => $p->streets->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'cable_types' => $allowedOptions['cable_types'],
                    'enclosures' => $allowedOptions['enclosures'],
                ];
            });

        return response()->json([
            'projects'   => $projects,
            'work_types'  => WorkEntry::WORK_TYPES,
        ]);
    }

    public function storeEntry(Request $request): JsonResponse
    {
        return $this->saveEntry($request);
    }

    public function updateEntry(Request $request, WorkEntry $entry): JsonResponse
    {
        $this->ensureEditableEntry($entry);

        return $this->saveEntry($request, $entry);
    }

    private function saveEntry(Request $request, ?WorkEntry $entry = null): JsonResponse
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
            'operations.*.kind'                           => 'required|in:iskop,upuhivanje,ha_plus',
            'operations.*.excavation_type'                => 'nullable|in:iskop,iskop_flaster,iskop_asfalt,raketa',
            'operations.*.dimensions'                     => 'nullable|in:15x45,15x60,30x45,30x60',
            'operations.*.meterage'                       => 'nullable|numeric|min:0.01',
            'operations.*.address'                        => 'nullable|string|max:255',
            'operations.*.upuhano'                        => 'nullable|boolean',
            'operations.*.splajsovano'                    => 'nullable|boolean',
            'operations.*.aktivirano'                     => 'nullable|boolean',
            'operations.*.sub_operations'                 => 'nullable|array',
            'operations.*.sub_operations.*.type'          => ['required', Rule::in(Operation::SUB_OPERATION_TYPES)],
            'operations.*.sub_operations.*.meterage'      => 'nullable|numeric|min:0.01',
            'operations.*.sub_operations.*.broj_kucice'   => 'nullable|string|max:50',
            'operations.*.existing_images'                => 'nullable|array',
            'operations.*.existing_images.*.path'         => 'required_with:operations.*.existing_images|string|max:2048',
            'operations.*.existing_images.*.name'         => 'nullable|string|max:255',
            'operations.*.sub_operations.*.existing_photos'       => 'nullable|array',
            'operations.*.sub_operations.*.existing_photos.*.path'=> 'required_with:operations.*.sub_operations.*.existing_photos|string|max:2048',
        ]);

        $assignedProject = Project::query()
            ->with([
                'teams' => fn ($q) => $q
                    ->whereNull('finished_at')
                    ->whereHas('workers', fn ($workerQuery) => $workerQuery->where('users.id', Auth::id()))
                    ->with(['projectNtvs.ntv']),
            ])
            ->where('id', $data['project_id'])
            ->whereHas('teams', fn ($q) => $q
                ->whereNull('finished_at')
                ->whereHas('workers', fn ($workerQuery) => $workerQuery->where('users.id', Auth::id())))
            ->first();

        if (! $assignedProject) {
            return response()->json([
                'errors' => [
                    'project_id' => ['Nemate pristup odabranom projektu.'],
                ],
            ], 422);
        }

        $allowedOptions = $this->resolveProjectWorkerOptions($assignedProject);
        $allowedCableTypes = $allowedOptions['cable_types'];
        $allowedEnclosureIds = $allowedOptions['enclosure_ids'];

        if (! in_array($data['cable_type'], $allowedCableTypes, true)) {
            return response()->json([
                'errors' => [
                    'cable_type' => ['Možete odabrati samo glavni kabal projekta.'],
                ],
            ], 422);
        }

        if (! in_array((int) $data['enclosure_id'], $allowedEnclosureIds, true)) {
            return response()->json([
                'errors' => [
                    'enclosure_id' => ['Možete odabrati samo NTV koji je dodijeljen vašem timu.'],
                ],
            ], 422);
        }

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
            } elseif ($op['kind'] === 'ha_plus') {
                if (empty($op['meterage'])) {
                    return response()->json(['errors' => ["operations.{$i}.meterage" => ['Metraža je obavezna.']]], 422);
                }
                if (empty($op['address'])) {
                    return response()->json(['errors' => ["operations.{$i}.address" => ['Broj kuće je obavezan.']]], 422);
                }
            }

            $operationPhotos = $request->file("photos_{$i}", []);
            if (! is_array($operationPhotos)) {
                $operationPhotos = $operationPhotos ? [$operationPhotos] : [];
            }

            $existingOperationImages = $op['existing_images'] ?? [];

            if ((count(array_filter($operationPhotos)) + count($existingOperationImages)) < 1) {
                return response()->json(['errors' => ["operations.{$i}.photos" => ['Za svaku operaciju morate uploadovati barem jednu fotografiju.']]], 422);
            }

            foreach (($op['sub_operations'] ?? []) as $j => $sub) {
                $subOperationPhotos = $request->file("sub_photos_{$i}_{$j}", []);
                if (! is_array($subOperationPhotos)) {
                    $subOperationPhotos = $subOperationPhotos ? [$subOperationPhotos] : [];
                }

                $existingSubPhotos = $sub['existing_photos'] ?? [];

                if ((count(array_filter($subOperationPhotos)) + count($existingSubPhotos)) < 1) {
                    return response()->json(['errors' => ["operations.{$i}.sub_operations.{$j}.photos" => ['Za svaku podoperaciju morate uploadovati barem jednu fotografiju.']]], 422);
                }
            }
        }

        if ($entry) {
            $entry->update([
                'project_id'   => $data['project_id'],
                'cable_type'   => $data['cable_type'],
                'work_types'   => $data['work_types'],
                'enclosure_id' => $data['enclosure_id'],
                'street_id'    => $data['street_ids'][0],
                'date'         => $data['date'],
            ]);

            $entry->loadMissing('operations.images');
            foreach ($entry->operations as $existingOperation) {
                $existingOperation->images()->delete();
                $existingOperation->delete();
            }
        } else {
            $entry = WorkEntry::create([
                'project_id'   => $data['project_id'],
                'user_id'      => Auth::id(),
                'cable_type'   => $data['cable_type'],
                'work_types'   => $data['work_types'],
                'enclosure_id' => $data['enclosure_id'],
                'street_id'    => $data['street_ids'][0],
                'date'         => $data['date'],
            ]);
        }

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
                    $subOps[$j]['photos'] = collect($sub['existing_photos'] ?? [])
                        ->pluck('path')
                        ->values()
                        ->toArray();

                    $fileKey = "sub_photos_{$i}_{$j}";
                    if ($request->hasFile($fileKey)) {
                        foreach ((array) $request->file($fileKey) as $file) {
                            $subOps[$j]['photos'][] = $file->store('operation-images', 'public');
                        }
                    }

                    unset($subOps[$j]['existing_photos']);
                }

                $operationPayload += [
                    'excavation_type' => $opData['excavation_type'],
                    'dimensions'      => $opData['dimensions'],
                    'meterage'        => $opData['meterage'],
                    'sub_operations'  => $subOps,
                ];
            } elseif ($opData['kind'] === 'upuhivanje') {
                $operationPayload += [
                    'address'     => $opData['address'] ?? null,
                    'upuhano'     => $opData['upuhano'] ?? false,
                    'splajsovano' => $opData['splajsovano'] ?? false,
                    'aktivirano'  => $opData['aktivirano'] ?? false,
                ];
            } else {
                $operationPayload += [
                    'meterage' => $opData['meterage'] ?? null,
                    'address'  => $opData['address'] ?? null,
                ];
            }

            $operation = Operation::create($operationPayload);

            foreach (($opData['existing_images'] ?? []) as $image) {
                OperationImage::create([
                    'operation_id'  => $operation->id,
                    'path'          => $image['path'],
                    'original_name' => $image['name'] ?? basename($image['path']),
                ]);
            }

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

        return response()->json([
            'message' => $request->isMethod('put') ? 'Unos je uspješno izmijenjen.' : 'Unos je uspješno sačuvan.',
            'id'      => $entry->id,
        ], $request->isMethod('put') ? 200 : 201);
    }

    private function ensureOwnedEntry(WorkEntry $entry): void
    {
        abort_if($entry->user_id !== Auth::id(), 403);
    }

    private function ensureEditableEntry(WorkEntry $entry): void
    {
        $this->ensureOwnedEntry($entry);

        abort_unless($entry->created_at?->isToday(), 403, 'Unos se može mijenjati samo isti dan kada je kreiran.');
    }

    private function resolveProjectWorkerOptions(Project $project): array
    {
        $ntvNames = $project->teams
            ->flatMap(fn ($team) => $team->projectNtvs)
            ->pluck('ntv.name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->unique()
            ->values();

        $enclosures = $ntvNames->isEmpty()
            ? collect()
            : Enclosure::query()
                ->whereIn('name', $ntvNames->all())
                ->orderBy('name')
                ->get(['id', 'name']);

        return [
            'cable_types' => $project->cable_type ? [$project->cable_type] : [],
            'enclosures' => $enclosures,
            'enclosure_ids' => $enclosures->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function serializeEntry(WorkEntry $entry, bool $forEdit = false): array
    {
        $entry->loadMissing(['project.city', 'project.streets', 'enclosure', 'street', 'operations.images']);
        $project = $entry->project;

        $operations = $entry->operations->map(function (Operation $op) use ($forEdit) {
            $streetNames = Street::query()
                ->whereIn('id', $op->street_ids ?? [])
                ->orderBy('name')
                ->pluck('name')
                ->values();

            $subOps = collect($op->sub_operations ?? [])->map(function ($sub) use ($forEdit) {
                $photoItems = collect($sub['photos'] ?? [])->map(fn ($path) => [
                    'path' => $path,
                    'url'  => url('storage/' . $path),
                ])->values()->toArray();

                return [
                    'type'            => $sub['type'] ?? 'HP+',
                    'meterage'        => $sub['meterage'] ?? null,
                    'broj_kucice'     => $sub['broj_kucice'] ?? null,
                    'photos'          => array_column($photoItems, 'url'),
                    'existing_photos' => $forEdit ? $photoItems : [],
                ];
            })->toArray();

            $images = $op->images->map(fn ($img) => [
                'path' => $img->path,
                'url'  => url('storage/' . $img->path),
                'name' => $img->original_name,
            ])->values();

            return [
                'id'              => $op->id,
                'kind'            => $op->kind,
                'street_ids'      => $op->street_ids ?? [],
                'streets'         => $streetNames,
                'excavation_type' => $op->excavation_type,
                'dimensions'      => $op->dimensions,
                'meterage'        => $op->meterage,
                'address'         => $op->address,
                'upuhano'        => $op->upuhano,
                'splajsovano'     => $op->splajsovano,
                'aktivirano'      => $op->aktivirano,
                'sub_operations'  => $subOps,
                'images'          => $images->map(fn ($img) => ['url' => $img['url'], 'name' => $img['name']])->values(),
                'existing_images' => $forEdit ? $images->toArray() : [],
            ];
        })->values();

        $streetIds = $operations->first()['street_ids'] ?? ($entry->street_id ? [$entry->street_id] : []);

        return [
            'id'           => $entry->id,
            'date'         => $forEdit ? $entry->date->format('Y-m-d') : $entry->date->format('d.m.Y.'),
            'raw_date'     => $entry->date->format('Y-m-d'),
            'cable_type'   => $entry->cable_type,
            'work_types'   => $entry->work_types,
            'project_id'   => $entry->project_id,
            'enclosure_id' => $entry->enclosure_id,
            'street_ids'   => $streetIds,
            'can_edit'     => $entry->created_at?->isToday() ?? false,
            'project'      => [
                'id'   => $project?->id ?? $entry->project_id,
                'name' => $project?->name ?? 'Obrisan projekat',
                'city' => $project?->city?->name ?? 'Nepoznat grad',
            ],
            'street'       => $entry->street?->name,
            'enclosure'    => $entry->enclosure?->name,
            'operations'   => $operations,
        ];
    }
}
