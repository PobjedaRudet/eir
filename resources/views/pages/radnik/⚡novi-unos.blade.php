<?php

use App\Models\Enclosure;
use App\Models\Operation;
use App\Models\OperationImage;
use App\Models\Project;
use App\Models\WorkEntry;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Novi unos')] class extends Component {
    use WithFileUploads;

    // Osnovni podaci
    public ?int $project_id = null;
    public string $cable_type = '';
    public array $work_types = [];
    public ?int $enclosure_id = null;
    public ?int $street_id = null;
    public string $date = '';

    // Operacije
    public array $operations = [];

    // Slike po operaciji: $photos[index] = array of TemporaryUploadedFile
    public array $photos = [];

    // Slike po pod-operaciji: $subPhotos[opIndex][subIndex] = array of TemporaryUploadedFile
    public array $subPhotos = [];

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->addOperation();
    }

    public function addOperation(): void
    {
        $this->operations[] = [
            'kind'            => '',
            // iskop fields
            'excavation_type' => '',
            'dimensions'      => '',
            'meterage'        => '',
            'sub_operations'  => [],
            // upuhivanje fields
            'address'         => '',
            'splajsovano'     => false,
            'aktivirano'      => false,
        ];
        $this->photos[]     = [];
        $this->subPhotos[] = [];
    }

    public function removeOperation(int $index): void
    {
        if (count($this->operations) <= 1) {
            return;
        }
        array_splice($this->operations, $index, 1);
        array_splice($this->photos, $index, 1);
        array_splice($this->subPhotos, $index, 1);
        $this->operations = array_values($this->operations);
        $this->photos     = array_values($this->photos);
        $this->subPhotos  = array_values($this->subPhotos);
    }

    public function addSubOperation(int $opIndex): void
    {
        $this->operations[$opIndex]['sub_operations'][] = [
            'type'        => 'HP+',
            'meterage'    => '',
            'broj_kucice' => '',
        ];
        $this->subPhotos[$opIndex][] = [];
    }

    public function removeSubOperation(int $opIndex, int $subIndex): void
    {
        array_splice($this->operations[$opIndex]['sub_operations'], $subIndex, 1);
        $this->operations[$opIndex]['sub_operations'] = array_values(
            $this->operations[$opIndex]['sub_operations']
        );
        if (isset($this->subPhotos[$opIndex])) {
            array_splice($this->subPhotos[$opIndex], $subIndex, 1);
            $this->subPhotos[$opIndex] = array_values($this->subPhotos[$opIndex]);
        }
    }

    public function updatedProjectId(): void
    {
        $this->street_id = null;
    }

    #[Computed]
    public function projects()
    {
        return Project::with('city')->latest()->get();
    }

    #[Computed]
    public function availableStreets()
    {
        if (! $this->project_id) {
            return collect();
        }

        return Project::find($this->project_id)?->streets ?? collect();
    }

    #[Computed]
    public function enclosures()
    {
        return Enclosure::orderBy('name')->get();
    }

    public function save(): void
    {
        $this->validate([
            'project_id'                           => 'required|exists:projects,id',
            'cable_type'                           => 'required|string|max:50',
            'work_types'                           => 'required|array|min:1',
            'work_types.*'                         => 'in:uvlačenje,iskop,otvaranje_rupa',
            'enclosure_id'                         => 'required|exists:enclosures,id',
            'street_id'                            => 'required|exists:streets,id',
            'date'                                 => 'required|date',
            'operations'                           => 'required|array|min:1',
            'operations.*.kind'                    => 'required|in:iskop,upuhivanje',
            'operations.*.excavation_type'         => 'nullable|in:iskop,iskop_flaster,iskop_asfalt,raketa',
            'operations.*.dimensions'              => 'nullable|in:15x45,15x60,30x45,30x60',
            'operations.*.meterage'                => 'nullable|numeric|min:0.01',
            'operations.*.sub_operations'                   => 'nullable|array',
            'operations.*.sub_operations.*.type'              => 'in:HP+',
            'operations.*.sub_operations.*.meterage'          => 'nullable|numeric|min:0.01',
            'operations.*.sub_operations.*.broj_kucice'       => 'nullable|string|max:50',
            'operations.*.address'                 => 'nullable|string|max:255',
            'operations.*.splajsovano'             => 'nullable|boolean',
            'operations.*.aktivirano'              => 'nullable|boolean',
            'photos.*.*'                           => 'nullable|image|max:10240',
            'subPhotos.*.*.*'                      => 'nullable|image|max:10240',
        ]);

        // Kind-specific required field check
        foreach ($this->operations as $i => $op) {
            if ($op['kind'] === 'iskop') {
                if (empty($op['excavation_type'])) {
                    $this->addError("operations.{$i}.excavation_type", 'Vrsta iskopa je obavezna.');
                }
                if (empty($op['dimensions'])) {
                    $this->addError("operations.{$i}.dimensions", 'Dimenzije su obavezne.');
                }
                if (empty($op['meterage'])) {
                    $this->addError("operations.{$i}.meterage", 'Metraža je obavezna.');
                }
            } elseif ($op['kind'] === 'upuhivanje') {
                if (empty($op['address'])) {
                    $this->addError("operations.{$i}.address", 'Adresa je obavezna.');
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $entry = WorkEntry::create([
            'project_id'   => $this->project_id,
            'user_id'      => Auth::id(),
            'cable_type'   => $this->cable_type,
            'work_types'   => $this->work_types,
            'enclosure_id' => $this->enclosure_id,
            'street_id'    => $this->street_id,
            'date'         => $this->date,
        ]);

        foreach ($this->operations as $i => $opData) {
            $operationData = ['work_entry_id' => $entry->id, 'kind' => $opData['kind']];

            if ($opData['kind'] === 'iskop') {
                // Embed sub-operation photos into the JSON before saving
                $subOps = $opData['sub_operations'] ?? [];
                foreach ($this->subPhotos[$i] ?? [] as $j => $subPhotoSet) {
                    if (! empty($subPhotoSet)) {
                        $files = is_array($subPhotoSet) ? $subPhotoSet : [$subPhotoSet];
                        $paths = [];
                        foreach ($files as $file) {
                            if ($file) {
                                $paths[] = $file->store('operation-images', 'public');
                            }
                        }
                        if (! empty($paths) && isset($subOps[$j])) {
                            $subOps[$j]['photos'] = $paths;
                        }
                    }
                }
                $operationData += [
                    'excavation_type' => $opData['excavation_type'],
                    'dimensions'      => $opData['dimensions'],
                    'meterage'        => $opData['meterage'],
                    'sub_operations'  => $subOps,
                ];
            } else {
                $operationData += [
                    'address'     => $opData['address'],
                    'splajsovano' => $opData['splajsovano'] ?? false,
                    'aktivirano'  => $opData['aktivirano'] ?? false,
                ];
            }

            $operation = Operation::create($operationData);

            // Operation-level photos — available for all operation types
            if (! empty($this->photos[$i])) {
                $files = is_array($this->photos[$i]) ? $this->photos[$i] : [$this->photos[$i]];
                foreach ($files as $file) {
                    if ($file) {
                        $path = $file->store('operation-images', 'public');
                        OperationImage::create([
                            'operation_id'  => $operation->id,
                            'path'          => $path,
                            'original_name' => $file->getClientOriginalName(),
                        ]);
                    }
                }
            }
        }

        Flux::toast(variant: 'success', text: 'Unos je uspješno sačuvan.');
        $this->redirect(route('radnik.unosi'), navigate: true);
    }
}; ?>

<div class="max-w-3xl">
        <div class="flex items-center gap-3 mb-6">
            <flux:button href="{{ route('radnik.unosi') }}" wire:navigate icon="arrow-left" variant="ghost" size="sm" />
            <flux:heading size="xl">Novi unos radova</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">

            {{-- Osnovni podaci --}}
            <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 space-y-5">
                <flux:heading size="lg">Osnovni podaci</flux:heading>

                {{-- Projekat --}}
                <flux:select wire:model.live="project_id" label="Projekat" placeholder="Izaberite projekat">
                    @foreach ($this->projects as $project)
                        <flux:select.option value="{{ $project->id }}">
                            {{ $project->name }} — {{ $project->city->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Vrsta kabla (identifikator) --}}
                <flux:select wire:model="cable_type" label="Vrsta kabla" placeholder="Izaberite vrstu kabla">
                    @foreach (\App\Models\WorkEntry::CABLE_TYPES as $cableType)
                        <flux:select.option value="{{ $cableType }}">{{ $cableType }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Vrste radova (checkboxes) --}}
                <div>
                    <flux:label>Vrste radova <flux:badge size="sm" class="ml-1">odaberite jednu ili više</flux:badge></flux:label>
                    <div class="mt-2 flex flex-col sm:flex-row gap-2">
                        @foreach (\App\Models\WorkEntry::WORK_TYPES as $value => $label)
                            <label class="flex-1 flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border
                                          border-neutral-200 dark:border-neutral-700
                                          hover:bg-neutral-50 dark:hover:bg-neutral-800
                                          has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20
                                          transition-colors">
                                <input type="checkbox" wire:model="work_types" value="{{ $value }}" class="rounded text-blue-600">
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('work_types')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kućište --}}
                <flux:select wire:model="enclosure_id" label="Kućište" placeholder="Izaberite kućište">
                    @foreach ($this->enclosures as $enclosure)
                        <flux:select.option value="{{ $enclosure->id }}">{{ $enclosure->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Datum (auto-set) --}}
                <flux:input wire:model="date" label="Datum" type="date" required />

                {{-- Ulica --}}
                @if ($project_id)
                    <flux:select wire:model="street_id" label="Ulica" placeholder="Izaberite ulicu">
                        @foreach ($this->availableStreets as $street)
                            <flux:select.option value="{{ $street->id }}">{{ $street->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <div>
                        <flux:label>Ulica</flux:label>
                        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Prvo izaberite projekat.</p>
                    </div>
                @endif
            </div>

            {{-- Operacije --}}
            <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Operacije</flux:heading>
                    <flux:button type="button" wire:click="addOperation" icon="plus" size="sm">
                        Dodaj operaciju
                    </flux:button>
                </div>

                <div class="space-y-6">
                    @foreach ($operations as $i => $op)
                        <div class="border border-neutral-100 dark:border-neutral-800 rounded-xl p-4 bg-neutral-50 dark:bg-neutral-800/50">
                            <div class="flex items-center justify-between mb-4">
                                <flux:heading size="base">Operacija {{ $i + 1 }}</flux:heading>
                                @if (count($operations) > 1)
                                    <flux:button
                                        type="button"
                                        wire:click="removeOperation({{ $i }})"
                                        icon="trash"
                                        size="sm"
                                        variant="ghost"
                                        class="text-red-500 hover:text-red-700"
                                    />
                                @endif
                            </div>

                            {{-- Vrsta operacije --}}
                            <div class="mb-4">
                                <flux:label>Vrsta operacije</flux:label>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    @foreach (['iskop' => 'Iskop', 'upuhivanje' => 'Upuhivanje kabla'] as $kindVal => $kindLabel)
                                        <label class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border
                                                      border-neutral-200 dark:border-neutral-700
                                                      hover:bg-white dark:hover:bg-neutral-700
                                                      has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20
                                                      transition-colors">
                                            <input type="radio" wire:model.live="operations.{{ $i }}.kind" value="{{ $kindVal }}" class="text-blue-600">
                                            <span class="text-sm font-medium">{{ $kindLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error("operations.{$i}.kind")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-4">

                                {{-- ======= ISKOP ======= --}}
                                @if ($op['kind'] === 'iskop')

                                    <div>
                                        <flux:label>Vrsta iskopa</flux:label>
                                        <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            @foreach (['iskop' => 'Iskop', 'iskop_flaster' => 'Iskop flaster', 'iskop_asfalt' => 'Iskop asfalt', 'raketa' => 'Raketa'] as $value => $label)
                                                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border
                                                              border-neutral-200 dark:border-neutral-600
                                                              hover:bg-white dark:hover:bg-neutral-700
                                                              has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/30
                                                              transition-colors text-sm">
                                                    <input type="radio" wire:model="operations.{{ $i }}.excavation_type" value="{{ $value }}" class="text-blue-600">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @error("operations.{$i}.excavation_type")
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <flux:label>Dimenzije</flux:label>
                                        <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            @foreach (['15x45', '15x60', '30x45', '30x60'] as $dim)
                                                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border
                                                              border-neutral-200 dark:border-neutral-600
                                                              hover:bg-white dark:hover:bg-neutral-700
                                                              has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/30
                                                              transition-colors text-sm font-mono">
                                                    <input type="radio" wire:model="operations.{{ $i }}.dimensions" value="{{ $dim }}" class="text-blue-600">
                                                    {{ $dim }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @error("operations.{$i}.dimensions")
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <flux:input
                                        wire:model="operations.{{ $i }}.meterage"
                                        label="Metraža (m)"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="npr. 12.50"
                                    />

                                    {{-- Podoperacije --}}
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <flux:label>Podoperacije</flux:label>
                                            <flux:button type="button" wire:click="addSubOperation({{ $i }})" size="sm" icon="plus" variant="ghost">
                                                Dodaj HP+
                                            </flux:button>
                                        </div>
                                        @if (!empty($op['sub_operations']))
                                            <div class="mt-2 space-y-3">
                                                @foreach ($op['sub_operations'] as $j => $sub)
                                                    <div class="rounded-lg bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 p-3">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <div class="flex items-center gap-2">
                                                                <flux:icon name="wrench-screwdriver" class="size-4 text-neutral-400" />
                                                                <span class="text-sm font-semibold">HP+</span>
                                                            </div>
                                                            <flux:button type="button" wire:click="removeSubOperation({{ $i }}, {{ $j }})" icon="x-mark" size="sm" variant="ghost" class="text-red-500 hover:text-red-700" />
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-3">
                                                            <flux:input
                                                                wire:model="operations.{{ $i }}.sub_operations.{{ $j }}.meterage"
                                                                label="Metraža (m)"
                                                                type="number"
                                                                step="0.01"
                                                                min="0.01"
                                                                placeholder="npr. 5.50"
                                                            />
                                                            <flux:input
                                                                wire:model="operations.{{ $i }}.sub_operations.{{ $j }}.broj_kucice"
                                                                label="Broj kućice"
                                                                type="text"
                                                                placeholder="npr. 12A"
                                                            />
                                                        </div>
                                                        {{-- Slike pod-operacije --}}
                                                        <div class="mt-3 pt-3 border-t border-neutral-100 dark:border-neutral-800">
                                                            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-2">Fotografije HP+</p>
                                                            <label class="flex items-center justify-center gap-2 w-full h-16 border-2 border-dashed
                                                                           border-neutral-200 dark:border-neutral-700 rounded-lg cursor-pointer
                                                                           hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors text-sm text-neutral-400">
                                                                <flux:icon name="camera" class="size-4" />
                                                                <span>Dodaj fotografije</span>
                                                                <input type="file" wire:model="subPhotos.{{ $i }}.{{ $j }}" accept="image/*" multiple class="hidden">
                                                            </label>
                                                            <div wire:loading wire:target="subPhotos.{{ $i }}.{{ $j }}" class="mt-1 text-xs text-blue-600">Učitavanje...</div>
                                                            @if (!empty($subPhotos[$i][$j] ?? []))
                                                                @php $subImgs = is_array($subPhotos[$i][$j]) ? $subPhotos[$i][$j] : [$subPhotos[$i][$j]]; @endphp
                                                                <div class="mt-1 flex flex-wrap gap-1">
                                                                    @foreach ($subImgs as $sImg)
                                                                        @if ($sImg)
                                                                            <img src="{{ $sImg->temporaryUrl() }}" class="h-14 w-14 object-cover rounded border border-neutral-200 dark:border-neutral-600" alt="Preview">
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="mt-1 text-sm text-neutral-400 dark:text-neutral-500">Nema podoperacija.</p>
                                        @endif
                                    </div>

                                {{-- ======= UPUHIVANJE KABLA ======= --}}
                                @elseif ($op['kind'] === 'upuhivanje')

                                    <flux:input
                                        wire:model="operations.{{ $i }}.address"
                                        label="Adresa kuće"
                                        type="text"
                                        placeholder="npr. Titova ulica 12"
                                    />
                                    @error("operations.{$i}.address")
                                        <p class="-mt-3 text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <label class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border
                                                      border-neutral-200 dark:border-neutral-700
                                                      hover:bg-neutral-50 dark:hover:bg-neutral-800
                                                      has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20
                                                      transition-colors flex-1">
                                            <input type="checkbox" wire:model="operations.{{ $i }}.splajsovano" class="rounded text-blue-600">
                                            <span class="text-sm font-medium">Splajsovano</span>
                                        </label>

                                        <label class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border
                                                      border-neutral-200 dark:border-neutral-700
                                                      hover:bg-neutral-50 dark:hover:bg-neutral-800
                                                      has-[:checked]:border-green-500 has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-900/20
                                                      transition-colors flex-1">
                                            <input type="checkbox" wire:model="operations.{{ $i }}.aktivirano" class="rounded text-green-600">
                                            <span class="text-sm font-medium">Aktivirano</span>
                                        </label>
                                    </div>

                                @endif

                                {{-- Slike operacije — dostupno za sve vrste --}}
                                @if ($op['kind'] !== '')
                                    <div>
                                        <flux:label>Fotografije operacije</flux:label>
                                        <div class="mt-2">
                                            <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed
                                                           border-neutral-300 dark:border-neutral-600 rounded-lg cursor-pointer
                                                           hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
                                                <div class="flex flex-col items-center gap-1 text-sm text-neutral-500">
                                                    <flux:icon name="camera" class="size-6" />
                                                    <span>Kliknite za dodavanje fotografija</span>
                                                    <span class="text-xs">PNG, JPG, JPEG, WEBP do 10MB</span>
                                                </div>
                                                <input type="file" wire:model="photos.{{ $i }}" accept="image/*" multiple class="hidden">
                                            </label>
                                            <div wire:loading wire:target="photos.{{ $i }}" class="mt-2 text-sm text-blue-600">Učitavanje slika...</div>
                                            @if (! empty($photos[$i]))
                                                @php $imgs = is_array($photos[$i]) ? $photos[$i] : [$photos[$i]]; @endphp
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach ($imgs as $img)
                                                        @if ($img)
                                                            <img src="{{ $img->temporaryUrl() }}" class="h-20 w-20 object-cover rounded-lg border border-neutral-200 dark:border-neutral-600" alt="Preview">
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                            @error("photos.{$i}.*")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">
                    <span wire:loading.remove>Sačuvaj unos</span>
                    <span wire:loading>Čuvanje...</span>
                </flux:button>
                <flux:button href="{{ route('radnik.unosi') }}" wire:navigate>Odustani</flux:button>
            </div>
        </form>
    </div>
</div>
