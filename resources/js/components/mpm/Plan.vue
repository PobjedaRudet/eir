<template>
  <div class="max-w-3xl">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <a :href="BASE + '/pm/projekti'"
         class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </a>
      <div>
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Radni plan</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ project?.name ?? '...' }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="size-8 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- Error -->
    <div v-else-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
      {{ serverError }}
    </div>

    <template v-else>
      <!-- No plan yet -->
      <div v-if="!plan && !showCreateForm"
           class="mb-6 p-8 rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 text-center">
        <svg class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
        </svg>
        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nema radnog plana</p>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-4">Kreirajte radni plan i rasporedite radnike u timove.</p>
        <button v-if="projectWorkers.length === 0" disabled
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-300 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 text-sm font-medium cursor-not-allowed">
          Nema radnika na projektu
        </button>
        <button v-else @click="initCreateForm"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">
          + Kreiraj radni plan
        </button>
      </div>

      <!-- ===== CREATE / EDIT FORM ===== -->
      <div v-if="showCreateForm"
           class="mb-6 p-5 rounded-xl border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20">
        <h2 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-4">
          Kreiraj radni plan
        </h2>

        <!-- Description -->
        <div class="mb-5">
          <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Opis plana (opcionalno)</label>
          <textarea v-model="createForm.description" rows="2"
                    placeholder="Kratki opis zadataka, napomene..."
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>

        <!-- Teams -->
        <div class="space-y-4 mb-4">
          <div v-for="(team, ti) in createForm.teams" :key="ti"
               class="p-4 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700">

            <!-- Team header -->
            <div class="flex items-center gap-2 mb-3">
              <input v-model="team.name" type="text" :placeholder="'Naziv tima ' + (ti + 1)"
                     class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 px-3 py-1.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <button v-if="createForm.teams.length > 1"
                      @click="removeTeam(ti)"
                      class="p-1.5 rounded-lg text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
              </button>
            </div>

            <!-- Worker checkboxes -->
            <div class="space-y-1.5 max-h-44 overflow-y-auto pr-1">
              <label v-for="worker in projectWorkers" :key="worker.id"
                     class="flex items-center gap-3 p-2 rounded-lg border cursor-pointer transition-colors"
                     :class="team.worker_ids.includes(worker.id)
                       ? 'border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/30'
                       : 'border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700'">
                <input type="checkbox" :value="worker.id" v-model="team.worker_ids"
                       class="size-4 rounded text-blue-600 border-zinc-300 dark:border-zinc-600 shrink-0" />
                <div class="flex items-center gap-2 min-w-0">
                  <div class="size-6 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center shrink-0">
                    <span class="text-xs font-bold text-blue-700 dark:text-blue-400">{{ worker.name[0].toUpperCase() }}</span>
                  </div>
                  <span class="text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ worker.name }}</span>
                </div>
              </label>
            </div>
            <p class="mt-1.5 text-xs text-zinc-400">{{ team.worker_ids.length }} odabrano</p>
          </div>
        </div>

        <!-- Add team button -->
        <button @click="addTeam"
                class="mb-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-dashed border-blue-400 dark:border-blue-600 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
          <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
          </svg>
          Dodaj tim
        </button>

        <p v-if="createError" class="text-xs text-red-600 dark:text-red-400 mb-2">{{ createError }}</p>
        <div class="flex gap-2">
          <button @click="createPlan" :disabled="creating"
                  class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
            {{ creating ? 'Kreiranje...' : 'Kreiraj plan' }}
          </button>
          <button @click="showCreateForm = false; createError = ''"
                  class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
            Odustani
          </button>
        </div>
      </div>

      <!-- ===== ACTIVE PLAN CARD ===== -->
      <div v-if="plan" class="p-5 rounded-xl border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-400">Aktivan plan</span>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200">
                v.{{ plan.version }}
              </span>
            </div>
            <p v-if="plan.description" class="text-sm text-green-800 dark:text-green-300 mt-1 italic">"{{ plan.description }}"</p>
            <p class="text-xs text-green-600 dark:text-green-500 mt-1">Kreiran: {{ plan.created_at }}</p>
          </div>

        </div>

        <!-- Teams list -->
        <div class="space-y-3">
          <div v-for="team in plan.teams" :key="team.id"
               class="p-3 rounded-lg bg-white dark:bg-zinc-900 border border-green-200 dark:border-green-800">
            <p class="text-xs font-semibold text-green-800 dark:text-green-300 uppercase tracking-wider mb-2">
              {{ team.name }}
              <span class="font-normal normal-case text-green-600 dark:text-green-500 ml-1">({{ team.workers.length }} radnika)</span>
            </p>
            <div v-if="team.workers.length === 0" class="text-xs text-zinc-400 italic">Nema radnika u timu.</div>
            <div v-else class="flex flex-wrap gap-1.5">
              <div v-for="worker in team.workers" :key="worker.id"
                   class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                <div class="size-5 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center shrink-0">
                  <span class="text-xs font-bold text-green-700 dark:text-green-400">{{ worker.name[0].toUpperCase() }}</span>
                </div>
                {{ worker.name }}
              </div>
            </div>
          </div>
          <div v-if="!plan.teams.length" class="text-xs text-zinc-400 italic">Nema timova u planu.</div>
        </div>

        <!-- Edit teams inline -->
        <div v-if="editingTeams" class="mt-4 pt-4 border-t border-green-200 dark:border-green-700">
          <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-3">Uredi timove</p>

          <div class="space-y-3 mb-3">
            <div v-for="(team, ti) in editTeams" :key="ti"
                 class="p-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700">
              <div class="flex items-center gap-2 mb-2">
                <input v-model="team.name" type="text" :placeholder="'Naziv tima ' + (ti + 1)"
                       class="flex-1 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 px-3 py-1.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                <button v-if="editTeams.length > 1"
                        @click="editTeams.splice(ti, 1)"
                        class="p-1.5 rounded-lg text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                  </svg>
                </button>
              </div>
              <div class="space-y-1 max-h-36 overflow-y-auto">
                <label v-for="worker in projectWorkers" :key="worker.id"
                       class="flex items-center gap-2.5 p-2 rounded-lg border cursor-pointer transition-colors"
                       :class="team.worker_ids.includes(worker.id)
                         ? 'border-blue-300 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/30'
                         : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800'">
                  <input type="checkbox" :value="worker.id" v-model="team.worker_ids"
                         class="size-4 rounded text-blue-600 border-zinc-300 dark:border-zinc-600 shrink-0" />
                  <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ worker.name }}</span>
                </label>
              </div>
              <p class="mt-1 text-xs text-zinc-400">{{ team.worker_ids.length }} odabrano</p>
            </div>
          </div>

          <button @click="editTeams.push({ name: '', worker_ids: [] })"
                  class="mb-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-dashed border-zinc-400 dark:border-zinc-600 text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
            + Dodaj tim
          </button>

          <div class="flex gap-2">
            <button @click="saveTeams" :disabled="savingTeams"
                    class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors disabled:opacity-50">
              {{ savingTeams ? 'uvanje...' : 'Sačuvaj timove' }}
            </button>
            <button @click="editingTeams = false"
                    class="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
              Odustani
            </button>
          </div>
        </div>
        <button v-else-if="!showCreateForm" @click="startEditTeams"
                class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-green-300 dark:border-green-700 text-xs font-medium text-green-700 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors">
          <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z" />
          </svg>
          Uredi timove
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const project        = ref(null)
const plan           = ref(null)
const projectWorkers = ref([])

const loading        = ref(true)
const serverError    = ref('')
const showCreateForm = ref(false)
const creating       = ref(false)
const createError    = ref('')

const createForm = reactive({ description: '', teams: [] })

const editingTeams = ref(false)
const editTeams    = ref([])
const savingTeams  = ref(false)

function getProjectId() {
  return window.location.pathname.split('/').at(-2)
}

function hdrs() {
  return {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

function makeTeam(name = '', worker_ids = []) {
  return { name, worker_ids: [...worker_ids] }
}

function initCreateForm() {
  createForm.description = ''
  createForm.teams = [makeTeam('Tim 1')]
  createError.value = ''
  showCreateForm.value = true
}

function addTeam() {
  createForm.teams.push(makeTeam('Tim ' + (createForm.teams.length + 1)))
}

function removeTeam(index) {
  createForm.teams.splice(index, 1)
}

async function loadPlan() {
  loading.value = true
  serverError.value = ''
  try {
    const res  = await fetch(`${BASE}/api/pm/projects/${getProjectId()}/plan`, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    project.value        = data.project
    plan.value           = data.plan
    projectWorkers.value = data.project_workers ?? []
  } catch {
    serverError.value = 'Greška pri učitavanju plana.'
  } finally {
    loading.value = false
  }
}

async function createPlan() {
  createError.value = ''
  // Validate teams
  for (let i = 0; i < createForm.teams.length; i++) {
    if (!createForm.teams[i].name.trim()) {
      createError.value = `Tim ${i + 1} nema naziv.`
      return
    }
  }
  const totalWorkers = createForm.teams.reduce((s, t) => s + t.worker_ids.length, 0)
  if (totalWorkers === 0) {
    createError.value = 'Dodijelite barem jednog radnika u plan.'
    return
  }

  creating.value = true
  try {
    const res = await fetch(`${BASE}/api/pm/projects/${getProjectId()}/plan`, {
      method: 'POST',
      headers: hdrs(),
      body: JSON.stringify({
        description: createForm.description,
        teams: createForm.teams.map(t => ({ name: t.name, worker_ids: t.worker_ids })),
      }),
    })
    const data = await res.json()
    if (!res.ok) { createError.value = data.message ?? 'Greška.'; return }
    plan.value = data.plan
    showCreateForm.value = false
  } finally {
    creating.value = false
  }
}

function startEditTeams() {
  editTeams.value = plan.value.teams.map(t => makeTeam(t.name, t.workers.map(w => w.id)))
  editingTeams.value = true
}

async function saveTeams() {
  for (let i = 0; i < editTeams.value.length; i++) {
    if (!editTeams.value[i].name.trim()) {
      alert(`Tim ${i + 1} nema naziv.`)
      return
    }
  }
  savingTeams.value = true
  try {
    const res = await fetch(`${BASE}/api/pm/plans/${plan.value.id}/teams`, {
      method: 'PUT',
      headers: hdrs(),
      body: JSON.stringify({
        teams: editTeams.value.map(t => ({ name: t.name, worker_ids: t.worker_ids })),
      }),
    })
    const data = await res.json()
    if (res.ok) {
      plan.value = { ...plan.value, teams: data.teams }
      editingTeams.value = false
    }
  } finally {
    savingTeams.value = false
  }
}

onMounted(() => {
  loadPlan()
  document.addEventListener('livewire:navigated', loadPlan)
})
</script>
