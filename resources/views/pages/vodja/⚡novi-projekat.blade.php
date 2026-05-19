<?php

use App\Models\City;
use App\Models\Project;
use App\Models\Street;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Novi projekat')] class extends Component {
    public string $name = '';
    public string $date = '';
    public ?int $city_id = null;
    public array $street_ids = [];

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    #[Computed]
    public function cities()
    {
        return City::orderBy('name')->get();
    }

    #[Computed]
    public function availableStreets()
    {
        if (! $this->city_id) {
            return collect();
        }

        return Street::where('city_id', $this->city_id)->orderBy('name')->get();
    }

    public function updatedCityId(): void
    {
        $this->street_ids = [];
    }

    public function save(): void
    {
        $this->validate([
            'name'           => 'required|string|max:255',
            'date'           => 'required|date',
            'city_id'        => 'required|exists:cities,id',
            'street_ids'     => 'required|array|min:1',
            'street_ids.*'   => 'exists:streets,id',
        ]);

        $project = Project::create([
            'name'    => $this->name,
            'date'    => $this->date,
            'city_id' => $this->city_id,
            'user_id' => Auth::id(),
        ]);

        $project->streets()->attach($this->street_ids);

        Flux::toast(variant: 'success', text: 'Projekat je uspješno kreiran.');
        $this->redirect(route('vodja.projekti'), navigate: true);
    }
}; ?>

<div class="max-w-2xl">
        <div class="flex items-center gap-3 mb-6">
            <flux:button href="{{ route('vodja.projekti') }}" wire:navigate icon="arrow-left" variant="ghost" size="sm" />
            <flux:heading size="xl">Novi projekat</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input
                wire:model="name"
                label="Naziv projekta"
                type="text"
                placeholder="Unesite naziv projekta"
                required
            />

            <flux:input
                wire:model="date"
                label="Datum"
                type="date"
                required
            />

            <flux:select wire:model.live="city_id" label="Grad" placeholder="Izaberite grad">
                @foreach ($this->cities as $city)
                    <flux:select.option value="{{ $city->id }}">{{ $city->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($city_id)
                @if ($this->availableStreets->isEmpty())
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        Nema ulica za izabrani grad.
                    </flux:callout>
                @else
                    <div>
                        <flux:label>Ulice projekta <flux:badge size="sm" class="ml-1">odaberite jednu ili više</flux:badge></flux:label>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($this->availableStreets as $street)
                                <label class="flex items-center gap-3 cursor-pointer px-3 py-2 rounded-lg border
                                              border-neutral-200 dark:border-neutral-700
                                              hover:bg-neutral-50 dark:hover:bg-neutral-800
                                              has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20
                                              transition-colors">
                                    <input
                                        type="checkbox"
                                        wire:model="street_ids"
                                        value="{{ $street->id }}"
                                        class="rounded border-neutral-300 text-blue-600 focus:ring-blue-500"
                                    >
                                    <span class="text-sm">{{ $street->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('street_ids')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            @endif

            <div class="flex items-center gap-3 pt-2">
                <flux:button variant="primary" type="submit">Sačuvaj projekat</flux:button>
                <flux:button href="{{ route('vodja.projekti') }}" wire:navigate>Odustani</flux:button>
            </div>
        </form>
    </div>
</div>
