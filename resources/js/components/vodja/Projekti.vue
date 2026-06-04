<template>
  <div>
    <div class="flex items-center justify-between gap-3 mb-6">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Projekti</h1>
      <div class="flex items-center gap-2">
        <a
          :href="BASE + '/vodja/gradovi-ulice'"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-neutral-200 dark:border-neutral-700 text-zinc-700 dark:text-zinc-200 text-sm font-medium hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
        >
          Gradovi i ulice
        </a>
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
    </div>

    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <template v-else>
      <div v-if="projects.length" class="mb-5 inline-flex rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-1 shadow-sm">
        <button
          type="button"
          @click="activeTab = 'open'"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="activeTab === 'open'
            ? 'bg-blue-600 text-white'
            : 'text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
        >
          Aktivni i ostali ({{ openFilteredProjects.length }})
        </button>
        <button
          type="button"
          @click="activeTab = 'closed'"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="activeTab === 'closed'
            ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
            : 'text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
        >
          Zaključeni ({{ closedFilteredProjects.length }})
        </button>
      </div>

      <!-- Filters -->
      <div v-if="projects.length" class="mb-5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 py-3 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex-1 min-w-0">
            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Projekat</label>
            <select v-model="filter.project_id" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-sm text-zinc-800 dark:text-zinc-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Svi projekti</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div class="w-full sm:w-40">
            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Od datuma</label>
            <input type="date" v-model="filter.date_from" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-sm text-zinc-800 dark:text-zinc-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div class="w-full sm:w-40">
            <label class="block text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-1">Do datuma</label>
            <input type="date" v-model="filter.date_to" class="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-sm text-zinc-800 dark:text-zinc-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          <div class="flex items-end">
            <button
              v-if="hasFilter"
              type="button"
              @click="clearFilter"
              class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 border border-red-200 dark:border-red-800 transition-colors"
            >
              <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
              </svg>
              Resetuj
            </button>
          </div>
        </div>
        <p v-if="hasFilter" class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">
          Prikazano {{ visibleProjects.length }} od {{ projects.length }} projekata
        </p>
      </div>

      <div
        v-if="visibleProjects.length === 0"
        class="text-center py-12 border border-dashed border-neutral-300 dark:border-neutral-700 rounded-xl"
      >
        <svg class="mx-auto size-12 text-neutral-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
        </svg>
        <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ hasFilter ? 'Nema rezultata' : activeTab === 'closed' ? 'Nema zaključenih projekata' : 'Nema projekata' }}</p>
        <p class="mt-1 text-sm text-zinc-500">{{ hasFilter ? 'Nijedan projekat ne odgovara odabranim filterima.' : activeTab === 'closed' ? 'Zaključeni projekti će se prikazati na ovoj kartici.' : 'Kreirajte prvi projekat klikom na dugme iznad.' }}</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="project in visibleProjects"
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
            <span v-if="project.ntv_count > 0" class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-300">
              {{ project.ntv_count }} NTV
            </span>
            <span v-if="project.team_count > 0" class="px-2.5 py-0.5 text-xs font-medium rounded-full border border-teal-200 dark:border-teal-800 text-teal-600 dark:text-teal-300">
              {{ project.team_count }} {{ project.team_count === 1 ? 'tim' : 'tima' }}
            </span>
            <!-- Project status badge -->
            <span v-if="project.status === 'na_cekanju'"
              class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
              Na čekanju odobrenja
            </span>
            <span v-else-if="project.status === 'odbijen'"
              class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
              Odbijen
            </span>
            <span v-else-if="project.status === 'zakljucen'"
              class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
              Zaključen
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

        <div v-if="project.teams?.length" class="mt-3">
          <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Timovi, NTV-ovi i ulice:</p>
          <div class="space-y-2">
            <div
              v-for="team in project.teams"
              :key="`${project.id}-team-${team.id}`"
              class="rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50/70 dark:bg-teal-900/10 px-3 py-2"
            >
              <div class="flex flex-wrap items-center gap-2">
                <span class="px-2 py-0.5 text-xs rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                  {{ team.name }}
                </span>
                <span v-if="team.ntvs?.length" class="text-xs text-zinc-500 dark:text-zinc-400">
                  {{ team.ntvs.length }} {{ team.ntvs.length === 1 ? 'NTV' : 'NTV-a' }}
                </span>
                <span v-else class="text-xs text-zinc-400 dark:text-zinc-500">
                  Nema dodijeljenih NTV-ova.
                </span>
              </div>

              <div v-if="team.ntvs?.length" class="mt-2 space-y-2 border-t border-teal-200/70 dark:border-teal-800/60 pt-2">
                <div
                  v-for="ntv in team.ntvs"
                  :key="`${project.id}-team-${team.id}-ntv-${ntv.id}`"
                  class="rounded-lg border border-indigo-200 dark:border-indigo-800 bg-white/80 dark:bg-neutral-900 px-3 py-2"
                >
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                      {{ ntv.name }}
                    </span>
                    <span v-if="ntv.streets?.length" class="text-xs text-zinc-500 dark:text-zinc-400">
                      Ulice:
                    </span>
                    <span v-else class="text-xs text-zinc-400 dark:text-zinc-500">
                      Nema vezanih ulica.
                    </span>
                  </div>

                  <div v-if="ntv.streets?.length" class="mt-2 flex flex-wrap gap-1">
                    <span
                      v-for="street in ntv.streets"
                      :key="`${project.id}-team-${team.id}-ntv-${ntv.id}-street-${street}`"
                      class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-neutral-800 text-zinc-600 dark:text-zinc-300 border border-neutral-200 dark:border-neutral-700"
                    >{{ street }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cable type row -->
        <div class="mt-3 flex items-center gap-2 flex-wrap">
          <span class="text-xs text-zinc-500 dark:text-zinc-400">Glavno kablo:</span>          <template v-if="editingCableType === project.id">
            <select v-model="cableTypeEdit" class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-xs px-2 py-1 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="8Y0001_1">8Y0001_1</option>
              <option value="8Y0001_2">8Y0001_2</option>
              <option value="8Y0001_3">8Y0001_3</option>
            </select>
            <button @click="saveCableType(project)" :disabled="savingCableType"
                    class="px-2.5 py-1 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
              {{ savingCableType ? 'Čuva...' : 'Sačuvaj' }}
            </button>
            <button @click="editingCableType = null"
                    class="px-2.5 py-1 rounded-lg border border-neutral-300 dark:border-neutral-600 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
              Odustani
            </button>
          </template>
          <template v-else>
            <span class="px-2 py-0.5 text-xs rounded-full font-medium"
                  :class="project.cable_type ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400'">
              {{ CABLE_LABELS[project.cable_type] ?? 'Nije odabrano' }}
            </span>
            <button v-if="['na_cekanju','odbijen'].includes(project.status)"
                    @click="startEditCableType(project)"
                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
              Izmijeni
            </button>
          </template>
        </div>

        <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-800 flex flex-wrap gap-3">
          <!-- Rejection note -->
          <div v-if="project.status === 'odbijen' && project.rejection_note"
            class="w-full p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-300">
            <strong>Razlog odbijanja:</strong> {{ project.rejection_note }}
          </div>
          <!-- Resubmit button for rejected projects -->
          <button
            v-if="project.status === 'odbijen'"
            @click="resubmit(project)"
            :disabled="resubmitting === project.id"
            class="inline-flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400 hover:underline font-medium disabled:opacity-50"
          >
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            {{ resubmitting === project.id ? 'Slanje...' : 'Ponovo podnijeti' }}
          </button>
          <!-- Actions only for active/approved projects -->
          <template v-if="project.status === 'aktivan'">
            <button
              @click="toggleStatus(project)"
              :disabled="togglingStatus === project.id"
              class="inline-flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400 hover:underline font-medium disabled:opacity-50"
            >
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
              </svg>
              {{ togglingStatus === project.id ? 'Mijenja status...' : 'Zaključi projekat' }}
            </button>
            <a
              :href="`${BASE}/vodja/projekti/${project.id}/timovi`"
              class="inline-flex items-center gap-1.5 text-sm text-teal-600 dark:text-teal-400 hover:underline font-medium"
            >
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
              </svg>
              Timovi
            </a>
            <a
              :href="`${BASE}/vodja/projekti/${project.id}/gradiliste`"
              class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
            >
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.96-4.62a2.625 2.625 0 1 0-5.25 0m5.25 0a2.625 2.625 0 0 1-5.25 0" />
              </svg>
              Gradilište
            </a>
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
          </template>
          <template v-else-if="project.status === 'zakljucen'">
            <button
              @click="toggleStatus(project)"
              :disabled="togglingStatus === project.id"
              class="inline-flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400 hover:underline font-medium disabled:opacity-50"
            >
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M14.5 1A4.5 4.5 0 0 0 10 5.5V9H3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-3V5.5A4.5 4.5 0 0 0 14.5 1Zm-4.5 8V5.5a3 3 0 1 1 6 0V9h-6Z" clip-rule="evenodd" />
              </svg>
              {{ togglingStatus === project.id ? 'Mijenja status...' : 'Aktiviraj projekat' }}
            </button>
            <button
              v-if="deleteProjectId !== project.id"
              @click="startDeleteProject(project)"
              class="inline-flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400 hover:underline font-medium"
            >
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.75 2.5a1.25 1.25 0 0 0-1.25 1.25V5H5.25a.75.75 0 0 0 0 1.5h.443l.894 8.047A2.25 2.25 0 0 0 8.825 16.5h2.35a2.25 2.25 0 0 0 2.238-1.953l.894-8.047h.443a.75.75 0 0 0 0-1.5H12.5V3.75A1.25 1.25 0 0 0 11.25 2.5h-2.5ZM11 5V4h-2v1h2Z" clip-rule="evenodd" />
              </svg>
              Izbriši projekat
            </button>
          </template>
        </div>

        <div v-if="deleteProjectId === project.id" class="mt-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
          <p class="text-sm font-medium text-red-700 dark:text-red-300">Za brisanje ovog projekta unesite kod 0000.</p>
          <div class="mt-2 flex flex-col sm:flex-row gap-2">
            <input
              v-model="deleteProjectCode"
              type="text"
              inputmode="numeric"
              maxlength="4"
              placeholder="Unesite kod 0000"
              class="w-full sm:w-52 rounded-lg border border-red-200 dark:border-red-800 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-red-500"
            >
            <div class="flex gap-2">
              <button
                @click="confirmDeleteProject(project)"
                :disabled="deletingProjectId === project.id"
                class="px-3 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-50"
              >
                {{ deletingProjectId === project.id ? 'Brisanje...' : 'Potvrdi brisanje' }}
              </button>
              <button
                @click="cancelDeleteProject"
                :disabled="deletingProjectId === project.id"
                class="px-3 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors disabled:opacity-50"
              >
                Odustani
              </button>
            </div>
          </div>
          <p v-if="deleteProjectError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ deleteProjectError }}</p>
        </div>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const projects = ref([])
const loading = ref(true)
const resubmitting = ref(null)
const togglingStatus = ref(null)
const editingCableType = ref(null)
const cableTypeEdit = ref('')
const savingCableType = ref(false)
const activeTab = ref('open')
const deleteProjectId = ref(null)
const deleteProjectCode = ref('')
const deleteProjectError = ref('')
const deletingProjectId = ref(null)

const CABLE_LABELS = {
  '8Y0001_1': '8Y0001_1',
  '8Y0001_2': '8Y0001_2',
  '8Y0001_3': '8Y0001_3',
}

function startEditCableType(project) {
  editingCableType.value = project.id
  cableTypeEdit.value = project.cable_type ?? '8Y0001_1'
}

async function saveCableType(project) {
  savingCableType.value = true
  try {
    const res = await fetch(`${BASE}/api/vodja/projects/${project.id}/cable-type`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
      },
      body: JSON.stringify({ cable_type: cableTypeEdit.value }),
    })
    if (res.ok) {
      project.cable_type = cableTypeEdit.value
      editingCableType.value = null
    }
  } finally {
    savingCableType.value = false
  }
}

const today = new Date().toISOString().slice(0, 10)
const filter = reactive({ project_id: '', date_from: today, date_to: '' })

// API returns dates as "dd.mm.YYYY." — convert to "YYYY-MM-DD" for comparison
function toISO(dateStr) {
  if (!dateStr) return ''
  const parts = dateStr.replace(/\.$/, '').split('.')
  if (parts.length < 3) return ''
  const [d, m, y] = parts
  return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`
}

const filteredProjects = computed(() => {
  return projects.value.filter(project => {
    if (filter.project_id && project.id != filter.project_id) return false
    const iso = toISO(project.date)
    if (filter.date_from && iso < filter.date_from) return false
    if (filter.date_to && iso > filter.date_to) return false
    return true
  })
})

const openFilteredProjects = computed(() => filteredProjects.value.filter(project => project.status !== 'zakljucen'))
const closedFilteredProjects = computed(() => filteredProjects.value.filter(project => project.status === 'zakljucen'))
const visibleProjects = computed(() => activeTab.value === 'closed' ? closedFilteredProjects.value : openFilteredProjects.value)

const hasFilter = computed(() => !!(filter.project_id || filter.date_from || filter.date_to))

function clearFilter() {
  filter.project_id = ''
  filter.date_from = ''
  filter.date_to = ''
}

function startDeleteProject(project) {
  deleteProjectId.value = project.id
  deleteProjectCode.value = ''
  deleteProjectError.value = ''
}

function cancelDeleteProject() {
  deleteProjectId.value = null
  deleteProjectCode.value = ''
  deleteProjectError.value = ''
}

async function confirmDeleteProject(project) {
  deletingProjectId.value = project.id
  deleteProjectError.value = ''

  try {
    const res = await fetch(`${BASE}/api/vodja/projects/${project.id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
      },
      body: JSON.stringify({ confirmation_code: deleteProjectCode.value }),
    })

    const json = await res.json().catch(() => ({}))

    if (!res.ok) {
      deleteProjectError.value = json.message ?? 'Brisanje projekta nije uspjelo.'
      return
    }

    projects.value = projects.value.filter(item => item.id !== project.id)
    cancelDeleteProject()
  } finally {
    deletingProjectId.value = null
  }
}

async function resubmit(project) {
  resubmitting.value = project.id
  try {
    const res = await fetch(`${BASE}/api/vodja/projects/${project.id}/resubmit`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
      },
    })
    if (res.ok) {
      project.status = 'na_cekanju'
      project.rejection_note = null
    }
  } finally {
    resubmitting.value = null
  }
}

async function toggleStatus(project) {
  togglingStatus.value = project.id
  try {
    const res = await fetch(`${BASE}/api/vodja/projects/${project.id}/toggle-status`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
      },
    })
    if (res.ok) {
      project.status = project.status === 'aktivan' ? 'zakljucen' : 'aktivan'
    }
  } finally {
    togglingStatus.value = null
  }
}

onMounted(async () => {
  try {
    const res = await fetch(BASE + '/api/vodja/projects', { headers: { 'Accept': 'application/json' } })
    projects.value = await res.json()
  } finally {
    loading.value = false
  }
})
</script>
