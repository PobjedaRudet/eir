<?php

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projekti')] class extends Component {
    #[Computed]
    public function projects()
    {
        return Project::with(['city', 'streets', 'workEntries'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
    }
}; ?>

<div>
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">Projekti</flux:heading>
            <flux:button variant="primary" href="{{ route('vodja.novi-projekat') }}" wire:navigate icon="plus">
                Novi projekat
            </flux:button>
        </div>

        @if ($this->projects->isEmpty())
            <div class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl">
                <flux:icon name="folder-open" class="mx-auto size-12 text-neutral-400" />
                <flux:heading class="mt-3">Nema projekata</flux:heading>
                <flux:text class="mt-1">Kreirajte prvi projekat klikom na dugme iznad.</flux:text>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->projects as $project)
                    <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 bg-white dark:bg-neutral-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="lg">{{ $project->name }}</flux:heading>
                                <flux:text class="mt-1">
                                    <span class="font-medium">{{ $project->city->name }}</span>
                                    &middot;
                                    {{ $project->date->format('d.m.Y.') }}
                                </flux:text>
                            </div>
                            <flux:badge variant="outline">
                                {{ $project->workEntries->count() }} {{ Str::plural('unos', $project->workEntries->count()) }}
                            </flux:badge>
                        </div>

                        @if ($project->streets->isNotEmpty())
                            <div class="mt-3">
                                <flux:text size="sm" class="text-neutral-500 dark:text-neutral-400 mb-1">Ulice:</flux:text>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($project->streets as $street)
                                        <flux:badge size="sm" variant="subtle">{{ $street->name }}</flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
