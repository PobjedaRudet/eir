<template>
  <div class="max-w-2xl">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Radnici</h1>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Upravljajte računima radnika na terenu.</p>
    </div>

    <!-- Create worker form -->
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-5 mb-6">
      <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-4">Novi radnik</h2>
      <form @submit.prevent="create" class="space-y-3">
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ime i prezime</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Npr. Mujo Mujić"
              class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>
          <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="radnik@firma.ba"
              class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Lozinka</label>
          <input
            v-model="form.password"
            type="password"
            required
            minlength="8"
            placeholder="Min. 8 znakova"
            class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
        </div>

        <div class="flex items-center gap-3 pt-1">
          <button
            type="submit"
            :disabled="creating"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50"
          >
            {{ creating ? 'Kreiranje...' : 'Kreiraj radnika' }}
          </button>
          <span v-if="createSuccess" class="text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
            </svg>
            Radnik kreiran
          </span>
          <span v-if="createError" class="text-sm text-red-600 dark:text-red-400">{{ createError }}</span>
        </div>
      </form>
    </div>

    <!-- Workers list -->
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
      <div class="px-5 py-3 border-b border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Registrirani radnici</h2>
        <span class="text-xs text-zinc-400">{{ workers.length }} ukupno</span>
      </div>

      <div v-if="loading" class="py-10 text-center text-sm text-zinc-400">Učitavanje...</div>
      <div v-else-if="workers.length === 0" class="py-10 text-center text-sm text-zinc-400">Nema registriranih radnika.</div>
      <ul v-else class="divide-y divide-neutral-100 dark:divide-neutral-800">
        <li
          v-for="w in workers"
          :key="w.id"
          class="flex items-center gap-3 px-5 py-3"
        >
          <div class="size-8 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center shrink-0">
            <span class="text-xs font-semibold text-teal-700 dark:text-teal-300">{{ initials(w.name) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ w.name }}</p>
            <p class="text-xs text-zinc-400 truncate">{{ w.email }}</p>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const loading       = ref(true)
const workers       = ref([])
const creating      = ref(false)
const createSuccess = ref(false)
const createError   = ref('')

const form = reactive({ name: '', email: '', password: '' })

function initials(name) {
  return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('')
}

function hdrs() {
  return {
    'Content-Type': 'application/json',
    Accept:         'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

async function create() {
  createError.value   = ''
  createSuccess.value = false
  creating.value      = true
  try {
    const res  = await fetch(`${BASE}/api/vodja/workers`, {
      method:  'POST',
      headers: hdrs(),
      body:    JSON.stringify({ name: form.name, email: form.email, password: form.password }),
    })
    const json = await res.json()
    if (!res.ok) {
      createError.value = json.errors
        ? Object.values(json.errors).flat().join(' ')
        : (json.message ?? 'Greška pri kreiranju.')
      return
    }
    workers.value.push(json.worker)
    workers.value.sort((a, b) => a.name.localeCompare(b.name))
    form.name     = ''
    form.email    = ''
    form.password = ''
    createSuccess.value = true
    setTimeout(() => { createSuccess.value = false }, 3000)
  } finally {
    creating.value = false
  }
}

onMounted(async () => {
  try {
    const res  = await fetch(`${BASE}/api/vodja/workers`, { headers: { Accept: 'application/json' } })
    const json = await res.json()
    if (res.ok) workers.value = json.workers
  } finally {
    loading.value = false
  }
})
</script>
