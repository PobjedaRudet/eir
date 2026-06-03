<?php

use App\Models\Enclosure;
use App\Models\Ntv;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $codes = [
            '8V8001',
            '8V8002',
            '8V8003',
            '8V8004',
            '8V8105',
            '8V8106',
            '8V8107',
            '8V8108',
            '8V8109',
        ];

        $this->syncEnclosures($codes);
        $this->syncNtvs($codes);
    }

    public function down(): void
    {
    }

    private function syncEnclosures(array $codes): void
    {
        $assignedIds = DB::table('project_worker')
            ->whereNotNull('enclosure_ids')
            ->pluck('enclosure_ids')
            ->flatMap(function ($json) {
                $decoded = json_decode($json, true);

                return is_array($decoded) ? $decoded : [];
            })
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $referencedIds = DB::table('work_entries')
            ->whereNotNull('enclosure_id')
            ->pluck('enclosure_id')
            ->map(fn ($id) => (int) $id)
            ->merge($assignedIds)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->syncNamedModels(Enclosure::class, $codes, $referencedIds);
    }

    private function syncNtvs(array $codes): void
    {
        $referencedIds = DB::table('project_ntvs')
            ->pluck('ntv_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->syncNamedModels(Ntv::class, $codes, $referencedIds);
    }

    private function syncNamedModels(string $modelClass, array $names, array $referencedIds): void
    {
        $existing = $modelClass::query()->orderBy('id')->get();

        $preferred = $existing->filter(fn ($model) => in_array((int) $model->id, $referencedIds, true))->values();
        $available = $existing->reject(fn ($model) => in_array((int) $model->id, $referencedIds, true))->values();
        $pool = $preferred->concat($available)->values();

        foreach ($names as $index => $name) {
            if (isset($pool[$index])) {
                $model = $pool[$index];
                if ($model->name !== $name) {
                    $model->forceFill(['name' => $name])->save();
                }
                continue;
            }

            $modelClass::query()->create(['name' => $name]);
        }

        $keepers = $modelClass::query()->whereIn('name', $names)->pluck('id')->all();

        $modelClass::query()
            ->whereNotIn('id', $keepers)
            ->whereNotIn('id', $referencedIds)
            ->delete();
    }
};
