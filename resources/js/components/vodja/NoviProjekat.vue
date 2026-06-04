<template>
  <div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="BASE + '/vodja/projekti'"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Novi projekat</h1>
    </div>

    <div v-if="configLoading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <form v-else @submit.prevent="save" class="space-y-8">

      <!-- ── OSNOVI PODACI ──────────────────────────────────────── -->
      <section class="space-y-5">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 border-b border-neutral-200 dark:border-neutral-700 pb-2">Osnovni podaci</h2>

        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Naziv projekta</label>
          <input type="text" v-model="form.name" placeholder="Unesite naziv projekta" required class="input-field">
          <p v-if="errors['name']" class="mt-1 text-sm text-red-600">{{ errors['name'][0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Datum</label>
          <input type="date" v-model="form.date" required class="input-field">
          <p v-if="errors['date']" class="mt-1 text-sm text-red-600">{{ errors['date'][0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Grad</label>
          <select v-model="form.city_id" @change="onCityChange" class="select-field">
            <option value="">Izaberite grad</option>
            <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
          </select>
          <p v-if="errors['city_id']" class="mt-1 text-sm text-red-600">{{ errors['city_id'][0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Glavno kablo</label>
          <select v-model="form.cable_type" required class="select-field">
            <option value="">Izaberite tip kabla</option>
            <option value="8Y0001_1">8Y0001_1</option>
            <option value="8Y0001_2">8Y0001_2</option>
            <option value="8Y0001_3">8Y0001_3</option>
          </select>
          <p v-if="errors['cable_type']" class="mt-1 text-sm text-red-600">{{ errors['cable_type'][0] }}</p>
        </div>

        <template v-if="form.city_id">
          <div v-if="streetsLoading" class="text-sm text-zinc-500">Učitavanje ulica...</div>
          <div v-else-if="streets.length === 0" class="p-4 rounded-lg border border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm">
            ⚠ Nema ulica za izabrani grad.
            <a :href="`${BASE}/vodja/gradovi-ulice`" class="ml-1 underline">Dodajte ulice u katalogu.</a>
          </div>
          <div v-else>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
              Ulice projekta <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500">odaberite jednu ili više</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <label
                v-for="street in streets"
                :key="street.id"
                class="flex items-center gap-3 cursor-pointer px-3 py-2 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                :class="form.street_ids.includes(street.id) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
              >
                <input type="checkbox" :value="street.id" v-model="form.street_ids" class="rounded border-neutral-300 text-blue-600">
                <span class="text-sm">{{ street.name }}</span>
              </label>
            </div>
            <p v-if="errors['street_ids']" class="mt-1 text-sm text-red-600">{{ errors['street_ids'][0] }}</p>
          </div>
        </template>
      </section>

      <!-- ── TIMOVI ─────────────────────────────────────────────── -->
      <section class="space-y-4">
        <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-700 pb-2">
          <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Timovi</h2>
          <div class="flex items-center gap-3">
            <button type="button" @click="addTeam" :disabled="teamsCatalog.length === 0"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-600 text-white text-xs font-medium hover:bg-teal-700 transition-colors disabled:opacity-40">
              <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
              </svg>
              Dodaj tim
            </button>
          </div>
        </div>

        <div v-if="teamsCatalog.length === 0"
             class="text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3">
          Nema dostupnih timova za dodjelu projektu.
        </div>
        <div v-else-if="form.teams.length === 0"
             class="text-sm text-zinc-400 dark:text-zinc-500 italic py-2">
          Kliknite "Dodaj tim" da dodate timove projektu.
        </div>

        <div v-for="(team, ti) in form.teams" :key="ti"
             class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">

          <!-- Team header: picker + remove -->
          <div class="flex items-center gap-3 px-4 py-3 bg-teal-50 dark:bg-teal-900/20 border-b border-neutral-200 dark:border-neutral-700">
            <span class="shrink-0 size-6 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">{{ ti + 1 }}</span>
            <select v-model="team.catalog_team_id" class="select-field flex-1 text-sm">
              <option :value="null">— Izaberite tim —</option>
              <option v-for="t in availableCatalogTeams(ti)" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
            <button type="button" @click="removeTeam(ti)"
                    class="shrink-0 p-1.5 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>

          <div class="p-4 space-y-5">

            <!-- Radnici -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">Radnici</span>
              </div>
              <div v-if="workersAll.length === 0" class="text-xs text-zinc-400 italic">Nema radnika u sistemu.</div>
              <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                <label
                  v-for="worker in workersAll"
                  :key="worker.id"
                  class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border transition-colors text-sm"
                  :class="workersBusyForTeam(ti).has(worker.id) && !team.worker_ids.includes(worker.id)
                    ? 'border-neutral-200 dark:border-neutral-700 opacity-40 cursor-not-allowed'
                    : team.worker_ids.includes(worker.id)
                      ? 'border-teal-400 bg-teal-50 dark:bg-teal-900/20 cursor-pointer'
                      : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 cursor-pointer'"
                >
                  <input
                    type="checkbox"
                    :value="worker.id"
                    v-model="team.worker_ids"
                    :disabled="workersBusyForTeam(ti).has(worker.id) && !team.worker_ids.includes(worker.id)"
                    class="rounded border-neutral-300 text-teal-600"
                  >
                  <span class="text-zinc-800 dark:text-zinc-200">{{ worker.name }}</span>
                  <span v-if="workersBusyForTeam(ti).has(worker.id) && !team.worker_ids.includes(worker.id)"
                        class="ml-auto text-xs text-red-500">zauzet</span>
                </label>
              </div>
            </div>

            <!-- NTV-ovi -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">NTV — Razvodne kutije</span>
                <button type="button" @click="addNtvToTeam(ti)"
                        :disabled="!form.city_id || ntvCatalog.length === 0"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition-colors disabled:opacity-40">
                  <svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                  </svg>
                  Dodaj NTV
                </button>
              </div>
              <div v-if="!form.city_id" class="text-xs text-zinc-400 italic">Odaberite grad da biste dodali NTV-ove.</div>
              <div v-else-if="ntvCatalog.length === 0" class="text-xs text-amber-600 dark:text-amber-400">Nema NTV-ova u katalogu. PM treba da doda NTV-ove.</div>
              <div v-else-if="team.ntvs.length === 0" class="text-xs text-zinc-400 italic">Kliknite "Dodaj NTV" za dodjelu razvodnih kutija.</div>
              <div v-for="(ntv, ni) in team.ntvs" :key="ni"
                   class="mt-2 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
                <div class="flex items-center justify-between px-3 py-2 bg-neutral-50 dark:bg-neutral-800/50 border-b border-neutral-200 dark:border-neutral-700">
                  <span class="text-xs text-zinc-500 font-medium">NTV {{ ni + 1 }}</span>
                  <button type="button" @click="removeNtvFromTeam(ti, ni)"
                          class="p-0.5 rounded text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
                <div class="p-3 space-y-3">
                  <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Razvodna kutija</label>
                    <select v-model="ntv.ntv_id" class="select-field text-sm">
                      <option value="">Izaberite NTV</option>
                      <option v-for="n in availableNtvsForRow(ti, ni)" :key="n.id" :value="n.id">{{ n.name }}</option>
                    </select>
                  </div>
                  <div v-if="selectedStreets.length">
                    <label class="block text-xs font-medium text-zinc-500 mb-1">Ulice ovog NTV-a</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                      <label
                        v-for="street in selectedStreets" :key="street.id"
                        class="flex items-center gap-2 cursor-pointer px-2 py-1 rounded border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-xs"
                        :class="ntv.street_ids.includes(street.id) ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' : ''"
                      >
                        <input type="checkbox" :value="street.id" v-model="ntv.street_ids" class="rounded border-neutral-300 text-indigo-600">
                        {{ street.name }}
                      </label>
                    </div>
                  </div>
                  <div v-else class="text-xs text-zinc-400 italic">Odaberite ulice projekta da biste dodijelili ulice NTV-u.</div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- ── SUBMIT ─────────────────────────────────────────────── -->
      <div class="flex items-center gap-3 pt-2">
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Šalje se...' : 'Podnesi na odobrenje' }}
        </button>
        <a :href="BASE + '/vodja/projekti'" class="btn-secondary">Odustani</a>
      </div>

      <div v-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
        {{ serverError }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const cities       = ref([])
const streets      = ref([])
const ntvCatalog     = ref([])
const teamsCatalog   = ref([])
const workersAll     = ref([])
const busyWorkerIds  = ref([])
const configLoading  = ref(true)
const streetsLoading = ref(false)
const saving         = ref(false)
const errors         = ref({})
const serverError    = ref('')

const form = reactive({
  name:       '',
  date:       new Date().toISOString().slice(0, 10),
  city_id:    '',
  street_ids: [],
  cable_type: '',
  teams: [],  // [{ catalog_team_id: null, worker_ids: [], ntvs: [{ ntv_id: '', street_ids: [] }] }]
})

// Streets currently selected for the project (used for NTV street picker)
const selectedStreets = computed(() =>
  streets.value.filter(s => form.street_ids.includes(s.id))
)

// Catalog teams not already selected in another entry
function availableCatalogTeams(teamIndex) {
  const usedIds = form.teams.filter((_, i) => i !== teamIndex).map(t => t.catalog_team_id).filter(Boolean)
  return teamsCatalog.value.filter(t => !usedIds.includes(t.id))
}

// Workers unavailable for a team: busy on active project OR in another team in this form
function workersBusyForTeam(teamIndex) {
  const inOtherTeams = form.teams.flatMap((t, i) => i !== teamIndex ? t.worker_ids : [])
  return new Set([...busyWorkerIds.value, ...inOtherTeams])
}

// NTVs not yet picked in any other NTV row across all teams
function availableNtvsForRow(teamIndex, ntvIndex) {
  const used = form.teams.flatMap((t, ti) =>
    t.ntvs.filter((_, ni) => !(ti === teamIndex && ni === ntvIndex)).map(n => n.ntv_id).filter(Boolean)
  )
  return ntvCatalog.value.filter(n => !used.includes(n.id))
}

function addTeam()                { form.teams.push({ catalog_team_id: null, worker_ids: [], ntvs: [] }) }
function removeTeam(i)            { form.teams.splice(i, 1) }
function addNtvToTeam(ti)         { form.teams[ti].ntvs.push({ ntv_id: '', street_ids: [] }) }
function removeNtvFromTeam(ti, ni){ form.teams[ti].ntvs.splice(ni, 1) }

function getCsrf() {
  return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '')
}

async function onCityChange() {
  form.street_ids = []
  form.teams.forEach(t => t.ntvs.forEach(n => { n.street_ids = [] }))
  streets.value = []
  serverError.value = ''
  if (!form.city_id) return
  streetsLoading.value = true
  try {
    const res = await fetch(`${BASE}/api/vodja/cities/${form.city_id}/streets`, { headers: { Accept: 'application/json' } })
    streets.value = await getJsonOrThrow(res, 'Ne mogu učitati ulice.')
  } catch (e) {
    serverError.value = e.message
  } finally {
    streetsLoading.value = false
  }
}

async function save() {
  errors.value = {}
  serverError.value = ''
  saving.value = true
  try {
    const res = await fetch(BASE + '/api/vodja/projects', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
      body: JSON.stringify({
        name:       form.name,
        date:       form.date,
        city_id:    form.city_id,
        street_ids: form.street_ids,
        cable_type: form.cable_type,
        teams: form.teams.filter(t => t.catalog_team_id).map(t => ({
          catalog_team_id: t.catalog_team_id,
          worker_ids:      t.worker_ids,
          ntvs:            t.ntvs.filter(n => n.ntv_id).map(n => ({
            ntv_id:     n.ntv_id,
            street_ids: n.street_ids,
          })),
        })),
      }),
    })
    const json = await res.json()
    if (!res.ok) {
      if (res.status === 422 && json.errors) errors.value = json.errors
      else serverError.value = json.message ?? 'Greška pri čuvanju.'
      return
    }
    window.location.href = BASE + '/vodja/projekti'
  } catch (e) {
    serverError.value = e instanceof Error ? e.message : 'Greška pri čuvanju.'
  } finally {
    saving.value = false
  }
}

async function getJsonOrThrow(response, fallback) {
  const ct = response.headers.get('content-type') ?? ''
  const payload = ct.includes('application/json') ? await response.json() : null
  if (!response.ok) throw new Error(payload?.message ?? fallback)
  return payload
}

onMounted(async () => {
  try {
    const res  = await fetch(BASE + '/api/vodja/project-form-config', { headers: { Accept: 'application/json' } })
    const data = await getJsonOrThrow(res, 'Ne mogu učitati konfiguraciju.')
    cities.value       = data.cities
    ntvCatalog.value   = data.ntvs ?? []
    teamsCatalog.value = data.teams ?? []
    workersAll.value    = data.workers ?? []
    busyWorkerIds.value = data.busy_worker_ids ?? []
  } catch (e) {
    serverError.value = e.message
  } finally {
    configLoading.value = false
  }
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
  @apply px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors;
}
</style>
