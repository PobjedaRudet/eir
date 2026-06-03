<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <a
          :href="BASE + '/pm/portal'"
          class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
        >
          <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
          </svg>
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Projekti</h1>
      </div>

    </div>

    <!-- Status tabs -->
    <div class="flex gap-1 mb-5 p-1 bg-neutral-100 dark:bg-neutral-800 rounded-lg w-fit">
      <button
        @click="setTab('aktivan')"
        :class="tab === 'aktivan'
          ? 'bg-white dark:bg-neutral-700 text-zinc-900 dark:text-white shadow-sm'
          : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
      >Aktivni</button>
      <button
        @click="setTab('zakljucen')"
        :class="tab === 'zakljucen'
          ? 'bg-white dark:bg-neutral-700 text-zinc-900 dark:text-white shadow-sm'
          : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all"
      >Zaključeni</button>
    </div>

    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <div
      v-else-if="projects.length === 0"
      class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl"
    >
      <svg class="mx-auto size-12 text-neutral-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
      </svg>
      <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Nema {{ tab === 'aktivan' ? 'aktivnih' : 'zaključenih' }} projekata</p>
      <p v-if="tab === 'aktivan'" class="mt-1 text-sm text-zinc-500">Kreirajte prvi projekat klikom na dugme iznad.</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="project in projects"
        :key="project.id"
        class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 bg-white dark:bg-neutral-900"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ project.name }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              <span class="font-medium">{{ project.city }}</span>
              &middot;
              {{ project.date }}
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span
              v-if="project.status === 'zakljucen'"
              class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-400"
            >Zaključen</span>
            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300">
              {{ project.entries_count }} {{ project.entries_count === 1 ? 'unos' : 'unosa' }}
            </span>
          </div>
        </div>

        <div v-if="project.streets.length" class="mt-3">
          <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Ulice:</p>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="street in project.streets"
              :key="street.id"
              class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300"
            >{{ street.name }}</span>
          </div>
        </div>

        <!-- Workers summary + actions -->
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-neutral-100 dark:border-neutral-800 pt-3">
          <div class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            <span v-if="project.workers_count === 0">Nema dodijeljenih radnika</span>
            <span v-else>{{ project.workers_count }} {{ project.workers_count === 1 ? 'radnik' : (project.workers_count < 5 ? 'radnika' : 'radnika') }}</span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <a
              :href="BASE + '/pm/projekti/' + project.id + '/radnici'"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
            >
              <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
              </svg>
              Upravljaj radnicima
            </a>
            <a
              :href="BASE + '/pm/projekti/' + project.id + '/plan'"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
            >
              <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 4a2 2 0 0 1 2-2h4.586A2 2 0 0 1 12 2.586L15.414 6A2 2 0 0 1 16 7.414V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4Zm2 6a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H7Z" clip-rule="evenodd" />
              </svg>
              Radni plan
            </a>
            <a
              :href="BASE + '/api/pm/projects/' + project.id + '/export'"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950 transition-colors"
            >
              <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 0 1 1 1v6.586l1.293-1.293a1 1 0 1 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0l-3-3a1 1 0 1 1 1.414-1.414L9 10.586V4a1 1 0 0 1 1-1ZM3 15a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
              </svg>
              Excel export
            </a>
            <button
              @click="toggleStatus(project)"
              :disabled="toggling === project.id"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors disabled:opacity-50"
              :class="project.status === 'aktivan'
                ? 'border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30'
                : 'border-green-200 dark:border-green-700 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30'"
            >
              <svg v-if="project.status === 'aktivan'" class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
              </svg>
              <svg v-else class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M14.5 1A4.5 4.5 0 0 0 10 5.5V9H3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-3V5.5A4.5 4.5 0 0 0 14.5 1Zm-4.5 8V5.5a3 3 0 1 1 6 0V9h-6Z" clip-rule="evenodd" />
              </svg>
              {{ project.status === 'aktivan' ? 'Zaključi' : 'Aktiviraj' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const projects = ref([])
const loading  = ref(true)
const tab      = ref('aktivan')
const toggling = ref(null)

async function loadProjects() {
  loading.value = true
  try {
    const res = await fetch(BASE + '/api/pm/projects?status=' + tab.value, { headers: { 'Accept': 'application/json' } })
    projects.value = await res.json()
  } finally {
    loading.value = false
  }
}

function setTab(value) {
  tab.value = value
  loadProjects()
}

async function toggleStatus(project) {
  toggling.value = project.id
  try {
    const res = await fetch(BASE + '/api/pm/projects/' + project.id + '/toggle-status', {
      method: 'PATCH',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
    })
    if (res.ok) {
      projects.value = projects.value.filter(p => p.id !== project.id)
    }
  } finally {
    toggling.value = null
  }
}

onMounted(loadProjects)
</script>

