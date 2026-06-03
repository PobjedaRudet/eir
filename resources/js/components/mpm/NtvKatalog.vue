<template>
  <div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="BASE + '/pm/portal'"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">NTV Katalog</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Upravljajte razvodnim kutijama (NTV).</p>
      </div>
    </div>

    <!-- Add form -->
    <div class="mb-6 p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900">
      <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-4">Dodaj novu razvodnu kutiju</h2>
      <div class="flex gap-3">
        <input
          v-model="newName"
          type="text"
          maxlength="100"
          placeholder="Naziv NTV-a (npr. 8V8001)"
          class="flex-1 px-3 py-2 rounded-lg border text-sm bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
          @keydown.enter.prevent="addNtv"
        />
        <button
          type="button"
          @click="addNtv"
          :disabled="saving || !newName.trim()"
          class="px-4 py-2 rounded-lg bg-zinc-800 dark:bg-zinc-100 text-white dark:text-zinc-900 text-sm font-medium hover:bg-zinc-700 dark:hover:bg-white transition-colors disabled:opacity-50"
        >
          {{ saving ? 'Dodaje se...' : 'Dodaj' }}
        </button>
      </div>
      <p v-if="formError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ formError }}</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="size-7 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="ntvs.length === 0"
      class="p-8 rounded-xl border-2 border-dashed border-neutral-300 dark:border-neutral-700 text-center text-sm text-zinc-400 dark:text-zinc-500"
    >
      Nema razvodnih kutija. Dodajte prvu klikom gore.
    </div>

    <!-- NTV list -->
    <div v-else class="rounded-xl border border-neutral-200 dark:border-neutral-700 divide-y divide-neutral-200 dark:divide-neutral-700 overflow-hidden">
      <div
        v-for="ntv in ntvs"
        :key="ntv.id"
        class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors"
      >
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ ntv.name }}</p>
        </div>
        <button
          @click="confirmDelete(ntv)"
          class="shrink-0 p-1.5 rounded-lg text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        >
          <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Delete confirm modal -->
    <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-xl">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-2">Potvrdite brisanje</h3>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-5">
          Sigurno želite obrisati <strong>{{ deleteTarget.name }}</strong>?
          NTV se ne može obrisati ako je dodijeljen projektu.
        </p>
        <p v-if="deleteError" class="mb-3 text-sm text-red-600 dark:text-red-400">{{ deleteError }}</p>
        <div class="flex gap-3">
          <button @click="executeDelete" :disabled="deleting"
                  class="flex-1 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
            {{ deleting ? 'Briše se...' : 'Da, obriši' }}
          </button>
          <button @click="deleteTarget = null; deleteError = ''"
                  class="flex-1 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
            Odustani
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const ntvs        = ref([])
const loading     = ref(true)
const saving      = ref(false)
const deleting    = ref(false)
const newName     = ref('')
const formError   = ref('')
const deleteTarget = ref(null)
const deleteError  = ref('')

function hdrs() {
  return {
    'Content-Type': 'application/json',
    Accept:         'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

async function load() {
  loading.value = true
  try {
    const res  = await fetch(BASE + '/api/pm/ntvs', { headers: { Accept: 'application/json' } })
    ntvs.value = await res.json()
  } finally {
    loading.value = false
  }
}

async function addNtv() {
  formError.value = ''
  const name = newName.value.trim()
  if (!name) return
  saving.value = true
  try {
    const res  = await fetch(BASE + '/api/pm/ntvs', { method: 'POST', headers: hdrs(), body: JSON.stringify({ name }) })
    const json = await res.json()
    if (!res.ok) { formError.value = json.errors?.name?.[0] ?? json.message ?? 'Greška.'; return }
    ntvs.value.push(json)
    newName.value = ''
  } finally {
    saving.value = false
  }
}

function confirmDelete(ntv) {
  deleteTarget.value = ntv
  deleteError.value  = ''
}

async function executeDelete() {
  deleteError.value = ''
  deleting.value    = true
  try {
    const res  = await fetch(`${BASE}/api/pm/ntvs/${deleteTarget.value.id}`, { method: 'DELETE', headers: hdrs() })
    if (!res.ok) {
      const json = await res.json().catch(() => ({}))
      deleteError.value = json.message ?? 'NTV se ne može obrisati jer je u upotrebi.'
      return
    }
    ntvs.value = ntvs.value.filter(n => n.id !== deleteTarget.value.id)
    deleteTarget.value = null
  } finally {
    deleting.value = false
  }
}

onMounted(load)
</script>
