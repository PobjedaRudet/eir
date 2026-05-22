<template>
  <div class="max-w-2xl">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="BASE + '/pm/projekti'"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Radnici na projektu</h1>
        <p v-if="project" class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ project.name }}</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <div v-else-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
      {{ serverError }}
    </div>

    <template v-else>
      <!-- No workers available -->
      <div
        v-if="available.length === 0"
        class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl"
      >
        <svg class="mx-auto size-12 text-neutral-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
        </svg>
        <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Nema registriranih radnika</p>
        <p class="mt-1 text-sm text-zinc-500">Radnici se dodaju u sistem od strane administratora.</p>
      </div>

      <!-- Worker list -->
      <div v-else>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
          Odaberite radnike koji su dodijeljeni ovom projektu. Promjene se čuvaju klikom na dugme.
        </p>

        <div class="space-y-2 mb-6">
          <label
            v-for="worker in available"
            :key="worker.id"
            class="flex items-center gap-4 cursor-pointer px-4 py-3 rounded-xl border transition-colors"
            :class="selected.includes(worker.id)
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
              : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800'"
          >
            <input
              type="checkbox"
              :value="worker.id"
              v-model="selected"
              class="rounded border-neutral-300 text-blue-600 focus:ring-blue-500"
            >
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ worker.name }}</p>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ worker.email }}</p>
            </div>
            <span
              v-if="selected.includes(worker.id)"
              class="shrink-0 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300"
            >Dodijeljen</span>
          </label>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="button"
            :disabled="saving"
            @click="save"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50"
          >
            {{ saving ? 'uvanje...' : 'Sačuvaj promjene' }}
          </button>
          <a :href="BASE + '/pm/projekti'" class="px-4 py-2 rounded-lg border border-neutral-200 dark:border-neutral-700 text-zinc-700 dark:text-zinc-300 text-sm font-medium hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
            Odustani
          </a>
          <span v-if="saveSuccess" class="text-sm text-green-600 dark:text-green-400 flex items-center gap-1">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
            </svg>
            Sačuvano
          </span>
        </div>

        <div v-if="saveError" class="mt-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
          {{ saveError }}
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const project = ref(null)
const available = ref([])
const selected = ref([])
const loading = ref(true)
const saving = ref(false)
const serverError = ref('')
const saveError = ref('')
const saveSuccess = ref(false)

function getProjectId() {
  const parts = window.location.pathname.split('/')
  // path: /pm/projekti/{id}/radnici
  const idx = parts.indexOf('projekti')
  return idx !== -1 ? parts[idx + 1] : null
}

function getCsrf() {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

async function save() {
  saveError.value = ''
  saveSuccess.value = false
  saving.value = true

  try {
    const id = getProjectId()
    const res = await fetch(`${BASE}/api/pm/projects/${id}/workers`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': getCsrf(),
      },
      body: JSON.stringify({ user_ids: selected.value }),
    })

    const json = await res.json()

    if (!res.ok) {
      saveError.value = json.message ?? 'Greška pri čuvanju.'
      return
    }

    saveSuccess.value = true
    setTimeout(() => { saveSuccess.value = false }, 3000)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  const id = getProjectId()
  if (!id) {
    serverError.value = 'Nije pronađen ID projekta u URL-u.'
    loading.value = false
    return
  }

  try {
    const res = await fetch(`${BASE}/api/pm/projects/${id}/workers`, {
      headers: { 'Accept': 'application/json' },
    })

    if (!res.ok) {
      const json = await res.json().catch(() => null)
      serverError.value = json?.message ?? 'Greška pri učitavanju radnika.'
      return
    }

    const data = await res.json()
    project.value = data.project
    available.value = data.available
    selected.value = data.assigned.map(w => w.id)
  } catch {
    serverError.value = 'Greška pri učitavanju radnika.'
  } finally {
    loading.value = false
  }
})
</script>
