<template>
  <div class="max-w-2xl">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="`${BASE}/vodja/projekti`"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Timovi projekta</h1>
        <p v-if="projectName" class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ projectName }}</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>
    <div v-else-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ serverError }}</div>

    <template v-else>

      <!-- DODAJ TIM -->
      <section class="mb-6">
        <div
          class="flex items-center justify-between px-4 py-3 rounded-xl border cursor-pointer select-none transition-colors"
          :class="showAddForm
            ? 'border-teal-400 bg-teal-50 dark:bg-teal-900/20'
            : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800'"
          @click="showAddForm = !showAddForm"
        >
          <div class="flex items-center gap-2">
            <svg class="size-4 text-teal-600 dark:text-teal-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
            </svg>
            <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Dodaj tim projektu</span>
          </div>
          <svg
            class="size-4 text-zinc-400 transition-transform duration-200"
            :class="showAddForm ? 'rotate-180' : ''"
            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
          >
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
          </svg>
        </div>

        <div v-if="showAddForm" class="mt-2 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 space-y-4">

          <!-- Catalog team picker -->
          <div>
            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Tim iz kataloga</label>
            <div v-if="availableCatalogTeams.length === 0"
                 class="text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2">
              Svi timovi su već aktivni na projektu.
              <a :href="`${BASE}/vodja/timovi-katalog`" target="_blank" class="underline ml-1">Dodaj novi -></a>
            </div>
            <select v-else v-model="addForm.catalog_team_id" class="select-field text-sm">
              <option :value="null">- Izaberite tim -</option>
              <option v-for="t in availableCatalogTeams" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>

          <!-- Worker assignment -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Radnici</label>
              <a :href="`${BASE}/vodja/radnici`" target="_blank"
                 class="text-xs text-teal-600 dark:text-teal-400 hover:underline">Upravljaj radnicima -></a>
            </div>
            <div v-if="allWorkers.length === 0" class="text-sm text-zinc-400 italic">Nema registriranih radnika.</div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
              <label
                v-for="w in allWorkers"
                :key="w.id"
                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-sm transition-colors"
                :class="busyWorkerIds.includes(w.id) && !addForm.worker_ids.includes(w.id)
                  ? 'border-neutral-200 dark:border-neutral-700 opacity-40 cursor-not-allowed'
                  : addForm.worker_ids.includes(w.id)
                    ? 'border-teal-400 bg-teal-50 dark:bg-teal-900/20 cursor-pointer'
                    : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 cursor-pointer'"
              >
                <input
                  type="checkbox"
                  :value="w.id"
                  v-model="addForm.worker_ids"
                  :disabled="busyWorkerIds.includes(w.id) && !addForm.worker_ids.includes(w.id)"
                  class="rounded border-neutral-300 text-teal-600"
                >
                <span class="text-zinc-800 dark:text-zinc-200 flex-1">{{ w.name }}</span>
                <span v-if="busyWorkerIds.includes(w.id) && !addForm.worker_ids.includes(w.id)"
                      class="text-xs text-red-500">zauzet</span>
              </label>
            </div>
          </div>

          <div v-if="addError" class="text-sm text-red-600 dark:text-red-400">{{ addError }}</div>

          <div class="flex items-center gap-2 pt-1">
            <button
              type="button"
              @click="submitAddTeam"
              :disabled="addSaving || !addForm.catalog_team_id"
              class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50"
            >
              {{ addSaving ? 'Dodaje se...' : 'Dodaj tim' }}
            </button>
            <button type="button" @click="showAddForm = false"
                    class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
              Odustani
            </button>
          </div>
        </div>
      </section>

      <!-- AKTIVNI TIMOVI -->
      <section class="space-y-4 mb-6">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-1">
          Aktivni timovi
          <span class="ml-1 px-1.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300">{{ activeTeams.length }}</span>
        </h2>

        <div v-if="activeTeams.length === 0"
             class="p-8 rounded-xl border-2 border-dashed border-neutral-300 dark:border-neutral-700 text-center text-sm text-zinc-400 dark:text-zinc-500">
          Nema aktivnih timova na projektu.
        </div>

        <div
          v-for="team in activeTeams"
          :key="team.id"
          class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden"
        >
          <!-- Team header -->
          <div class="flex items-center justify-between px-4 py-3 bg-teal-50 dark:bg-teal-900/20 border-b border-neutral-200 dark:border-neutral-700 flex-wrap gap-2">
            <div class="flex items-center gap-3">
              <span class="text-sm font-semibold text-teal-900 dark:text-teal-100">{{ team.name }}</span>
              <span class="text-xs text-teal-700 dark:text-teal-300 opacity-70">od {{ fmtDate(team.created_at) }}</span>
              <span class="px-2 py-0.5 text-xs rounded-full bg-teal-100 dark:bg-teal-800 text-teal-700 dark:text-teal-200">
                {{ team.workers.length }} {{ team.workers.length === 1 ? 'radnik' : 'radnika' }}
              </span>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" @click="team.expanded = !team.expanded"
                      class="text-xs px-2.5 py-1 rounded-lg border border-neutral-300 dark:border-neutral-600 text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
                {{ team.expanded ? 'Zatvori' : 'Uredi radnike' }}
              </button>
              <!-- Dismiss with inline confirm -->
              <template v-if="confirmDismissId === team.id">
                <span class="text-xs text-red-600 dark:text-red-400 font-medium">Raspustiti tim?</span>
                <button type="button" @click="doDismiss(team)"
                        :disabled="team.dismissing"
                        class="text-xs px-2.5 py-1 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-50">
                  {{ team.dismissing ? '...' : 'Da, raspusti' }}
                </button>
                <button type="button" @click="confirmDismissId = null"
                        class="text-xs px-2.5 py-1 rounded-lg border border-neutral-300 dark:border-neutral-600 text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
                  Otkaži
                </button>
              </template>
              <button v-else type="button" @click="confirmDismissId = team.id"
                      class="text-xs px-2.5 py-1 rounded-lg border border-red-300 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                Raspusti tim
              </button>
            </div>
          </div>

          <!-- Workers list (collapsed view) -->
          <div v-if="!team.expanded" class="px-4 py-3">
            <div v-if="team.workers.length === 0" class="text-sm text-zinc-400 italic">Nema radnika u timu.</div>
            <div v-else class="flex flex-wrap gap-2">
              <span
                v-for="w in team.workers" :key="w.id"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-neutral-100 dark:bg-neutral-800 text-xs text-zinc-700 dark:text-zinc-300"
              >
                <span class="size-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">{{ w.name.charAt(0).toUpperCase() }}</span>
                {{ w.name }}
              </span>
            </div>
          </div>

          <!-- Workers edit (expanded) -->
          <div v-else class="p-4 space-y-4 border-t border-neutral-100 dark:border-neutral-800">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">Radnici koji su u aktivnim timovima (na bilo kom projektu) su onemogućeni, osim ako su već u ovom timu.</p>
            <div v-if="allWorkers.length === 0" class="text-sm text-zinc-400 italic">Nema radnika u sistemu.</div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
              <label
                v-for="w in allWorkers"
                :key="w.id"
                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border text-sm transition-colors"
                :class="isWorkerBusyForTeamEdit(team, w.id)
                  ? 'border-neutral-200 dark:border-neutral-700 opacity-40 cursor-not-allowed'
                  : team.selected.includes(w.id)
                    ? 'border-teal-400 bg-teal-50 dark:bg-teal-900/20 cursor-pointer'
                    : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 cursor-pointer'"
              >
                <input
                  type="checkbox"
                  :value="w.id"
                  v-model="team.selected"
                  :disabled="isWorkerBusyForTeamEdit(team, w.id)"
                  class="rounded border-neutral-300 text-teal-600"
                >
                <span class="flex-1 text-zinc-800 dark:text-zinc-200">{{ w.name }}</span>
                <span v-if="isWorkerBusyForTeamEdit(team, w.id)" class="text-xs text-red-500">zauzet</span>
              </label>
            </div>

            <div v-if="team.saveError" class="text-sm text-red-600 dark:text-red-400">{{ team.saveError }}</div>
            <p v-if="team.saveOk" class="text-sm text-emerald-600 dark:text-emerald-400">Sačuvano.</p>

            <button type="button" @click="saveTeamWorkers(team)"
                    :disabled="team.saving"
                    class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50">
              {{ team.saving ? 'Sprema se...' : 'Sačuvaj radnike' }}
            </button>
          </div>

          <div v-if="team.dismissError"
               class="px-4 py-2 text-sm text-red-600 dark:text-red-400 border-t border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
            {{ team.dismissError }}
          </div>
        </div>
      </section>

      <!-- ISTORIJA -->
      <section v-if="dismissedTeams.length > 0">
        <div
          class="flex items-center justify-between px-4 py-3 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 cursor-pointer select-none hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
          @click="showHistory = !showHistory"
        >
          <div class="flex items-center gap-2">
            <svg class="size-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-semibold text-zinc-600 dark:text-zinc-300">Istorija raspuštenih timova</span>
            <span class="px-2 py-0.5 text-xs rounded-full bg-neutral-200 dark:bg-neutral-700 text-zinc-500 dark:text-zinc-400">{{ dismissedTeams.length }}</span>
          </div>
          <svg
            class="size-4 text-zinc-400 transition-transform duration-200"
            :class="showHistory ? 'rotate-180' : ''"
            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
          >
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
          </svg>
        </div>

        <div v-if="showHistory" class="mt-2 space-y-3">
          <div
            v-for="team in dismissedTeams"
            :key="team.id"
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden"
          >
            <div class="flex items-center justify-between px-4 py-3 bg-neutral-50 dark:bg-neutral-800/50 border-b border-neutral-200 dark:border-neutral-700">
              <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ team.name }}</span>
                <span class="px-2 py-0.5 text-xs rounded-full bg-neutral-200 dark:bg-neutral-700 text-zinc-500">raspušten</span>
              </div>
              <span class="text-xs text-zinc-400 dark:text-zinc-500">
                {{ fmtDate(team.created_at) }} -> {{ fmtDate(team.finished_at) }}
              </span>
            </div>
            <div class="px-4 py-3">
              <div v-if="team.workers.length === 0" class="text-sm text-zinc-400 italic">Bez radnika.</div>
              <div v-else class="flex flex-wrap gap-2">
                <span
                  v-for="w in team.workers" :key="w.id"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-neutral-100 dark:bg-neutral-800 text-xs text-zinc-500 dark:text-zinc-400"
                >
                  <span class="size-5 rounded-full bg-neutral-400 text-white text-xs font-bold flex items-center justify-center">{{ w.name.charAt(0).toUpperCase() }}</span>
                  {{ w.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const loading        = ref(true)
const serverError    = ref('')
const projectName    = ref('')
const activeTeams    = ref([])
const dismissedTeams = ref([])
const allWorkers     = ref([])
const busyWorkerIds  = ref([])
const availableCatalogTeams = ref([])

const showAddForm = ref(false)
const showHistory = ref(false)
const addForm = reactive({ catalog_team_id: null, worker_ids: [] })
const addSaving = ref(false)
const addError  = ref('')
const confirmDismissId = ref(null)

const projectId = window.location.pathname.split('/').at(-2)

function fmtDate(isoStr) {
  if (!isoStr) return ''
  return new Date(isoStr).toLocaleDateString('bs-BA', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function hdrs() {
  return {
    'Content-Type': 'application/json',
    Accept:         'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

function buildTeamItem(t) {
  return {
    ...t,
    selected:     t.workers.map(w => w.id),
    expanded:     false,
    saving:       false,
    saveOk:       false,
    saveError:    '',
    dismissing:   false,
    dismissError: '',
  }
}

function isWorkerBusyForTeamEdit(team, workerId) {
  return busyWorkerIds.value.includes(workerId) && !team.selected.includes(workerId)
}

async function submitAddTeam() {
  addError.value  = ''
  addSaving.value = true
  try {
    const res  = await fetch(`${BASE}/api/vodja/projects/${projectId}/teams`, {
      method:  'POST',
      headers: hdrs(),
      body:    JSON.stringify({ catalog_team_id: addForm.catalog_team_id, worker_ids: addForm.worker_ids }),
    })
    const json = await res.json()
    if (!res.ok) { addError.value = json.message ?? 'Greška.'; return }

    activeTeams.value.push(buildTeamItem(json.team))
    addForm.worker_ids.forEach(id => {
      if (!busyWorkerIds.value.includes(id)) busyWorkerIds.value.push(id)
    })
    availableCatalogTeams.value = availableCatalogTeams.value.filter(t => t.id !== addForm.catalog_team_id)
    addForm.catalog_team_id = null
    addForm.worker_ids      = []
    showAddForm.value       = false
  } finally {
    addSaving.value = false
  }
}

async function doDismiss(team) {
  team.dismissing   = true
  team.dismissError = ''
  try {
    const res  = await fetch(`${BASE}/api/vodja/project-teams/${team.id}/dismiss`, {
      method:  'POST',
      headers: hdrs(),
    })
    const json = await res.json()
    if (!res.ok) { team.dismissError = json.message ?? 'Greška.'; return }

    team.finished_at = json.finished_at
    activeTeams.value = activeTeams.value.filter(t => t.id !== team.id)
    dismissedTeams.value.unshift({
      id:          team.id,
      name:        team.name,
      created_at:  team.created_at,
      finished_at: team.finished_at,
      workers:     team.workers,
    })
    const stillBusy = new Set(activeTeams.value.flatMap(t => t.selected))
    busyWorkerIds.value    = busyWorkerIds.value.filter(id => stillBusy.has(id))
    confirmDismissId.value = null
    showHistory.value      = true
  } finally {
    team.dismissing = false
  }
}

async function saveTeamWorkers(team) {
  team.saving    = true
  team.saveError = ''
  team.saveOk    = false
  try {
    const res  = await fetch(`${BASE}/api/vodja/teams/${team.id}/workers`, {
      method:  'PUT',
      headers: hdrs(),
      body:    JSON.stringify({ user_ids: team.selected }),
    })
    const json = await res.json()
    if (!res.ok) { team.saveError = json.message ?? 'Greška.'; return }

    team.workers = allWorkers.value
      .filter(w => team.selected.includes(w.id))
      .map(w => ({ id: w.id, name: w.name }))
    busyWorkerIds.value = [...new Set(activeTeams.value.flatMap(t => t.selected))]
    team.saveOk = true
    setTimeout(() => { team.saveOk = false }, 3000)
  } finally {
    team.saving = false
  }
}

onMounted(async () => {
  try {
    const res  = await fetch(`${BASE}/api/vodja/projects/${projectId}/team-workers`, { headers: { Accept: 'application/json' } })
    if (!res.ok) { serverError.value = 'Greška pri učitavanju.'; return }
    const data = await res.json()

    projectName.value           = data.project_name ?? ''
    allWorkers.value            = data.all_workers ?? []
    busyWorkerIds.value         = data.busy_worker_ids ?? []
    availableCatalogTeams.value = data.available_catalog_teams ?? []
    activeTeams.value           = (data.active_teams ?? []).map(buildTeamItem)
    dismissedTeams.value        = data.dismissed_teams ?? []
  } catch (e) {
    serverError.value = e.message
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
@reference "../../../css/app.css";
.select-field {
  @apply w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500;
}
</style>
