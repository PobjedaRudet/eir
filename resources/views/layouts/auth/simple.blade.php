<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        <div class="relative min-h-svh overflow-hidden">
            <div class="absolute inset-0 bg-zinc-950"></div>
            <div class="absolute inset-x-0 top-0 h-72 bg-linear-to-b from-blue-600/15 to-transparent"></div>
            <div class="absolute left-1/2 top-24 h-72 w-72 -translate-x-1/2 rounded-full bg-blue-500/10 blur-3xl"></div>

            <header class="relative z-10 border-b border-white/5 bg-zinc-950/80 backdrop-blur-md">
                <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold tracking-tight text-white" wire:navigate>
                        <span class="flex size-8 items-center justify-center rounded-lg bg-blue-600">
                            <x-app-logo-icon class="size-5 fill-current text-white" />
                        </span>
                        <span>{{ config('app.name', 'Laravel') }}</span>
                    </a>
                </div>
            </header>

            <div class="relative z-10 flex min-h-[calc(100svh-4rem)] items-center justify-center px-6 py-12">
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
