<?php

use App\Models\WorkEntry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Moji unosi')] class extends Component {
    #[Computed]
    public function entries()
    {
        return WorkEntry::with(['project.city', 'enclosure', 'street', 'operations.images'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
    }
}; ?>

<div>
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">Moji unosi radova</flux:heading>
            <flux:button variant="primary" href="{{ route('radnik.novi-unos') }}" wire:navigate icon="plus">
                Novi unos
            </flux:button>
        </div>

        @if ($this->entries->isEmpty())
            <div class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl">
                <flux:icon name="clipboard-document-list" class="mx-auto size-12 text-neutral-400" />
                <flux:heading class="mt-3">Nema unosa</flux:heading>
                <flux:text class="mt-1">Kreirajte prvi unos klikom na dugme iznad.</flux:text>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->entries as $entry)
                    @php
                        $workTypeLabels = ['uvlačenje' => 'Uvlačenje', 'iskop' => 'Iskop', 'otvaranje_rupa' => 'Otvaranje rupa'];
                        $excavationLabels = ['iskop' => 'Iskop', 'iskop_flaster' => 'Iskop flaster', 'iskop_asfalt' => 'Iskop asfalt', 'raketa' => 'Raketa'];
                    @endphp
                    <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 bg-white dark:bg-neutral-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="lg">{{ $entry->project->name }}</flux:heading>
                                <flux:text class="mt-1">
                                    {{ $entry->project->city->name }} &middot; {{ $entry->street->name }}
                                    &middot; {{ $entry->date->format('d.m.Y.') }}
                                </flux:text>
                            </div>
                            <flux:badge>{{ $entry->cable_type }}</flux:badge>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <flux:text size="sm" class="text-neutral-500 dark:text-neutral-400">Radovi:</flux:text>
                            @foreach ((array) $entry->work_types as $wt)
                                <flux:badge size="sm" variant="subtle">{{ $workTypeLabels[$wt] ?? $wt }}</flux:badge>
                            @endforeach
                            <flux:text size="sm" class="text-neutral-500 dark:text-neutral-400 ml-2">Kućište:</flux:text>
                            <span class="text-sm font-medium">{{ $entry->enclosure->name }}</span>
                        </div>

                        @if ($entry->operations->isNotEmpty())
                            <div class="mt-4 space-y-2">
                                <flux:text size="sm" class="font-medium text-neutral-500 dark:text-neutral-400">
                                    Operacije ({{ $entry->operations->count() }}):
                                </flux:text>
                                @foreach ($entry->operations as $op)
                                    <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-neutral-50 dark:bg-neutral-800">
                                        @if ($op->kind === 'upuhivanje')
                                            <div class="flex items-center gap-3 text-sm">
                                                <flux:badge size="sm" variant="outline" color="purple">Upuhivanje</flux:badge>
                                                <span class="text-neutral-600 dark:text-neutral-400">{{ $op->address }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if ($op->splajsovano)
                                                    <flux:badge size="sm" color="blue">Splajsovano</flux:badge>
                                                @endif
                                                @if ($op->aktivirano)
                                                    <flux:badge size="sm" color="green">Aktivirano</flux:badge>
                                                @endif
                                            </div>
                                        @else
                                            <div class="flex items-center gap-3 text-sm">
                                                <flux:badge size="sm" variant="outline">{{ $excavationLabels[$op->excavation_type] ?? $op->excavation_type }}</flux:badge>
                                                <span>{{ $op->dimensions }}</span>
                                                <span class="font-medium">{{ $op->meterage }} m</span>
                                            </div>
                                            <div class="flex items-center flex-wrap gap-2">
                                                @if (!empty($op->sub_operations))
                                                    @foreach ($op->sub_operations as $sub)
                                                        <flux:badge size="sm" variant="subtle" icon="wrench-screwdriver">
                                                            {{ $sub['type'] }}
                                                            @if (!empty($sub['meterage']))
                                                                · {{ $sub['meterage'] }} m
                                                            @endif
                                                            @if (!empty($sub['broj_kucice']))
                                                                · kć. {{ $sub['broj_kucice'] }}
                                                            @endif
                                                        </flux:badge>
                                                    @endforeach
                                                @endif
                                                @if ($op->images->isNotEmpty())
                                                    <flux:badge size="sm" icon="photo">{{ $op->images->count() }}</flux:badge>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
