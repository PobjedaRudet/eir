<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Projekti</h1>
    </div>

    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <div
      v-else-if="projects.length === 0"
      class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl"
    >
      <svg class="mx-auto size-12 text-neutral-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
      </svg>
      <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Nema projekata</p>
      <p class="mt-1 text-sm text-zinc-500">Projekti se kreiraju od strane projekt menadžera.</p>
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
          <div class="flex flex-col items-end gap-1 shrink-0">
            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300">
              {{ project.entries_count }} {{ project.entries_count === 1 ? 'unos' : 'unosa' }}
            </span>
            <span v-if="project.plan_status"
              class="px-2.5 py-0.5 text-xs font-medium rounded-full"
              :class="{
                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': project.plan_status === 'draft',
                'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': project.plan_status === 'submitted',
                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': project.plan_status === 'approved',
                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': project.plan_status === 'rejected',
              }"
            >
              Plan v{{ project.plan_version }} · {{ { draft: 'Nacrt', submitted: 'Na čekanju', approved: 'Odobren', rejected: 'Odbijen' }[project.plan_status] }}
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

        <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-800 flex flex-wrap gap-3">
          <a
            :href="`${BASE}/vodja/projekti/${project.id}/resursi`"
            class="inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium"
          >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            Upravljaj resursima
          </a>
          <a
            :href="`${BASE}/vodja/projekti/${project.id}/servis`"
            class="inline-flex items-center gap-1.5 text-sm text-orange-600 dark:text-orange-400 hover:underline font-medium"
          >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.96-4.62a2.625 2.625 0 1 0-5.25 0m5.25 0a2.625 2.625 0 0 1-5.25 0" />
            </svg>
            Servisni nalozi
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const projects = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await fetch(BASE + '/api/vodja/projects', { headers: { 'Accept': 'application/json' } })
    projects.value = await res.json()
  } finally {
    loading.value = false
  }
})
</script>
