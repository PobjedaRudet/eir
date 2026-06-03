<template>
  <div class="max-w-lg">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Timovi — katalog</h1>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Kreirajte timove koji se koriste pri postavljanju projekata.</p>
    </div>

    <!-- Create form -->
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-5 mb-6">
      <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3">Novi tim</h2>
      <form @submit.prevent="create" class="flex gap-2">
        <input
          v-model="newName"
          type="text"
          required
          placeholder="Naziv tima (npr. Tim A)"
          class="flex-1 rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-teal-500"
        >
        <button
          type="submit"
          :disabled="creating"
          class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50 shrink-0"
        >
          {{ creating ? '...' : 'Dodaj' }}
        </button>
      </form>
      <p v-if="createError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ createError }}</p>
    </div>

    <!-- List -->
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
      <div class="px-5 py-3 border-b border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Timovi</h2>
        <span class="text-xs text-zinc-400">{{ teams.length }} ukupno</span>
      </div>

      <div v-if="loading" class="py-10 text-center text-sm text-zinc-400">Učitavanje...</div>
      <div v-else-if="teams.length === 0" class="py-10 text-center text-sm text-zinc-400">Nema kreiranih timova.</div>
      <ul v-else class="divide-y divide-neutral-100 dark:divide-neutral-800">
        <li
          v-for="team in teams"
          :key="team.id"
          class="flex items-center gap-3 px-5 py-3"
        >
          <div class="size-8 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center shrink-0">
            <svg class="size-4 text-teal-600 dark:text-teal-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM6 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM1.49 15.326a.78.78 0 0 1-.358-.442 3 3 0 0 1 4.308-3.516 6.484 6.484 0 0 0-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 0 1-2.07-.655ZM16.44 15.98a4.97 4.97 0 0 0 2.07-.654.78.78 0 0 0 .357-.442 3 3 0 0 0-4.308-3.517 6.484 6.484 0 0 1 1.907 3.96 2.32 2.32 0 0 1-.026.654ZM18 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM5.304 16.19a.844.844 0 0 1-.277-.71 5 5 0 0 1 9.947 0 .843.843 0 0 1-.277.71A6.975 6.975 0 0 1 10 18a6.974 6.974 0 0 1-4.696-1.81Z" />
            </svg>
          </div>
          <span class="flex-1 text-sm font-medium text-zinc-900 dark:text-white">{{ team.name }}</span>
          <button
            type="button"
            @click="remove(team)"
            class="text-xs text-red-500 hover:text-red-700 transition-colors"
          >Obriši</button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const loading     = ref(true)
const teams       = ref([])
const newName     = ref('')
const creating    = ref(false)
const createError = ref('')

function hdrs() {
  return {
    'Content-Type': 'application/json',
    Accept:         'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

async function create() {
  createError.value = ''
  creating.value    = true
  try {
    const res  = await fetch(`${BASE}/api/vodja/teams-catalog`, {
      method:  'POST',
      headers: hdrs(),
      body:    JSON.stringify({ name: newName.value }),
    })
    const json = await res.json()
    if (!res.ok) {
      createError.value = json.errors?.name?.[0] ?? json.message ?? 'Greška.'
      return
    }
    teams.value.push(json.team)
    teams.value.sort((a, b) => a.name.localeCompare(b.name))
    newName.value = ''
  } finally {
    creating.value = false
  }
}

async function remove(team) {
  if (!confirm(`Obrisati tim "${team.name}"?`)) return
  const res = await fetch(`${BASE}/api/vodja/teams-catalog/${team.id}`, { method: 'DELETE', headers: hdrs() })
  if (res.ok) teams.value = teams.value.filter(t => t.id !== team.id)
}

onMounted(async () => {
  try {
    const res  = await fetch(`${BASE}/api/vodja/teams-catalog`, { headers: { Accept: 'application/json' } })
    const json = await res.json()
    if (res.ok) teams.value = json.teams
  } finally {
    loading.value = false
  }
})
</script>
