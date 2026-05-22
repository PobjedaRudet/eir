<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">PM Portal</h1>
      <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Dobrodošli, menadžeru projekata. Odaberite akciju ispod.</p>
    </div>

    <!-- Aktivne funkcije -->
    <div class="mb-6">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3">Projekti</h2>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a
          :href="BASE + '/pm/projekti'"
          class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                 bg-white dark:bg-neutral-900 hover:border-blue-500 hover:shadow-md transition-all"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
              <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
              </svg>
            </div>
            <span class="text-base font-semibold text-zinc-900 dark:text-white">Moji projekti</span>
          </div>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Pregledajte i upravljajte projektima koje ste kreirali.</p>
        </a>

        <a
          :href="BASE + '/pm/novi-projekat'"
          class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                 bg-white dark:bg-neutral-900 hover:border-green-500 hover:shadow-md transition-all"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
              <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
              </svg>
            </div>
            <span class="text-base font-semibold text-zinc-900 dark:text-white">Novi projekat</span>
          </div>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Kreirajte novi projekat sa listom ulica za radove.</p>
        </a>

        <a
          :href="BASE + '/pm/projekti'"
          class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                 bg-white dark:bg-neutral-900 hover:border-orange-500 hover:shadow-md transition-all"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
              <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
              </svg>
            </div>
            <span class="text-base font-semibold text-zinc-900 dark:text-white">Radnici na projektima</span>
          </div>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Upravljajte radnicima dodijeljenim na projekte.</p>
        </a>
      </div>
    </div>

    <!-- Katalog i Odobrenja -->
    <div class="mb-6">
      <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3">Resursi</h2>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a
          :href="BASE + '/pm/oprema'"
          class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                 bg-white dark:bg-neutral-900 hover:border-purple-500 hover:shadow-md transition-all"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
              <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
              </svg>
            </div>
            <span class="text-base font-semibold text-zinc-900 dark:text-white">Katalog resursa</span>
          </div>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Upravljajte katalogom opreme, materijala i servisa.</p>
        </a>

        <a
          :href="BASE + '/pm/odobrenja'"
          class="group flex flex-col gap-3 p-6 rounded-xl border border-neutral-200 dark:border-neutral-700
                 bg-white dark:bg-neutral-900 hover:border-amber-500 hover:shadow-md transition-all"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
              <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
              </svg>
            </div>
            <span class="text-base font-semibold text-zinc-900 dark:text-white">Odobrenja</span>
          </div>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">Pregledajte i odobrite planove resursa koje su podnijeli vođe.</p>
        </a>
      </div>
    </div>

    <!-- Placeholder za buduće funkcije -->
    <div>
      <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3">Uskoro dostupno</h2>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="feature in upcomingFeatures"
          :key="feature.label"
          class="flex flex-col gap-3 p-6 rounded-xl border border-dashed border-neutral-300 dark:border-neutral-700
                 bg-neutral-50 dark:bg-neutral-900/50 opacity-60 cursor-not-allowed"
        >
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500">
              <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
              </svg>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-base font-semibold text-zinc-500 dark:text-zinc-400">{{ feature.label }}</span>
              <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400">Uskoro</span>
            </div>
          </div>
          <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ feature.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { BASE } from '../../utils/base'

const upcomingFeatures = [
  {
    label: 'Izvještaji',
    description: 'Pregled i export izvještaja po projektima i radnicima.',
    icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
  },
  {
    label: 'Statistika',
    description: 'Pregled statusa i napretka projekata u realnom vremenu.',
    icon: 'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z',
  },
]
</script>
