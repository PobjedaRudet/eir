<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} â€” Evidencija iskopa optiÄŤkih kablova</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxStyles
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">

        {{-- Navigation --}}
        <header class="fixed top-0 inset-x-0 z-50 border-b border-white/5 bg-zinc-950/80 backdrop-blur-md">
            <div class="mx-auto max-w-5xl px-6 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center size-8 rounded-lg bg-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="size-5 text-white">
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                            <path stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                d="M12 2v3M12 19v3M2 12h3M19 12h3M4.93 4.93l2.12 2.12M16.95 16.95l2.12 2.12M4.93 19.07l2.12-2.12M16.95 7.05l2.12-2.12"/>
                            <circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="1.5" fill="none" opacity="0.4"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-lg tracking-tight">{{ config('app.name') }}</span>
                </div>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-sm font-medium transition-colors">
                                Otvori aplikaciju
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 rounded-lg text-sm font-medium text-zinc-300 hover:text-white transition-colors">
                                Prijava
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-sm font-medium transition-colors">
                                    Registracija
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        {{-- Hero --}}
        <main class="pt-32 pb-24 px-6">
            <div class="mx-auto max-w-3xl text-center">

                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-xs font-medium tracking-wide uppercase mb-8">
                    <span class="size-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                    Sistem za upravljanje radovima
                </div>

                <h1 class="text-5xl sm:text-6xl font-bold tracking-tight leading-tight mb-6">
                    Evidencija iskopa<br>
                    <span class="text-blue-400">optiÄŤkih kablova</span>
                </h1>

                <p class="text-lg text-zinc-400 max-w-xl mx-auto mb-10 leading-relaxed">
                    Digitalna platforma za praÄ‡enje radova na terenu. VoÄ‘e projekata upravljaju projektima,
                    radnici evidentiraju iskop, uvlaÄŤenje i upuhivanje kabla u realnom vremenu.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 font-semibold text-sm transition-colors shadow-lg shadow-blue-600/25">
                            Otvori aplikaciju â†’
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 font-semibold text-sm transition-colors shadow-lg shadow-blue-600/25">
                            Prijavite se â†’
                        </a>
                    @endauth
                </div>
            </div>
        </main>

        {{-- Feature grid --}}
        <section class="pb-24 px-6">
            <div class="mx-auto max-w-4xl grid grid-cols-1 sm:grid-cols-3 gap-4">

                <div class="rounded-2xl border border-white/8 bg-white/3 p-6 hover:bg-white/5 transition-colors">
                    <div class="size-10 rounded-xl bg-blue-600/20 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white mb-1.5">Upravljanje projektima</h3>
                    <p class="text-sm text-zinc-400 leading-relaxed">VoÄ‘e projekata kreiraju projekte, definiĹˇu ulice i dodjeljuju radove timovima.</p>
                </div>

                <div class="rounded-2xl border border-white/8 bg-white/3 p-6 hover:bg-white/5 transition-colors">
                    <div class="size-10 rounded-xl bg-green-600/20 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white mb-1.5">Evidencija radova</h3>
                    <p class="text-sm text-zinc-400 leading-relaxed">Radnici biljeĹľe iskop, uvlaÄŤenje i upuhivanje kabla s fotografijama i mjerama.</p>
                </div>

                <div class="rounded-2xl border border-white/8 bg-white/3 p-6 hover:bg-white/5 transition-colors">
                    <div class="size-10 rounded-xl bg-purple-600/20 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-white mb-1.5">Pregled i kontrola</h3>
                    <p class="text-sm text-zinc-400 leading-relaxed">Pregled svih unosa po projektu, ulici i operateru s filtiranjem i statusima.</p>
                </div>

            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-white/5 py-6 px-6 text-center">
            <p class="text-xs text-zinc-600">Â© {{ date('Y') }} {{ config('app.name') }}. Sva prava pridrĹľana.</p>
        </footer>

        @fluxScripts
    </body>
</html>
