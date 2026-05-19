<?php

use App\Models\Operation;
use App\Models\Project;
use App\Models\WorkEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Izvještaj operacija')] class extends Component {

    public ?int $project_id = null;
    public string $date_from = '';
    public string $date_to   = '';

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to   = now()->format('Y-m-d');
    }

    #[Computed]
    public function projects()
    {
        return Project::with('city')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    #[Computed]
    public function entriesByDay()
    {
        $query = WorkEntry::with([
                'worker',
                'project.city',
                'street',
                'enclosure',
                'operations.images',
            ])
            ->whereHas('project', fn ($q) => $q->where('user_id', Auth::id()));

        if ($this->project_id) {
            $query->where('project_id', $this->project_id);
        }

        if ($this->date_from) {
            $query->whereDate('date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('date', '<=', $this->date_to);
        }

        return $query
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn ($entry) => $entry->date->format('Y-m-d'));
    }

    #[Computed]
    public function totalOperations(): int
    {
        return $this->entriesByDay->flatten()->sum(fn ($entry) => $entry->operations->count());
    }

    #[Computed]
    public function totalEntries(): int
    {
        return $this->entriesByDay->flatten()->count();
    }
}; ?>

<div>
    {{-- Page header --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Izvještaj operacija</flux:heading>
            <flux:text class="mt-1 text-neutral-500 dark:text-neutral-400">Pregled izvršenih operacija radnika po danima</flux:text>
        </div>
        @if ($this->entriesByDay->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                <flux:badge variant="outline" icon="calendar-days">
                    {{ $this->entriesByDay->count() }} {{ $this->entriesByDay->count() === 1 ? 'dan' : 'dana' }}
                </flux:badge>
                <flux:badge variant="outline" icon="document-text">
                    {{ $this->totalEntries }} {{ $this->totalEntries === 1 ? 'unos' : 'unosa' }}
                </flux:badge>
                <flux:badge variant="outline" icon="wrench-screwdriver">
                    {{ $this->totalOperations }} {{ $this->totalOperations === 1 ? 'operacija' : 'operacija' }}
                </flux:badge>
            </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-4 mb-6 bg-white dark:bg-neutral-900">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <flux:select wire:model.live="project_id" label="Projekat" placeholder="Svi projekti">
                @foreach ($this->projects as $project)
                    <flux:select.option value="{{ $project->id }}">
                        {{ $project->name }} — {{ $project->city->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live="date_from" label="Datum od" type="date" />
            <flux:input wire:model.live="date_to" label="Datum do" type="date" />
        </div>
    </div>

    {{-- Empty state --}}
    @if ($this->entriesByDay->isEmpty())
        <div class="text-center py-16 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl">
            <flux:icon name="document-magnifying-glass" class="mx-auto size-12 text-neutral-400" />
            <flux:heading class="mt-3">Nema podataka</flux:heading>
            <flux:text class="mt-1 text-neutral-500">Nema operacija za odabrani period i projekat.</flux:text>
        </div>
    @else

        {{-- Days --}}
        <div class="space-y-10">
            @foreach ($this->entriesByDay as $dateKey => $entries)
                @php
                    $day      = \Carbon\Carbon::parse($dateKey);
                    $dayOps   = $entries->sum(fn ($e) => $e->operations->count());
                @endphp

                <div>
                    {{-- Day header --}}
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-1.5 text-white">
                            <flux:icon name="calendar" class="size-4 shrink-0" />
                            <span class="text-sm font-semibold whitespace-nowrap">
                                {{ $day->format('D') }}, {{ $day->format('d.m.Y.') }}
                            </span>
                        </div>
                        <flux:badge variant="subtle">{{ $entries->count() }} {{ $entries->count() === 1 ? 'unos' : 'unosa' }}</flux:badge>
                        <flux:badge variant="subtle" icon="wrench-screwdriver">{{ $dayOps }} op.</flux:badge>
                        <div class="flex-1 h-px bg-neutral-200 dark:bg-neutral-700"></div>
                    </div>

                    {{-- Work entries for this day --}}
                    <div class="space-y-4">
                        @foreach ($entries as $entry)
                            <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-900 overflow-hidden">

                                {{-- Entry header --}}
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-5 py-3 bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon name="user-circle" class="size-4 text-neutral-400 shrink-0" />
                                        <span class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">{{ $entry->worker->name }}</span>
                                    </div>
                                    <span class="text-neutral-300 dark:text-neutral-600 hidden sm:inline">·</span>
                                    <div class="flex items-center gap-1.5">
                                        <flux:icon name="folder-open" class="size-4 text-neutral-400 shrink-0" />
                                        <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ $entry->project->name }}</span>
                                    </div>
                                    @if ($entry->street)
                                        <span class="text-neutral-300 dark:text-neutral-600 hidden sm:inline">·</span>
                                        <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ $entry->street->name }}</span>
                                    @endif
                                    @if ($entry->enclosure)
                                        <span class="text-neutral-300 dark:text-neutral-600 hidden sm:inline">·</span>
                                        <span class="text-sm text-neutral-500 dark:text-neutral-400">Kućište: {{ $entry->enclosure->name }}</span>
                                    @endif
                                    <div class="ml-auto flex flex-wrap items-center gap-1">
                                        <flux:badge size="sm" variant="outline">{{ $entry->cable_type }}</flux:badge>
                                        @foreach ($entry->work_types ?? [] as $wt)
                                            <flux:badge size="sm" variant="subtle">
                                                {{ \App\Models\WorkEntry::WORK_TYPES[$wt] ?? $wt }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Operations list --}}
                                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                    @forelse ($entry->operations as $opIdx => $op)
                                        <div class="px-5 py-4">

                                            {{-- Operation meta --}}
                                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                                <span class="text-xs font-medium text-neutral-400 uppercase tracking-wider mr-1">
                                                    Op. {{ $opIdx + 1 }}
                                                </span>

                                                @if ($op->kind === 'iskop')
                                                    <flux:badge size="sm" color="purple">Iskop</flux:badge>
                                                    @if ($op->excavation_type)
                                                        <flux:badge size="sm" variant="subtle">
                                                            {{ Operation::EXCAVATION_TYPES[$op->excavation_type] ?? $op->excavation_type }}
                                                        </flux:badge>
                                                    @endif
                                                    @if ($op->dimensions)
                                                        <flux:badge size="sm" variant="outline" class="font-mono">{{ $op->dimensions }}</flux:badge>
                                                    @endif
                                                    @if ($op->meterage)
                                                        <flux:badge size="sm" icon="arrows-right-left">{{ number_format($op->meterage, 2) }} m</flux:badge>
                                                    @endif

                                                @elseif ($op->kind === 'upuhivanje')
                                                    <flux:badge size="sm" color="sky">Upuhivanje kabla</flux:badge>
                                                    @if ($op->address)
                                                        <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ $op->address }}</span>
                                                    @endif
                                                    @if ($op->splajsovano)
                                                        <flux:badge size="sm" color="blue">Splajsovano</flux:badge>
                                                    @endif
                                                    @if ($op->aktivirano)
                                                        <flux:badge size="sm" color="green">Aktivirano</flux:badge>
                                                    @endif
                                                @endif
                                            </div>

                                            {{-- HP+ sub-operations --}}
                                            @if ($op->kind === 'iskop' && !empty($op->sub_operations))
                                                <div class="mb-3 space-y-2 pl-2 border-l-2 border-neutral-100 dark:border-neutral-800">
                                                    @foreach ($op->sub_operations as $sub)
                                                        <div class="rounded-lg border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800/50 p-3">
                                                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                                                <flux:badge size="sm" variant="subtle" icon="wrench-screwdriver">{{ $sub['type'] ?? 'HP+' }}</flux:badge>
                                                                @if (!empty($sub['meterage']))
                                                                    <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ number_format((float)$sub['meterage'], 2) }} m</span>
                                                                @endif
                                                                @if (!empty($sub['broj_kucice']))
                                                                    <span class="text-sm text-neutral-500 dark:text-neutral-400">· Kć. <strong>{{ $sub['broj_kucice'] }}</strong></span>
                                                                @endif
                                                            </div>
                                                            {{-- Sub-op photos --}}
                                                            @if (!empty($sub['photos']))
                                                                <div class="flex flex-wrap gap-2 mt-2">
                                                                    @foreach ($sub['photos'] as $photoPath)
                                                                        <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" class="block">
                                                                            <img
                                                                                src="{{ asset('storage/' . $photoPath) }}"
                                                                                class="h-20 w-20 object-cover rounded-lg border border-neutral-200 dark:border-neutral-700 hover:opacity-75 transition-opacity"
                                                                                alt="Fotografija HP+"
                                                                            >
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Operation-level images --}}
                                            @if ($op->images->isNotEmpty())
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($op->images as $img)
                                                        <a href="{{ asset('storage/' . $img->path) }}" target="_blank" class="block">
                                                            <img
                                                                src="{{ asset('storage/' . $img->path) }}"
                                                                class="h-24 w-24 object-cover rounded-lg border border-neutral-200 dark:border-neutral-700 hover:opacity-75 transition-opacity"
                                                                alt="{{ $img->original_name }}"
                                                            >
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif

                                        </div>
                                    @empty
                                        <div class="px-5 py-4 text-sm text-neutral-400 dark:text-neutral-500">Nema operacija.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            @endforeach
        </div>

    @endif
</div>
