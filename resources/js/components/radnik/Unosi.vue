<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Moji unosi radova</h1>
      <a
        :href="BASE + '/radnik/novi-unos'"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
      >
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
        </svg>
        Novi unos
      </a>
    </div>

    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <div
      v-else-if="entries.length === 0"
      class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl"
    >
      <svg class="mx-auto size-12 text-neutral-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
      </svg>
      <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Nema unosa</p>
      <p class="mt-1 text-sm text-zinc-500">Kreirajte prvi unos klikom na dugme iznad.</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="entry in entries"
        :key="entry.id"
        class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 bg-white dark:bg-neutral-900"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ entry.project.name }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              {{ entry.project.city }} &middot; {{ entry.street }} &middot; {{ entry.date }}
            </p>
          </div>
          <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
            {{ entry.cable_type }}
          </span>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
          <span class="text-sm text-neutral-500 dark:text-neutral-400">Radovi:</span>
          <span
            v-for="wt in entry.work_types"
            :key="wt"
            class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300"
          >{{ workTypeLabels[wt] ?? wt }}</span>
          <span class="text-sm text-neutral-500 dark:text-neutral-400 ml-2">Kućište:</span>
          <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ entry.enclosure }}</span>
        </div>

        <div v-if="entry.operations.length" class="mt-4 space-y-2">
          <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">
            Operacije ({{ entry.operations.length }}):
          </p>
          <div
            v-for="op in entry.operations"
            :key="op.id"
            class="flex items-center justify-between px-3 py-2 rounded-lg bg-neutral-50 dark:bg-neutral-800"
          >
            <template v-if="op.kind === 'upuhivanje'">
              <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="px-2 py-0.5 text-xs rounded-full border border-purple-400 text-purple-700 dark:text-purple-300">Upuhivanje</span>
                <span class="text-neutral-600 dark:text-neutral-400">{{ op.address }}</span>
                <span
                  v-for="street in op.streets ?? []"
                  :key="street"
                  class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300"
                >
                  {{ street }}
                </span>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="op.splajsovano" class="px-2 py-0.5 text-xs rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">Splajsovano</span>
                <span v-if="op.aktivirano" class="px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">Aktivirano</span>
              </div>
            </template>
            <template v-else>
              <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="px-2 py-0.5 text-xs rounded-full border border-neutral-300 dark:border-neutral-600 text-neutral-600 dark:text-neutral-300">{{ excavationLabels[op.excavation_type] ?? op.excavation_type }}</span>
                <span>{{ op.dimensions }}</span>
                <span class="font-medium">{{ op.meterage }} m</span>
                <span
                  v-for="street in op.streets ?? []"
                  :key="street"
                  class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300"
                >
                  {{ street }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <template v-if="op.sub_operations?.length">
                  <span
                    v-for="(sub, j) in op.sub_operations"
                    :key="j"
                    class="px-2 py-0.5 text-xs rounded-full bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300"
                  >
                    {{ sub.type }}<template v-if="sub.meterage"> · {{ sub.meterage }} m</template><template v-if="sub.broj_kucice"> · kć. {{ sub.broj_kucice }}</template>
                  </span>
                </template>
                <span v-if="op.images_count" class="px-2 py-0.5 text-xs rounded-full bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300">
                  📷 {{ op.images_count }}
                </span>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const entries = ref([])
const loading = ref(true)

const workTypeLabels = { uvlačenje: 'Uvlačenje', iskop: 'Iskop', otvaranje_rupa: 'Otvaranje rupa' }
const excavationLabels = { iskop: 'Iskop', iskop_flaster: 'Iskop flaster', iskop_asfalt: 'Iskop asfalt', raketa: 'Raketa' }

onMounted(async () => {
  try {
    const res = await fetch(BASE + '/api/radnik/entries', { headers: { 'Accept': 'application/json' } })
    entries.value = await res.json()
  } finally {
    loading.value = false
  }
})
</script>
