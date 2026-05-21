<template>
  <div class="max-w-2xl">
    <!-- Page header -->
    <div class="flex items-center gap-3 mb-5">
      <a
        :href="BASE + '/radnik/unosi'"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <div>
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white leading-tight">Novi unos radova</h1>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Popunite sve obavezne podatke i sačuvajte unos</p>
      </div>
    </div>

    <div v-if="configLoading" class="text-center py-12 text-zinc-500 text-sm">Učitavanje...</div>

    <form v-else @submit.prevent="save" class="space-y-4">

      <!-- ── Osnovni podaci ────────────────────────── -->
      <section class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
        <div class="px-4 py-2.5 bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700 flex items-center gap-2">
          <span class="size-1.5 rounded-full bg-blue-500"></span>
          <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 uppercase tracking-wide">Osnovni podaci</h2>
        </div>

        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">

          <!-- Projekat (full width) -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Projekat</label>
            <select v-model="form.project_id" @change="onProjectChange" class="select-field">
              <option value="">Izaberite projekat</option>
              <option v-for="p in config.projects" :key="p.id" :value="p.id">{{ p.name }} — {{ p.city }}</option>
            </select>
            <p v-if="!config.projects.length && !serverError" class="mt-1 text-xs text-neutral-400">Nema projekata za odabir.</p>
            <p v-if="errors['project_id']" class="mt-1 text-xs text-red-600">{{ errors['project_id'][0] }}</p>
          </div>

          <!-- Vrsta kabla -->
          <div>
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Vrsta kabla</label>
            <select v-model="form.cable_type" class="select-field">
              <option value="">Izaberite</option>
              <option v-for="ct in config.cable_types" :key="ct" :value="ct">{{ ct }}</option>
            </select>
            <p v-if="errors['cable_type']" class="mt-1 text-xs text-red-600">{{ errors['cable_type'][0] }}</p>
          </div>

          <!-- Kućište -->
          <div>
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Kućište</label>
            <select v-model="form.enclosure_id" class="select-field">
              <option value="">Izaberite</option>
              <option v-for="e in config.enclosures" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
            <p v-if="errors['enclosure_id']" class="mt-1 text-xs text-red-600">{{ errors['enclosure_id'][0] }}</p>
          </div>

          <!-- Datum -->
          <div>
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Datum</label>
            <input type="date" v-model="form.date" required class="input-field">
            <p v-if="errors['date']" class="mt-1 text-xs text-red-600">{{ errors['date'][0] }}</p>
          </div>

          <!-- Ulice -->
          <div>
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Ulice</label>
            <div v-if="!form.project_id" class="text-xs text-neutral-400 py-2">Prvo izaberite projekat.</div>
            <div v-else-if="availableStreets.length" ref="streetsDropdown" class="relative">
              <button
                type="button"
                @click="streetsOpen = !streetsOpen"
                class="flex w-full items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white"
              >
                <span class="truncate text-left text-sm">{{ selectedStreetSummary }}</span>
                <svg class="size-4 shrink-0 text-zinc-400 transition-transform" :class="streetsOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
              </button>
              <div v-if="streetsOpen" class="absolute z-20 mt-1 w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 shadow-lg">
                <div class="max-h-52 overflow-y-auto p-1.5 space-y-0.5">
                  <label
                    v-for="street in availableStreets"
                    :key="street.id"
                    class="flex items-center gap-2.5 cursor-pointer px-3 py-1.5 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                  >
                    <input type="checkbox" :value="street.id" v-model="form.street_ids" class="rounded border-neutral-300 text-blue-600">
                    <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ street.name }}</span>
                  </label>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-neutral-400 py-2">Nema dostupnih ulica.</div>
            <p v-if="errors['street_ids']" class="mt-1 text-xs text-red-600">{{ errors['street_ids'][0] }}</p>
            <div v-if="selectedStreetNames.length" class="mt-2 flex flex-wrap gap-1.5">
              <span
                v-for="streetName in selectedStreetNames"
                :key="streetName"
                class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700"
              >{{ streetName }}</span>
            </div>
          </div>

          <!-- Vrste radova (full width) -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">
              Vrste radova <span class="normal-case font-normal text-zinc-400">(jedna ili više)</span>
            </label>
            <div class="flex flex-wrap gap-2">
              <label
                v-for="(label, value) in config.work_types"
                :key="value"
                class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg border text-sm transition-colors"
                :class="form.work_types.includes(value)
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200'
                  : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-zinc-700 dark:text-zinc-300'"
              >
                <input type="checkbox" :value="value" v-model="form.work_types" class="rounded text-blue-600 size-3.5">
                <span class="font-medium">{{ label }}</span>
              </label>
            </div>
            <p v-if="errors['work_types']" class="mt-1 text-xs text-red-600">{{ errors['work_types'][0] }}</p>
          </div>
        </div>
      </section>

      <!-- ── Operacije ─────────────────────────────── -->
      <section class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
        <div class="px-4 py-2.5 bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700 flex items-center gap-2">
          <span class="size-1.5 rounded-full bg-emerald-500"></span>
          <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 uppercase tracking-wide">Operacije</h2>
        </div>

        <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
          <div
            v-for="(op, i) in form.operations"
            :key="i"
            class="p-4"
          >
            <!-- Op header -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="flex size-5 items-center justify-center rounded-full bg-neutral-200 dark:bg-neutral-700 text-xs font-bold text-neutral-700 dark:text-neutral-200">{{ i + 1 }}</span>
                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Operacija {{ i + 1 }}</span>
              </div>
              <button
                v-if="form.operations.length > 1"
                type="button"
                @click="removeOperation(i)"
                class="inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-700 px-2 py-1 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              >
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                </svg>
                Ukloni
              </button>
            </div>

            <div class="space-y-3">
              <!-- Vrsta operacije -->
              <div>
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">Vrsta operacije</label>
                <div class="grid grid-cols-2 gap-2">
                  <label
                    v-for="(label, kind) in { iskop: 'Iskop', upuhivanje: 'Upuhivanje kabla' }"
                    :key="kind"
                    class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border text-sm font-medium transition-colors"
                    :class="op.kind === kind
                      ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200'
                      : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-zinc-700 dark:text-zinc-300'"
                  >
                    <input type="radio" v-model="op.kind" :value="kind" class="text-blue-600 size-3.5">
                    {{ label }}
                  </label>
                </div>
                <p v-if="errors[`operations.${i}.kind`]" class="mt-1 text-xs text-red-600">{{ errors[`operations.${i}.kind`][0] }}</p>
              </div>

              <!-- ── ISKOP ── -->
              <template v-if="op.kind === 'iskop'">
                <!-- Vrsta iskopa -->
                <div>
                  <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">Vrsta iskopa</label>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                    <label
                      v-for="(label, value) in { iskop: 'Iskop', iskop_flaster: 'Iskop flaster', iskop_asfalt: 'Iskop asfalt', raketa: 'Raketa' }"
                      :key="value"
                      class="flex items-center gap-2 cursor-pointer px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors"
                      :class="op.excavation_type === value
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200'
                        : 'border-neutral-200 dark:border-neutral-600 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-zinc-700 dark:text-zinc-300'"
                    >
                      <input type="radio" v-model="op.excavation_type" :value="value" class="text-blue-600 size-3">
                      {{ label }}
                    </label>
                  </div>
                  <p v-if="errors[`operations.${i}.excavation_type`]" class="mt-1 text-xs text-red-600">{{ errors[`operations.${i}.excavation_type`][0] }}</p>
                </div>

                <!-- Dimenzije -->
                <div>
                  <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">Dimenzije</label>
                  <div class="flex flex-wrap gap-1.5">
                    <label
                      v-for="dim in ['15x45', '15x60', '30x45', '30x60']"
                      :key="dim"
                      class="flex items-center gap-2 cursor-pointer px-3 py-1.5 rounded-lg border text-xs font-mono font-medium transition-colors"
                      :class="op.dimensions === dim
                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200'
                        : 'border-neutral-200 dark:border-neutral-600 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-zinc-700 dark:text-zinc-300'"
                    >
                      <input type="radio" v-model="op.dimensions" :value="dim" class="text-blue-600 size-3">
                      {{ dim }}
                    </label>
                  </div>
                  <p v-if="errors[`operations.${i}.dimensions`]" class="mt-1 text-xs text-red-600">{{ errors[`operations.${i}.dimensions`][0] }}</p>
                </div>

                <!-- Metraža -->
                <div>
                  <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Metraža (m)</label>
                  <input type="number" v-model="op.meterage" step="0.01" min="0.01" placeholder="npr. 12.50" class="input-field">
                  <p v-if="errors[`operations.${i}.meterage`]" class="mt-1 text-xs text-red-600">{{ errors[`operations.${i}.meterage`][0] }}</p>
                </div>

                <!-- Podoperacije HP+ -->
                <div>
                  <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Podoperacije</label>
                    <button type="button" @click="addSubOperation(i)" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1">
                      <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                      Dodaj HP+
                    </button>
                  </div>

                  <div v-if="op.sub_operations.length" class="space-y-2">
                    <div
                      v-for="(sub, j) in op.sub_operations"
                      :key="j"
                      class="rounded-lg bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-3"
                    >
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-zinc-700 dark:text-zinc-200 flex items-center gap-1.5">
                          <span class="px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 font-mono">HP+</span>
                          Podoperacija {{ j + 1 }}
                        </span>
                        <button type="button" @click="removeSubOperation(i, j)" class="text-red-400 hover:text-red-600 p-0.5 rounded transition-colors">
                          <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                        </button>
                      </div>
                      <div class="grid grid-cols-2 gap-2">
                        <div>
                          <label class="block text-xs text-zinc-500 mb-0.5">Metraža (m)</label>
                          <input type="number" v-model="sub.meterage" step="0.01" min="0.01" placeholder="npr. 5.50" class="input-field text-sm">
                        </div>
                        <div>
                          <label class="block text-xs text-zinc-500 mb-0.5">Broj kućice</label>
                          <input type="text" v-model="sub.broj_kucice" placeholder="npr. 12A" class="input-field text-sm">
                        </div>
                      </div>
                      <!-- Sub-op photos -->
                      <div class="mt-2 pt-2 border-t border-neutral-200 dark:border-neutral-700">
                        <p class="text-xs font-medium text-neutral-500 mb-1.5">Fotografije HP+</p>
                        <label class="flex items-center justify-center gap-2 w-full h-10 border border-dashed border-neutral-300 dark:border-neutral-600 rounded-lg cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors text-xs text-neutral-400">
                          <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 0 1 3.25 3h13.5A2.25 2.25 0 0 1 19 5.25v9.5A2.25 2.25 0 0 1 16.75 17H3.25A2.25 2.25 0 0 1 1 14.75v-9.5Zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 0 0 .75-.75v-2.69l-2.22-2.219a.75.75 0 0 0-1.06 0l-1.91 1.909.47.47a.75.75 0 1 1-1.06 1.06L6.53 8.091a.75.75 0 0 0-1.06 0l-2.97 2.97ZM12 7a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" clip-rule="evenodd" /></svg>
                          Dodaj fotografije
                          <input type="file" :name="`sub_photos_${i}_${j}`" @change="e => onSubPhotoChange(e, i, j)" accept="image/*" multiple class="hidden">
                        </label>
                        <div v-if="subPhotoPreviews[i]?.[j]?.length" class="mt-1.5 flex flex-wrap gap-1.5">
                          <div v-for="(src, k) in subPhotoPreviews[i][j]" :key="k" class="relative">
                            <img :src="src" class="h-12 w-12 object-cover rounded-lg border border-neutral-200 dark:border-neutral-600">
                            <button type="button" @click="removeSubPhoto(i, j, k)" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-white text-xs shadow hover:bg-red-700" aria-label="Ukloni">×</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p v-else class="text-xs text-neutral-400 italic">Nema podoperacija.</p>
                </div>
              </template>

              <!-- ── UPUHIVANJE ── -->
              <template v-else-if="op.kind === 'upuhivanje'">
                <div>
                  <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Adresa kuće</label>
                  <input type="text" v-model="op.address" placeholder="npr. Titova ulica 12" class="input-field">
                  <p v-if="errors[`operations.${i}.address`]" class="mt-1 text-xs text-red-600">{{ errors[`operations.${i}.address`][0] }}</p>
                </div>

                <div class="flex gap-2">
                  <label
                    class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border text-sm font-medium transition-colors flex-1"
                    :class="op.splajsovano ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-zinc-700 dark:text-zinc-300'"
                  >
                    <input type="checkbox" v-model="op.splajsovano" class="rounded text-blue-600 size-3.5">
                    Splajsovano
                  </label>
                  <label
                    class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border text-sm font-medium transition-colors flex-1"
                    :class="op.aktivirano ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-zinc-700 dark:text-zinc-300'"
                  >
                    <input type="checkbox" v-model="op.aktivirano" class="rounded text-green-600 size-3.5">
                    Aktivirano
                  </label>
                </div>
              </template>

              <!-- Fotografije operacije -->
              <div v-if="op.kind">
                <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1.5">Fotografije operacije</label>
                <label class="flex items-center justify-center gap-2 w-full h-12 border border-dashed border-neutral-300 dark:border-neutral-600 rounded-lg cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors text-xs text-neutral-400">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 0 1 3.25 3h13.5A2.25 2.25 0 0 1 19 5.25v9.5A2.25 2.25 0 0 1 16.75 17H3.25A2.25 2.25 0 0 1 1 14.75v-9.5Zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 0 0 .75-.75v-2.69l-2.22-2.219a.75.75 0 0 0-1.06 0l-1.91 1.909.47.47a.75.75 0 1 1-1.06 1.06L6.53 8.091a.75.75 0 0 0-1.06 0l-2.97 2.97ZM12 7a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" clip-rule="evenodd" /></svg>
                  Kliknite za dodavanje fotografija
                  <input type="file" :name="`photos_${i}`" @change="e => onPhotoChange(e, i)" accept="image/*" multiple class="hidden">
                </label>
                <div v-if="photoPreviews[i]?.length" class="mt-2 flex flex-wrap gap-2">
                  <div v-for="(src, k) in photoPreviews[i]" :key="k" class="relative">
                    <img :src="src" class="h-16 w-16 object-cover rounded-lg border border-neutral-200 dark:border-neutral-600">
                    <button type="button" @click="removePhoto(i, k)" class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-white text-xs shadow hover:bg-red-700" aria-label="Ukloni">×</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="px-4 py-3 border-t border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/30">
          <button type="button" @click="addOperation" class="btn-secondary text-xs py-1.5 px-3">
            + Dodaj operaciju
          </button>
        </div>
      </section>

      <!-- ── Submit ─────────────────────────────────── -->
      <div class="flex items-center gap-3 pt-1">
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Čuvanje...' : 'Sačuvaj unos' }}
        </button>
        <a :href="BASE + '/radnik/unosi'" class="btn-secondary">Odustani</a>
      </div>

      <div v-if="serverError" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
        {{ serverError }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, reactive } from 'vue'
import { BASE } from '../../utils/base'

const config = ref({ projects: [], enclosures: [], cable_types: [], work_types: {} })
const configLoading = ref(true)
const saving = ref(false)
const errors = ref({})
const serverError = ref('')
const streetsOpen = ref(false)
const streetsDropdown = ref(null)

const form = reactive({
  project_id: '',
  cable_type: '',
  work_types: [],
  enclosure_id: '',
  street_ids: [],
  date: new Date().toISOString().slice(0, 10),
  operations: [],
})

// File refs — stored as File[] per operation index
const photoFiles = ref([])
const subPhotoFiles = ref([]) // [opIndex][subIndex] = File[]
const photoPreviews = ref([])
const subPhotoPreviews = ref([])

const availableStreets = computed(() => {
  const project = config.value.projects.find(p => p.id == form.project_id)
  return project?.streets ?? []
})

const selectedStreetNames = computed(() => {
  const selectedIds = new Set(form.street_ids.map(id => String(id)))
  return availableStreets.value
    .filter(street => selectedIds.has(String(street.id)))
    .map(street => street.name)
})

const selectedStreetSummary = computed(() => {
  if (!selectedStreetNames.value.length) {
    return 'Izaberite ulice'
  }

  if (selectedStreetNames.value.length === 1) {
    return selectedStreetNames.value[0]
  }

  return `${selectedStreetNames.value.length} odabrane ulice`
})

function addOperation() {
  form.operations.push({ kind: '', excavation_type: '', dimensions: '', meterage: '', sub_operations: [], address: '', splajsovano: false, aktivirano: false })
  photoFiles.value.push([])
  photoPreviews.value.push([])
  subPhotoFiles.value.push([])
  subPhotoPreviews.value.push([])
}

function removeOperation(i) {
  if (form.operations.length <= 1) return
  form.operations.splice(i, 1)
  photoFiles.value.splice(i, 1)
  photoPreviews.value.splice(i, 1)
  subPhotoFiles.value.splice(i, 1)
  subPhotoPreviews.value.splice(i, 1)
}

function addSubOperation(opIndex) {
  form.operations[opIndex].sub_operations.push({ type: 'HP+', meterage: '', broj_kucice: '' })
  if (!subPhotoFiles.value[opIndex]) subPhotoFiles.value[opIndex] = []
  subPhotoFiles.value[opIndex].push([])
  if (!subPhotoPreviews.value[opIndex]) subPhotoPreviews.value[opIndex] = []
  subPhotoPreviews.value[opIndex].push([])
}

function removeSubOperation(opIndex, subIndex) {
  form.operations[opIndex].sub_operations.splice(subIndex, 1)
  subPhotoFiles.value[opIndex]?.splice(subIndex, 1)
  subPhotoPreviews.value[opIndex]?.splice(subIndex, 1)
}

function onProjectChange() {
  form.street_ids = []
  streetsOpen.value = false
}

function onDocumentClick(event) {
  if (!streetsOpen.value) return
  if (streetsDropdown.value?.contains(event.target)) return

  streetsOpen.value = false
}

function appendFiles(existingFiles, existingPreviews, newFiles) {
  return {
    files: [...existingFiles, ...newFiles],
    previews: [...existingPreviews, ...newFiles.map(file => URL.createObjectURL(file))],
  }
}

function onPhotoChange(e, i) {
  const files = Array.from(e.target.files ?? [])
  const next = appendFiles(photoFiles.value[i] ?? [], photoPreviews.value[i] ?? [], files)
  photoFiles.value[i] = next.files
  photoPreviews.value[i] = next.previews
  e.target.value = ''
}

function onSubPhotoChange(e, i, j) {
  const files = Array.from(e.target.files ?? [])
  if (!subPhotoFiles.value[i]) subPhotoFiles.value[i] = []
  if (!subPhotoPreviews.value[i]) subPhotoPreviews.value[i] = []
  const next = appendFiles(subPhotoFiles.value[i][j] ?? [], subPhotoPreviews.value[i][j] ?? [], files)
  subPhotoFiles.value[i][j] = next.files
  subPhotoPreviews.value[i][j] = next.previews
  e.target.value = ''
}

function removePhoto(opIndex, photoIndex) {
  const preview = photoPreviews.value[opIndex]?.[photoIndex]
  if (preview) URL.revokeObjectURL(preview)
  photoFiles.value[opIndex]?.splice(photoIndex, 1)
  photoPreviews.value[opIndex]?.splice(photoIndex, 1)
}

function removeSubPhoto(opIndex, subIndex, photoIndex) {
  const preview = subPhotoPreviews.value[opIndex]?.[subIndex]?.[photoIndex]
  if (preview) URL.revokeObjectURL(preview)
  subPhotoFiles.value[opIndex]?.[subIndex]?.splice(photoIndex, 1)
  subPhotoPreviews.value[opIndex]?.[subIndex]?.splice(photoIndex, 1)
}

async function save() {
  errors.value = {}
  serverError.value = ''
  saving.value = true

  try {
    const fd = new FormData()
    fd.append('project_id', form.project_id)
    fd.append('cable_type', form.cable_type)
    fd.append('enclosure_id', form.enclosure_id)
    fd.append('date', form.date)
    form.work_types.forEach(wt => fd.append('work_types[]', wt))
    form.street_ids.forEach(streetId => fd.append('street_ids[]', streetId))

    form.operations.forEach((op, i) => {
      fd.append(`operations[${i}][kind]`, op.kind)
      if (op.kind === 'iskop') {
        fd.append(`operations[${i}][excavation_type]`, op.excavation_type)
        fd.append(`operations[${i}][dimensions]`, op.dimensions)
        fd.append(`operations[${i}][meterage]`, op.meterage)
        op.sub_operations.forEach((sub, j) => {
          fd.append(`operations[${i}][sub_operations][${j}][type]`, sub.type)
          if (sub.meterage) fd.append(`operations[${i}][sub_operations][${j}][meterage]`, sub.meterage)
          if (sub.broj_kucice) fd.append(`operations[${i}][sub_operations][${j}][broj_kucice]`, sub.broj_kucice)
          ;(subPhotoFiles.value[i]?.[j] ?? []).forEach(file => fd.append(`sub_photos_${i}_${j}[]`, file))
        })
      } else if (op.kind === 'upuhivanje') {
        fd.append(`operations[${i}][address]`, op.address)
        fd.append(`operations[${i}][splajsovano]`, op.splajsovano ? '1' : '0')
        fd.append(`operations[${i}][aktivirano]`, op.aktivirano ? '1' : '0')
      }
      ;(photoFiles.value[i] ?? []).forEach(file => fd.append(`photos_${i}[]`, file))
    })

    const res = await fetch(BASE + '/api/radnik/entries', {
      method: 'POST',
      headers: {
        'X-XSRF-TOKEN': getCsrf(),
        'Accept': 'application/json',
      },
      body: fd,
    })

    const json = await res.json()

    if (!res.ok) {
      if (res.status === 422 && json.errors) {
        errors.value = json.errors
      } else {
        serverError.value = json.message ?? 'Greška pri čuvanju.'
      }
      return
    }

    window.location.href = BASE + '/radnik/unosi'
  } finally {
    saving.value = false
  }
}

function getCsrf() {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

async function getJsonOrThrow(response, fallbackMessage) {
  const contentType = response.headers.get('content-type') ?? ''
  const payload = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    throw new Error(payload?.message ?? fallbackMessage)
  }

  return payload
}

onMounted(async () => {
  document.addEventListener('click', onDocumentClick)
  serverError.value = ''

  try {
    const res = await fetch(BASE + '/api/radnik/form-config', { headers: { 'Accept': 'application/json' } })
    const data = await getJsonOrThrow(res, 'Ne mogu učitati projekte i pomoćne podatke iz baze.')
    config.value = {
      projects: data.projects ?? [],
      enclosures: data.enclosures ?? [],
      cable_types: data.cable_types ?? [],
      work_types: data.work_types ?? {},
    }
  } catch (error) {
    serverError.value = error instanceof Error ? error.message : 'Ne mogu učitati projekte i pomoćne podatke iz baze.'
  } finally {
    configLoading.value = false
  }
  addOperation()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>

<style scoped>
@reference "../../../css/app.css";

.input-field {
  @apply w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500;
}
.select-field {
  @apply w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500;
}
.btn-primary {
  @apply px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50;
}
.btn-secondary {
  @apply px-4 py-2 rounded-lg border border-neutral-200 dark:border-neutral-700 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors;
}
</style>
