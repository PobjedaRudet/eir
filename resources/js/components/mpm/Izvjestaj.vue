<template>
  <div>
    <!-- Header -->
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Izvještaj operacija</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Pregled izvršenih operacija radnika po danima</p>
      </div>
      <div v-if="days.length" class="flex flex-wrap items-center gap-2">
        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300">
          📅 {{ days.length }} {{ days.length === 1 ? 'dan' : 'dana' }}
        </span>
        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300">
          📄 {{ totalEntries }} {{ totalEntries === 1 ? 'unos' : 'unosa' }}
        </span>
        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300">
          🔧 {{ totalOps }} operacija
        </span>
      </div>
    </div>

    <!-- Filters -->
    <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-4 mb-6 bg-white dark:bg-neutral-900">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Projekat</label>
          <select v-model="filters.project_id" @change="loadReport" class="select-field">
            <option value="">Svi projekti</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }} — {{ p.city }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Datum od</label>
          <input type="date" v-model="filters.date_from" @change="loadReport" class="input-field">
        </div>
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Datum do</label>
          <input type="date" v-model="filters.date_to" @change="loadReport" class="input-field">
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <!-- Empty -->
    <div
      v-else-if="days.length === 0"
      class="text-center py-16 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl"
    >
      <svg class="mx-auto size-12 text-neutral-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
      </svg>
      <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Nema podataka</p>
      <p class="mt-1 text-sm text-zinc-500">Nema operacija za odabrani period i projekat.</p>
    </div>

    <!-- Grouped by project → worker -->
    <div v-else class="space-y-8">
      <div v-for="pg in projectGroups" :key="pg.project_id">

        <!-- Project header -->
        <div class="flex items-center gap-3 mb-4">
          <div class="flex items-center gap-2 rounded-xl bg-zinc-900 dark:bg-zinc-100 px-4 py-2 text-white dark:text-zinc-900">
            <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-bold whitespace-nowrap">{{ pg.project }}</span>
            <span class="text-xs opacity-60">{{ pg.city }}</span>
          </div>
          <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
            {{ pg.workers.length }} {{ pg.workers.length === 1 ? 'radnik' : 'radnika' }}
          </span>
          <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
            {{ pg.workers.reduce((s, w) => s + w.entries.length, 0) }} unosa
          </span>
          <div class="flex-1 h-px bg-neutral-200 dark:bg-neutral-700"></div>
        </div>

        <!-- Workers within project -->
        <div class="space-y-6 pl-4 border-l-2 border-zinc-200 dark:border-zinc-700">
          <div v-for="wg in pg.workers" :key="wg.worker">

            <!-- Worker header -->
            <div class="flex items-center gap-2 mb-3">
              <div class="flex items-center gap-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-3 py-1.5">
                <svg class="size-4 text-blue-600 dark:text-blue-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-5.5-2.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM10 12a5.99 5.99 0 0 0-4.793 2.39A6.483 6.483 0 0 0 10 16.5a6.483 6.483 0 0 0 4.793-2.11A5.99 5.99 0 0 0 10 12Z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm font-semibold text-blue-800 dark:text-blue-200">{{ wg.worker }}</span>
              </div>
              <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                {{ wg.entries.length }} {{ wg.entries.length === 1 ? 'unos' : 'unosa' }}
              </span>
              <div class="flex-1 h-px bg-neutral-100 dark:bg-neutral-800"></div>
            </div>

            <!-- Entries for this worker/project -->
            <div class="space-y-3">
              <div
                v-for="entry in wg.entries"
                :key="entry.id"
                class="border border-neutral-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-900 overflow-hidden"
              >
                <!-- Entry meta bar -->
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 px-4 py-2.5 bg-neutral-50 dark:bg-neutral-800/60 border-b border-neutral-100 dark:border-neutral-800">
                  <div class="flex items-center gap-1.5">
                    <svg class="size-3.5 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>
                    <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ formatDate(entry.date) }}</span>
                  </div>
                  <template v-if="entry.street">
                    <span class="text-neutral-300 dark:text-neutral-600">·</span>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">{{ entry.street }}</span>
                  </template>
                  <template v-if="entry.enclosure">
                    <span class="text-neutral-300 dark:text-neutral-600">·</span>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400">Kućište: {{ entry.enclosure }}</span>
                  </template>
                  <div class="ml-auto flex flex-wrap items-center gap-1">
                    <span class="px-2 py-0.5 text-xs rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300 font-mono">{{ entry.cable_type }}</span>
                    <span
                      v-for="wt in entry.work_types"
                      :key="wt"
                      class="px-2 py-0.5 text-xs rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                    >{{ workTypes[wt] ?? wt }}</span>
                  </div>
                </div>

                <!-- Operations -->
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                  <div v-for="(op, opIdx) in entry.operations" :key="op.id" class="px-4 py-3.5">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                      <span class="text-xs font-medium text-neutral-400 uppercase tracking-wider mr-1">Op. {{ opIdx + 1 }}</span>

                      <template v-if="op.kind === 'iskop'">
                        <span class="px-2 py-0.5 text-xs rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">Iskop</span>
                        <span v-if="op.excavation_type" class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">{{ excavationTypes[op.excavation_type] ?? op.excavation_type }}</span>
                        <span v-if="op.dimensions" class="px-2 py-0.5 text-xs rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300 font-mono">{{ op.dimensions }}</span>
                        <span v-if="op.meterage" class="px-2 py-0.5 text-xs rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-medium">↔ {{ parseFloat(op.meterage).toFixed(2) }} m</span>
                      </template>

                      <template v-else-if="op.kind === 'upuhivanje'">
                        <span class="px-2 py-0.5 text-xs rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300">Upuhivanje kabla</span>
                        <span v-if="op.address" class="text-sm text-neutral-600 dark:text-neutral-400">{{ op.address }}</span>
                        <span v-if="op.splajsovano" class="px-2 py-0.5 text-xs rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">Splajsovano</span>
                        <span v-if="op.aktivirano" class="px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">Aktivirano</span>
                      </template>

                      <span
                        v-for="street in op.streets ?? []"
                        :key="street"
                        class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300"
                      >{{ street }}</span>
                    </div>

                    <!-- Sub-operations -->
                    <div v-if="op.kind === 'iskop' && op.sub_operations?.length" class="mb-3 space-y-2 pl-2 border-l-2 border-neutral-100 dark:border-neutral-800">
                      <div
                        v-for="(sub, si) in op.sub_operations"
                        :key="si"
                        class="rounded-lg border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800/50 p-3"
                      >
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                          <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">🔧 {{ sub.type ?? 'HP+' }}</span>
                          <span v-if="sub.meterage" class="text-sm text-neutral-600 dark:text-neutral-400">{{ parseFloat(sub.meterage).toFixed(2) }} m</span>
                          <span v-if="sub.broj_kucice" class="text-sm text-neutral-500 dark:text-neutral-400">· Kć. <strong>{{ sub.broj_kucice }}</strong></span>
                        </div>
                        <div v-if="sub.photos?.length" class="flex flex-wrap gap-2 mt-2">
                          <a v-for="(photo, pi) in sub.photos" :key="pi" :href="`${BASE}/storage/${photo}`" target="_blank" class="block">
                            <img :src="`${BASE}/storage/${photo}`" class="h-20 w-20 object-cover rounded-lg border border-neutral-200 dark:border-neutral-700 hover:opacity-75 transition-opacity" alt="Fotografija HP+">
                          </a>
                        </div>
                      </div>
                    </div>

                    <!-- Operation images -->
                    <div v-if="op.images?.length" class="flex flex-wrap gap-2">
                      <a v-for="img in op.images" :key="img.url" :href="img.url" target="_blank" class="block">
                        <img :src="img.url" class="h-20 w-20 object-cover rounded-lg border border-neutral-200 dark:border-neutral-700 hover:opacity-75 transition-opacity" :alt="img.name">
                      </a>
                    </div>
                  </div>

                  <div v-if="!entry.operations.length" class="px-4 py-3 text-sm text-neutral-400">Nema operacija.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const days = ref([])
const projects = ref([])
const workTypes = ref({})
const excavationTypes = ref({})
const loading = ref(true)

const now = new Date()
const firstOfMonth = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)

const filters = reactive({
  project_id: '',
  date_from: firstOfMonth,
  date_to: now.toISOString().slice(0, 10),
})

const totalEntries = computed(() => days.value.reduce((s, d) => s + d.entries.length, 0))
const totalOps = computed(() => days.value.reduce((s, d) => s + d.entries.reduce((ss, e) => ss + e.operations.length, 0), 0))

const projectGroups = computed(() => {
  const allEntries = days.value.flatMap(d => d.entries.map(e => ({ ...e, date: d.date })))

  const projectMap = new Map()
  for (const entry of allEntries) {
    const projKey = entry.project
    if (!projectMap.has(projKey)) {
      projectMap.set(projKey, { project: entry.project, project_id: entry.project_id ?? projKey, city: entry.city ?? '', workers: new Map() })
    }
    const pg = projectMap.get(projKey)
    if (!pg.workers.has(entry.worker)) {
      pg.workers.set(entry.worker, [])
    }
    pg.workers.get(entry.worker).push(entry)
  }

  return Array.from(projectMap.values()).map(pg => ({
    ...pg,
    workers: Array.from(pg.workers.entries()).map(([worker, entries]) => ({
      worker,
      entries: entries.slice().sort((a, b) => b.date.localeCompare(a.date)),
    })),
  }))
})

function formatDate(dateStr) {
  const d = new Date(dateStr)
  const days = ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub']
  return `${days[d.getDay()]}, ${d.toLocaleDateString('bs-BA')}`
}

async function loadReport() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.project_id) params.set('project_id', filters.project_id)
    if (filters.date_from) params.set('date_from', filters.date_from)
    if (filters.date_to) params.set('date_to', filters.date_to)

    const res = await fetch(`${BASE}/api/mpm/report?${params}`, { headers: { 'Accept': 'application/json' } })
    const data = await res.json()
    days.value = data.days
    projects.value = data.projects
    workTypes.value = data.work_types
    excavationTypes.value = data.excavation_types
  } finally {
    loading.value = false
  }
}

onMounted(loadReport)
</script>

<style scoped>
@reference "../../../css/app.css";

.input-field {
  @apply w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500;
}
.select-field {
  @apply w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500;
}
</style>
