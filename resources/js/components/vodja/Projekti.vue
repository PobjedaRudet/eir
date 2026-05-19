<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Projekti</h1>
      <a
        :href="BASE + '/vodja/novi-projekat'"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
      >
        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
        </svg>
        Novi projekat
      </a>
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
      <p class="mt-1 text-sm text-zinc-500">Kreirajte prvi projekat klikom na dugme iznad.</p>
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
          <span class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-neutral-200 dark:border-neutral-700 text-zinc-600 dark:text-zinc-300">
            {{ project.entries_count }} {{ project.entries_count === 1 ? 'unos' : 'unosa' }}
          </span>
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
