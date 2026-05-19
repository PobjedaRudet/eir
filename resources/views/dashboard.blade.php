<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">Dobrodošli, {{ auth()->user()->name }}!</flux:heading>

        @if (auth()->user()->isVodja())
            {{-- Vođa projekta --}}
            <div class="grid gap-4 md:grid-cols-2">
                <a href="{{ route('vodja.projekti') }}" wire:navigate
                   class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                          bg-white dark:bg-neutral-900 hover:border-blue-500 hover:shadow-md transition-all">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                            <flux:icon name="folder-open" class="size-6" />
                        </div>
                        <flux:heading size="lg">Projekti</flux:heading>
                    </div>
                    <flux:text class="text-neutral-500 dark:text-neutral-400">
                        Pregledajte i upravljajte svim projektima optičkih kablova.
                    </flux:text>
                </a>

                <a href="{{ route('vodja.novi-projekat') }}" wire:navigate
                   class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                          bg-white dark:bg-neutral-900 hover:border-green-500 hover:shadow-md transition-all">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                            <flux:icon name="plus-circle" class="size-6" />
                        </div>
                        <flux:heading size="lg">Novi projekat</flux:heading>
                    </div>
                    <flux:text class="text-neutral-500 dark:text-neutral-400">
                        Kreirajte novi projekat sa listom ulica za radove.
                    </flux:text>
                </a>
            </div>
        @else
            {{-- Radnik na terenu --}}
            <div class="grid gap-4 md:grid-cols-2">
                <a href="{{ route('radnik.unosi') }}" wire:navigate
                   class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                          bg-white dark:bg-neutral-900 hover:border-blue-500 hover:shadow-md transition-all">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                            <flux:icon name="clipboard-document-list" class="size-6" />
                        </div>
                        <flux:heading size="lg">Moji unosi</flux:heading>
                    </div>
                    <flux:text class="text-neutral-500 dark:text-neutral-400">
                        Pregledajte sve vaše unesene radove na terenu.
                    </flux:text>
                </a>

                <a href="{{ route('radnik.novi-unos') }}" wire:navigate
                   class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                          bg-white dark:bg-neutral-900 hover:border-green-500 hover:shadow-md transition-all">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                            <flux:icon name="plus-circle" class="size-6" />
                        </div>
                        <flux:heading size="lg">Novi unos</flux:heading>
                    </div>
                    <flux:text class="text-neutral-500 dark:text-neutral-400">
                        Evidentirajte radove iskopa i polaganja optičkih kablova.
                    </flux:text>
                </a>
            </div>
        @endif
    </div>
</x-layouts::app>

