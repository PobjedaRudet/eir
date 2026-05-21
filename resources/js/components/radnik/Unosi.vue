<template>
  <div>
    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Moji unosi radova</h1>
        <p v-if="!loading && entries.length" class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
          {{ entries.length }} {{ entries.length === 1 ? 'unos' : entries.length < 5 ? 'unosa' : 'unosa' }}
        </p>
      </div>
      <a
        :href="BASE + '/radnik/novi-unos'"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 active:bg-blue-800 transition-colors shadow-sm"
      >
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
        </svg>
        Novi unos
      </a>
    </div>

    <!-- Filters -->
    <div v-if="!loading && entries.length" class="mb-5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 py-3 shadow-sm">
      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Project filter -->
        <div class="flex-1 min-w-0">
          <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Projekat</label>
          <select v-model="filter.project_id" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-sm text-zinc-800 dark:text-zinc-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Svi projekti</option>
            <option v-for="p in filterProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <!-- Date from -->
        <div class="w-full sm:w-40">
          <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Od datuma</label>
          <input type="date" v-model="filter.date_from" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-sm text-zinc-800 dark:text-zinc-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <!-- Date to -->
        <div class="w-full sm:w-40">
          <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Do datuma</label>
          <input type="date" v-model="filter.date_to" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-sm text-zinc-800 dark:text-zinc-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <!-- Clear button -->
        <div class="flex items-end">
          <button
            v-if="hasFilter"
            type="button"
            @click="clearFilter"
            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 border border-red-200 dark:border-red-800 transition-colors"
          >
            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            </svg>
            Resetuj
          </button>
        </div>
      </div>
      <!-- Result count -->
      <p v-if="hasFilter" class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
        Prikazano {{ filteredEntries.length }} od {{ entries.length }} unosa
      </p>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-4">
      <div v-for="n in 3" :key="n" class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-5 animate-pulse">
        <div class="flex justify-between mb-3">
          <div class="space-y-2">
            <div class="h-5 w-48 rounded bg-neutral-200 dark:bg-neutral-700"></div>
            <div class="h-3.5 w-32 rounded bg-neutral-100 dark:bg-neutral-800"></div>
          </div>
          <div class="h-6 w-20 rounded-full bg-neutral-100 dark:bg-neutral-800"></div>
        </div>
        <div class="h-3 w-full rounded bg-neutral-100 dark:bg-neutral-800 mt-4"></div>
        <div class="h-3 w-3/4 rounded bg-neutral-100 dark:bg-neutral-800 mt-2"></div>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="filteredEntries.length === 0"
      class="text-center py-16 border-2 border-dashed border-neutral-200 dark:border-neutral-700 rounded-2xl"
    >
      <div class="mx-auto size-14 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-4">
        <svg class="size-7 text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
        </svg>
      </div>
      <p class="text-base font-semibold text-zinc-800 dark:text-zinc-200">{{ hasFilter ? 'Nema rezultata' : 'Nema unosa' }}</p>
      <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ hasFilter ? 'Nijedan unos ne odgovara odabranim filterima.' : 'Kreirajte prvi unos klikom na dugme iznad.' }}</p>
    </div>

    <!-- Entry cards -->
    <div v-else class="space-y-6">
      <div
        v-for="entry in filteredEntries"
        :key="entry.id"
        class="rounded-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden shadow-sm"
      >
        <!-- Entry header -->
        <div class="px-5 py-4 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/60 dark:bg-neutral-800/40">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 class="text-base font-bold text-zinc-900 dark:text-white truncate">{{ entry.project.name }}</h2>
              <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span class="flex items-center gap-1">
                  <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
                  </svg>
                  {{ entry.project.city }}
                </span>
                <span class="text-neutral-300 dark:text-neutral-600">·</span>
                <span class="flex items-center gap-1">
                  <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                  </svg>
                  {{ entry.date }}
                </span>
                <span v-if="entry.street" class="text-neutral-300 dark:text-neutral-600">·</span>
                <span v-if="entry.street">{{ entry.street }}</span>
              </div>
            </div>
            <span class="shrink-0 px-2.5 py-1 text-xs font-semibold rounded-full bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 font-mono tracking-wide">
              {{ entry.cable_type }}
            </span>
          </div>

          <!-- Work types & enclosure row -->
          <div class="mt-3 flex flex-wrap items-center gap-2">
            <span
              v-for="wt in entry.work_types"
              :key="wt"
              class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
            >{{ workTypeLabels[wt] ?? wt }}</span>
            <span v-if="entry.enclosure" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
              <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M1 8.25a1.25 1.25 0 1 1 2.5 0v7.5a1.25 1.25 0 1 1-2.5 0v-7.5ZM11 3V1.7c0-.268.14-.526.395-.607A2 2 0 0 1 14 3c0 .995-.182 1.948-.514 2.826-.204.54.166 1.174.744 1.174h2.52c1.243 0 2.261 1.01 2.146 2.247a23.864 23.864 0 0 1-1.341 5.974C17.153 16.323 16.072 17 14.9 17h-3.192a3 3 0 0 1-1.341-.317l-2.734-1.366A3 3 0 0 0 6.292 15H5V8h.963c.685 0 1.258-.483 1.612-1.068a4.011 4.011 0 0 1 2.166-1.73c.432-.143.853-.386 1.011-.814.16-.432.248-.9.248-1.388Z" />
              </svg>
              {{ entry.enclosure }}
            </span>
          </div>
        </div>

        <!-- Operations -->
        <div class="divide-y-2 divide-neutral-200 dark:divide-neutral-700">
          <div
            v-for="(op, idx) in entry.operations"
            :key="op.id"
            class="p-5"
          >
            <!-- Operation header line -->
            <div class="flex items-start gap-3 flex-wrap">
              <!-- Index badge -->
              <span class="shrink-0 mt-0.5 flex size-6 items-center justify-center rounded-full text-xs font-bold"
                :class="op.kind === 'upuhivanje'
                  ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300'
                  : 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'"
              >{{ idx + 1 }}</span>

              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <!-- Kind badge -->
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg"
                    :class="op.kind === 'upuhivanje'
                      ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-800'
                      : 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800'"
                  >
                    <svg v-if="op.kind === 'upuhivanje'" class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M3.505 2.365A41.369 41.369 0 0 1 9 2c1.863 0 3.697.124 5.495.365 1.247.167 2.18 1.108 2.435 2.268a4.45 4.45 0 0 0-.577-.069 43.141 43.141 0 0 0-4.706 0C9.229 4.696 7.5 6.727 7.5 8.998v2.24c0 1.413.67 2.735 1.8 3.575l-3.898 3.301a.75.75 0 0 1-1.202-.6V4.268a2.195 2.195 0 0 0-1.695-2.033Z" />
                      <path d="M10.5 8.998c0-1.856 1.458-3.44 3.352-3.557a41.48 41.48 0 0 1 4.296 0C20.042 5.558 21.5 7.142 21.5 9v2.24c0 1.413-.67 2.735-1.8 3.575l-1.9 1.61V18a.75.75 0 0 1-1.5 0v-1.7l-2.22.62a.5.5 0 0 1-.28 0l-2.22-.62V18a.75.75 0 0 1-1.5 0v-1.575l-1.9-1.61A4.49 4.49 0 0 1 6.5 11.24V9c0-1.856 1.458-3.44 3.352-3.557a41.48 41.48 0 0 1 4.296 0" />
                    </svg>
                    <svg v-else class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M5.75 3a.75.75 0 0 0-.75.75v12.5c0 .414.336.75.75.75h8.5a.75.75 0 0 0 .75-.75V3.75a.75.75 0 0 0-.75-.75h-8.5Z" />
                    </svg>
                    {{ op.kind === 'upuhivanje' ? 'Upuhivanje kabla' : 'Iskop' }}
                  </span>

                  <!-- Iskop details -->
                  <template v-if="op.kind === 'iskop'">
                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                      {{ excavationLabels[op.excavation_type] ?? op.excavation_type }}
                    </span>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 font-mono">
                      {{ op.dimensions }}
                    </span>
                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                      {{ op.meterage }} m
                    </span>
                  </template>

                  <!-- Upuhivanje details -->
                  <template v-else>
                    <span v-if="op.address" class="px-2.5 py-1 text-xs rounded-lg bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                      {{ op.address }}
                    </span>
                    <span v-if="op.splajsovano" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                      Splajsovano
                    </span>
                    <span v-if="op.aktivirano" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800">
                      Aktivirano
                    </span>
                  </template>
                </div>

                <!-- Streets -->
                <div v-if="op.streets?.length" class="mt-2 flex flex-wrap gap-1.5">
                  <span
                    v-for="street in op.streets"
                    :key="street"
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-md bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700"
                  >
                    <svg class="size-2.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
                    </svg>
                    {{ street }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Sub-operations -->
            <div v-if="op.sub_operations?.length" class="mt-3 ml-9">
              <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-2">Pod-operacije</p>
              <div class="space-y-2">
                <div
                  v-for="(sub, j) in op.sub_operations"
                  :key="j"
                  class="rounded-xl border border-neutral-100 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/60 p-3"
                >
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200">{{ sub.type }}</span>
                    <span v-if="sub.meterage" class="text-xs font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-md">
                      {{ sub.meterage }} m
                    </span>
                    <span v-if="sub.broj_kucice" class="text-xs text-neutral-600 dark:text-neutral-400">
                      Kućica: <strong class="text-zinc-800 dark:text-zinc-200">{{ sub.broj_kucice }}</strong>
                    </span>
                  </div>
                  <!-- Sub-operation photos -->
                  <div v-if="sub.photos?.length" class="mt-3 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                    <button
                      v-for="(photo, pi) in sub.photos"
                      :key="pi"
                      type="button"
                      @click="openLightbox({ url: photo, name: 'Slika ' + (pi + 1) })"
                      class="group relative aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 focus:outline-none focus:border-blue-500 transition-all"
                    >
                      <img :src="photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" />
                      <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                        <svg class="size-5 text-white opacity-0 group-hover:opacity-100 drop-shadow transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                          <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41Z" clip-rule="evenodd" />
                        </svg>
                      </div>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Operation images -->
            <div v-if="op.images?.length" class="mt-3 ml-9">
              <p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 0 1 3.25 3h13.5A2.25 2.25 0 0 1 19 5.25v9.5A2.25 2.25 0 0 1 16.75 17H3.25A2.25 2.25 0 0 1 1 14.75v-9.5Zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 0 0 .75-.75v-2.69l-2.22-2.219a.75.75 0 0 0-1.06 0l-1.91 1.909.47.47a.75.75 0 1 1-1.06 1.06L6.53 8.091a.75.75 0 0 0-1.06 0l-2.97 2.97ZM12 7a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" clip-rule="evenodd" />
                </svg>
                Fotografije ({{ op.images.length }})
              </p>
              <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                <button
                  v-for="(img, pi) in op.images"
                  :key="pi"
                  type="button"
                  @click="openLightbox(img)"
                  class="group relative aspect-square rounded-xl overflow-hidden border-2 border-transparent hover:border-blue-500 focus:outline-none focus:border-blue-500 transition-all shadow-sm"
                >
                  <img :src="img.url" :alt="img.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" />
                  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-colors flex items-center justify-center">
                    <svg class="size-5 text-white opacity-0 group-hover:opacity-100 drop-shadow-md transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                      <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41Z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Entry footer: total meterage summary -->
        <div v-if="totalMeterage(entry) > 0" class="px-5 py-3 border-t border-neutral-100 dark:border-neutral-800 bg-neutral-50/40 dark:bg-neutral-800/20 flex items-center justify-end gap-2">
          <span class="text-xs text-neutral-500 dark:text-neutral-400">Ukupna metraža:</span>
          <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ totalMeterage(entry) }} m</span>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <Teleport to="body">
      <div
        v-if="lightbox"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        @click.self="closeLightbox"
      >
        <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col items-center">
          <button
            @click="closeLightbox"
            class="absolute -top-10 right-0 text-white/80 hover:text-white transition-colors"
          >
            <svg class="size-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            </svg>
          </button>
          <img
            :src="lightbox.url"
            :alt="lightbox.name"
            class="max-h-[80vh] max-w-full rounded-xl shadow-2xl object-contain"
          />
          <p v-if="lightbox.name" class="mt-3 text-sm text-white/70">{{ lightbox.name }}</p>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { BASE } from '../../utils/base'

const entries = ref([])
const loading = ref(true)
const lightbox = ref(null)

const today = new Date().toISOString().slice(0, 10)
const filter = reactive({ project_id: '', date_from: today, date_to: '' })

const filterProjects = computed(() => {
  const seen = new Set()
  return entries.value
    .map(e => ({ id: e.project.id, name: e.project.name }))
    .filter(p => seen.has(p.id) ? false : seen.add(p.id))
})

const filteredEntries = computed(() => {
  return entries.value.filter(entry => {
    if (filter.project_id && entry.project.id != filter.project_id) return false
    if (filter.date_from && entry.date < filter.date_from) return false
    if (filter.date_to && entry.date > filter.date_to) return false
    return true
  })
})

const hasFilter = computed(() => !!(filter.project_id || filter.date_from || filter.date_to))

function clearFilter() {
  filter.project_id = ''
  filter.date_from = ''
  filter.date_to = ''
}

const workTypeLabels = { uvlačenje: 'Uvlačenje', iskop: 'Iskop', otvaranje_rupa: 'Otvaranje rupa' }
const excavationLabels = { iskop: 'Iskop', iskop_flaster: 'Iskop flaster', iskop_asfalt: 'Iskop asfalt', raketa: 'Raketa' }

function totalMeterage(entry) {
  return entry.operations
    .filter(op => op.kind === 'iskop' && op.meterage)
    .reduce((sum, op) => sum + parseFloat(op.meterage), 0)
    .toFixed(2)
    .replace(/\.00$/, '')
}

function openLightbox(img) {
  lightbox.value = img
  document.body.style.overflow = 'hidden'
}

function closeLightbox() {
  lightbox.value = null
  document.body.style.overflow = ''
}

function onKeydown(e) {
  if (e.key === 'Escape') closeLightbox()
}

onMounted(async () => {
  window.addEventListener('keydown', onKeydown)
  try {
    const res = await fetch(BASE + '/api/radnik/entries', { headers: { 'Accept': 'application/json' } })
    entries.value = await res.json()
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>
