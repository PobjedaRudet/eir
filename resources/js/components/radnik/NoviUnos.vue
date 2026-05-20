<template>
  <div class="max-w-3xl">
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="BASE + '/radnik/unosi'"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Novi unos radova</h1>
    </div>

    <div v-if="configLoading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <form v-else @submit.prevent="save" class="space-y-6">

      <!-- Osnovni podaci -->
      <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5 space-y-5">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Osnovni podaci</h2>

        <!-- Projekat -->
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Projekat</label>
          <select v-model="form.project_id" @change="onProjectChange" class="select-field">
            <option value="">Izaberite projekat</option>
            <option v-for="p in config.projects" :key="p.id" :value="p.id">
              {{ p.name }} — {{ p.city }}
            </option>
          </select>
          <p v-if="!config.projects.length && !serverError" class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Nema projekata za odabir. Na ovoj formi se gradovi i ulice ne učitavaju direktno iz tabela, nego kroz već kreirane projekte.
          </p>
          <p v-if="errors['project_id']" class="mt-1 text-sm text-red-600">{{ errors['project_id'][0] }}</p>
        </div>

        <!-- Vrsta kabla -->
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Vrsta kabla</label>
          <select v-model="form.cable_type" class="select-field">
            <option value="">Izaberite vrstu kabla</option>
            <option v-for="ct in config.cable_types" :key="ct" :value="ct">{{ ct }}</option>
          </select>
          <p v-if="errors['cable_type']" class="mt-1 text-sm text-red-600">{{ errors['cable_type'][0] }}</p>
        </div>

        <!-- Vrste radova -->
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
            Vrste radova <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500">odaberite jednu ili više</span>
          </label>
          <div class="flex flex-col sm:flex-row gap-2">
            <label
              v-for="(label, value) in config.work_types"
              :key="value"
              class="flex-1 flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
              :class="form.work_types.includes(value) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
            >
              <input type="checkbox" :value="value" v-model="form.work_types" class="rounded text-blue-600">
              <span class="text-sm font-medium">{{ label }}</span>
            </label>
          </div>
          <p v-if="errors['work_types']" class="mt-1 text-sm text-red-600">{{ errors['work_types'][0] }}</p>
        </div>

        <!-- Kućište -->
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Kućište</label>
          <select v-model="form.enclosure_id" class="select-field">
            <option value="">Izaberite kućište</option>
            <option v-for="e in config.enclosures" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
          <p v-if="errors['enclosure_id']" class="mt-1 text-sm text-red-600">{{ errors['enclosure_id'][0] }}</p>
        </div>

        <!-- Datum -->
        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Datum</label>
          <input type="date" v-model="form.date" required class="input-field">
          <p v-if="errors['date']" class="mt-1 text-sm text-red-600">{{ errors['date'][0] }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Ulice</label>
          <div v-if="!form.project_id" class="text-sm text-neutral-500 dark:text-neutral-400">
            Prvo izaberite projekat.
          </div>
          <div v-else-if="availableStreets.length" ref="streetsDropdown" class="relative">
            <button
              type="button"
              @click="streetsOpen = !streetsOpen"
              class="flex w-full items-center justify-between rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white"
            >
              <span class="truncate text-left">
                {{ selectedStreetSummary }}
              </span>
              <svg class="size-4 shrink-0 text-zinc-400 transition-transform" :class="streetsOpen ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
              </svg>
            </button>

            <div v-if="streetsOpen" class="absolute z-20 mt-2 w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 shadow-lg">
              <div class="max-h-64 overflow-y-auto p-2 space-y-1">
                <label
                  v-for="street in availableStreets"
                  :key="street.id"
                  class="flex items-center gap-3 cursor-pointer px-3 py-2 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                >
                  <input type="checkbox" :value="street.id" v-model="form.street_ids" class="rounded border-neutral-300 text-blue-600">
                  <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ street.name }}</span>
                </label>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-neutral-500 dark:text-neutral-400">
            Nema dostupnih ulica za izabrani projekat.
          </div>
          <p v-if="errors['street_ids']" class="mt-1 text-sm text-red-600">{{ errors['street_ids'][0] }}</p>

          <div v-if="selectedStreetNames.length" class="mt-3 flex flex-wrap gap-2">
            <span
              v-for="streetName in selectedStreetNames"
              :key="streetName"
              class="px-2.5 py-1 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700"
            >
              {{ streetName }}
            </span>
          </div>
        </div>
      </div>

      <!-- Operacije -->
      <div class="border border-neutral-200 dark:border-neutral-700 rounded-xl p-5">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Operacije</h2>
        </div>

        <div class="space-y-6">
          <div
            v-for="(op, i) in form.operations"
            :key="i"
            class="rounded-xl border p-4"
            :class="i % 2 === 0
              ? 'border-neutral-100 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-800/50'
              : 'border-sky-100 bg-sky-50/50 dark:border-sky-900/40 dark:bg-sky-950/20'"
          >
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-medium text-zinc-900 dark:text-white">Operacija {{ i + 1 }}</h3>
              <button
                v-if="form.operations.length > 1"
                type="button"
                @click="removeOperation(i)"
                class="text-red-500 hover:text-red-700 p-1 rounded"
              >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>

            <!-- Vrsta operacije -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Vrsta operacije</label>
              <div class="grid grid-cols-2 gap-2">
                <label
                  v-for="(label, kind) in { iskop: 'Iskop', upuhivanje: 'Upuhivanje kabla' }"
                  :key="kind"
                  class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-white dark:hover:bg-neutral-700 transition-colors"
                  :class="op.kind === kind ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
                >
                  <input type="radio" v-model="op.kind" :value="kind" class="text-blue-600">
                  <span class="text-sm font-medium">{{ label }}</span>
                </label>
              </div>
              <p v-if="errors[`operations.${i}.kind`]" class="mt-1 text-sm text-red-600">{{ errors[`operations.${i}.kind`][0] }}</p>
            </div>

            <div class="space-y-4">

              <!-- ISKOP -->
              <template v-if="op.kind === 'iskop'">
                <!-- Vrsta iskopa -->
                <div>
                  <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Vrsta iskopa</label>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <label
                      v-for="(label, value) in { iskop: 'Iskop', iskop_flaster: 'Iskop flaster', iskop_asfalt: 'Iskop asfalt', raketa: 'Raketa' }"
                      :key="value"
                      class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-neutral-200 dark:border-neutral-600 hover:bg-white dark:hover:bg-neutral-700 transition-colors text-sm"
                      :class="op.excavation_type === value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : ''"
                    >
                      <input type="radio" v-model="op.excavation_type" :value="value" class="text-blue-600">
                      {{ label }}
                    </label>
                  </div>
                  <p v-if="errors[`operations.${i}.excavation_type`]" class="mt-1 text-sm text-red-600">{{ errors[`operations.${i}.excavation_type`][0] }}</p>
                </div>

                <!-- Dimenzije -->
                <div>
                  <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Dimenzije</label>
                  <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <label
                      v-for="dim in ['15x45', '15x60', '30x45', '30x60']"
                      :key="dim"
                      class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-neutral-200 dark:border-neutral-600 hover:bg-white dark:hover:bg-neutral-700 transition-colors text-sm font-mono"
                      :class="op.dimensions === dim ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : ''"
                    >
                      <input type="radio" v-model="op.dimensions" :value="dim" class="text-blue-600">
                      {{ dim }}
                    </label>
                  </div>
                  <p v-if="errors[`operations.${i}.dimensions`]" class="mt-1 text-sm text-red-600">{{ errors[`operations.${i}.dimensions`][0] }}</p>
                </div>

                <!-- Metraža -->
                <div>
                  <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Metraža (m)</label>
                  <input type="number" v-model="op.meterage" step="0.01" min="0.01" placeholder="npr. 12.50" class="input-field">
                  <p v-if="errors[`operations.${i}.meterage`]" class="mt-1 text-sm text-red-600">{{ errors[`operations.${i}.meterage`][0] }}</p>
                </div>

                <!-- Podoperacije HP+ -->
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Podoperacije</label>
                    <button type="button" @click="addSubOperation(i)" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">+ Dodaj HP+</button>
                  </div>

                  <div v-if="op.sub_operations.length" class="space-y-3">
                    <div
                      v-for="(sub, j) in op.sub_operations"
                      :key="j"
                      class="rounded-lg bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 p-3"
                    >
                      <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                          <svg class="size-4 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 1ZM5.05 3.05a.75.75 0 0 1 1.06 0l1.062 1.06A.75.75 0 1 1 6.11 5.173L5.05 4.11a.75.75 0 0 1 0-1.06ZM14.95 3.05a.75.75 0 0 1 0 1.06l-1.06 1.062a.75.75 0 0 1-1.062-1.061l1.061-1.06a.75.75 0 0 1 1.06 0ZM3 8a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5A.75.75 0 0 1 3 8ZM14 8a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5h-1.5A.75.75 0 0 1 14 8Z" /></svg>
                          <span class="text-sm font-semibold">HP+</span>
                        </div>
                        <button type="button" @click="removeSubOperation(i, j)" class="text-red-500 hover:text-red-700">
                          <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                        </button>
                      </div>
                      <div class="grid grid-cols-2 gap-3">
                        <div>
                          <label class="block text-xs text-zinc-500 mb-1">Metraža (m)</label>
                          <input type="number" v-model="sub.meterage" step="0.01" min="0.01" placeholder="npr. 5.50" class="input-field text-sm">
                        </div>
                        <div>
                          <label class="block text-xs text-zinc-500 mb-1">Broj kućice</label>
                          <input type="text" v-model="sub.broj_kucice" placeholder="npr. 12A" class="input-field text-sm">
                        </div>
                      </div>
                      <!-- Sub-op photos -->
                      <div class="mt-3 pt-3 border-t border-neutral-100 dark:border-neutral-800">
                        <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-2">Fotografije HP+</p>
                        <label class="flex items-center justify-center gap-2 w-full h-16 border-2 border-dashed border-neutral-200 dark:border-neutral-700 rounded-lg cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors text-sm text-neutral-400">
                          📷 Dodaj fotografije
                          <input type="file" :name="`sub_photos_${i}_${j}`" @change="e => onSubPhotoChange(e, i, j)" accept="image/*" multiple class="hidden">
                        </label>
                        <div v-if="subPhotoPreviews[i]?.[j]?.length" class="mt-1 flex flex-wrap gap-1">
                          <div
                            v-for="(src, k) in subPhotoPreviews[i][j]"
                            :key="k"
                            class="relative"
                          >
                            <img :src="src" class="h-14 w-14 object-cover rounded border border-neutral-200 dark:border-neutral-600">
                            <button
                              type="button"
                              @click="removeSubPhoto(i, j, k)"
                              class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-white text-xs shadow hover:bg-red-700"
                              aria-label="Ukloni fotografiju"
                            >
                              ×
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p v-else class="text-sm text-neutral-400 dark:text-neutral-500">Nema podoperacija.</p>
                </div>
              </template>

              <!-- UPUHIVANJE -->
              <template v-else-if="op.kind === 'upuhivanje'">
                <div>
                  <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Adresa kuće</label>
                  <input type="text" v-model="op.address" placeholder="npr. Titova ulica 12" class="input-field">
                  <p v-if="errors[`operations.${i}.address`]" class="mt-1 text-sm text-red-600">{{ errors[`operations.${i}.address`][0] }}</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                  <label
                    class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors flex-1"
                    :class="op.splajsovano ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
                  >
                    <input type="checkbox" v-model="op.splajsovano" class="rounded text-blue-600">
                    <span class="text-sm font-medium">Splajsovano</span>
                  </label>
                  <label
                    class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors flex-1"
                    :class="op.aktivirano ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ''"
                  >
                    <input type="checkbox" v-model="op.aktivirano" class="rounded text-green-600">
                    <span class="text-sm font-medium">Aktivirano</span>
                  </label>
                </div>
              </template>

              <!-- Photos per operation -->
              <div v-if="op.kind">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Fotografije operacije</label>
                <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-neutral-300 dark:border-neutral-600 rounded-lg cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
                  <div class="flex flex-col items-center gap-1 text-sm text-neutral-500">
                    📷
                    <span>Kliknite za dodavanje fotografija</span>
                    <span class="text-xs">PNG, JPG, JPEG, WEBP do 10MB</span>
                  </div>
                  <input type="file" :name="`photos_${i}`" @change="e => onPhotoChange(e, i)" accept="image/*" multiple class="hidden">
                </label>
                <div v-if="photoPreviews[i]?.length" class="mt-2 flex flex-wrap gap-2">
                  <div
                    v-for="(src, k) in photoPreviews[i]"
                    :key="k"
                    class="relative"
                  >
                    <img :src="src" class="h-20 w-20 object-cover rounded-lg border border-neutral-200 dark:border-neutral-600">
                    <button
                      type="button"
                      @click="removePhoto(i, k)"
                      class="absolute -top-1.5 -right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-white text-sm shadow hover:bg-red-700"
                      aria-label="Ukloni fotografiju"
                    >
                      ×
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-center">
          <button type="button" @click="addOperation" class="btn-secondary text-sm">
            + Dodaj operaciju
          </button>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex items-center gap-3">
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Čuvanje...' : 'Sačuvaj unos' }}
        </button>
        <a :href="BASE + '/radnik/unosi'" class="btn-secondary">Odustani</a>
      </div>

      <div v-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
        {{ serverError }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, reactive } from 'vue'
import { BASE } from '../../utils/base'

const config = ref({ projects: [], enclosures: [], cable_types: [], work_types: {} })
const configLoading = ref(true)
const saving = ref(false)
const errors = ref({})
const serverError = ref('')
const streetsOpen = ref(false)
const streetsDropdown = ref(null)

const form = reactive({
  project_id: '',
  cable_type: '',
  work_types: [],
  enclosure_id: '',
  street_ids: [],
  date: new Date().toISOString().slice(0, 10),
  operations: [],
})

// File refs — stored as File[] per operation index
const photoFiles = ref([])
const subPhotoFiles = ref([]) // [opIndex][subIndex] = File[]
const photoPreviews = ref([])
const subPhotoPreviews = ref([])

const availableStreets = computed(() => {
  const project = config.value.projects.find(p => p.id == form.project_id)
  return project?.streets ?? []
})

const selectedStreetNames = computed(() => {
  const selectedIds = new Set(form.street_ids.map(id => String(id)))
  return availableStreets.value
    .filter(street => selectedIds.has(String(street.id)))
    .map(street => street.name)
})

const selectedStreetSummary = computed(() => {
  if (!selectedStreetNames.value.length) {
    return 'Izaberite ulice'
  }

  if (selectedStreetNames.value.length === 1) {
    return selectedStreetNames.value[0]
  }

  return `${selectedStreetNames.value.length} odabrane ulice`
})

function addOperation() {
  form.operations.push({ kind: '', excavation_type: '', dimensions: '', meterage: '', sub_operations: [], address: '', splajsovano: false, aktivirano: false })
  photoFiles.value.push([])
  photoPreviews.value.push([])
  subPhotoFiles.value.push([])
  subPhotoPreviews.value.push([])
}

function removeOperation(i) {
  if (form.operations.length <= 1) return
  form.operations.splice(i, 1)
  photoFiles.value.splice(i, 1)
  photoPreviews.value.splice(i, 1)
  subPhotoFiles.value.splice(i, 1)
  subPhotoPreviews.value.splice(i, 1)
}

function addSubOperation(opIndex) {
  form.operations[opIndex].sub_operations.push({ type: 'HP+', meterage: '', broj_kucice: '' })
  if (!subPhotoFiles.value[opIndex]) subPhotoFiles.value[opIndex] = []
  subPhotoFiles.value[opIndex].push([])
  if (!subPhotoPreviews.value[opIndex]) subPhotoPreviews.value[opIndex] = []
  subPhotoPreviews.value[opIndex].push([])
}

function removeSubOperation(opIndex, subIndex) {
  form.operations[opIndex].sub_operations.splice(subIndex, 1)
  subPhotoFiles.value[opIndex]?.splice(subIndex, 1)
  subPhotoPreviews.value[opIndex]?.splice(subIndex, 1)
}

function onProjectChange() {
  form.street_ids = []
  streetsOpen.value = false
}

function onDocumentClick(event) {
  if (!streetsOpen.value) return
  if (streetsDropdown.value?.contains(event.target)) return

  streetsOpen.value = false
}

function appendFiles(existingFiles, existingPreviews, newFiles) {
  return {
    files: [...existingFiles, ...newFiles],
    previews: [...existingPreviews, ...newFiles.map(file => URL.createObjectURL(file))],
  }
}

function onPhotoChange(e, i) {
  const files = Array.from(e.target.files ?? [])
  const next = appendFiles(photoFiles.value[i] ?? [], photoPreviews.value[i] ?? [], files)
  photoFiles.value[i] = next.files
  photoPreviews.value[i] = next.previews
  e.target.value = ''
}

function onSubPhotoChange(e, i, j) {
  const files = Array.from(e.target.files ?? [])
  if (!subPhotoFiles.value[i]) subPhotoFiles.value[i] = []
  if (!subPhotoPreviews.value[i]) subPhotoPreviews.value[i] = []
  const next = appendFiles(subPhotoFiles.value[i][j] ?? [], subPhotoPreviews.value[i][j] ?? [], files)
  subPhotoFiles.value[i][j] = next.files
  subPhotoPreviews.value[i][j] = next.previews
  e.target.value = ''
}

function removePhoto(opIndex, photoIndex) {
  const preview = photoPreviews.value[opIndex]?.[photoIndex]
  if (preview) URL.revokeObjectURL(preview)
  photoFiles.value[opIndex]?.splice(photoIndex, 1)
  photoPreviews.value[opIndex]?.splice(photoIndex, 1)
}

function removeSubPhoto(opIndex, subIndex, photoIndex) {
  const preview = subPhotoPreviews.value[opIndex]?.[subIndex]?.[photoIndex]
  if (preview) URL.revokeObjectURL(preview)
  subPhotoFiles.value[opIndex]?.[subIndex]?.splice(photoIndex, 1)
  subPhotoPreviews.value[opIndex]?.[subIndex]?.splice(photoIndex, 1)
}

async function save() {
  errors.value = {}
  serverError.value = ''
  saving.value = true

  try {
    const fd = new FormData()
    fd.append('project_id', form.project_id)
    fd.append('cable_type', form.cable_type)
    fd.append('enclosure_id', form.enclosure_id)
    fd.append('date', form.date)
    form.work_types.forEach(wt => fd.append('work_types[]', wt))
    form.street_ids.forEach(streetId => fd.append('street_ids[]', streetId))

    form.operations.forEach((op, i) => {
      fd.append(`operations[${i}][kind]`, op.kind)
      if (op.kind === 'iskop') {
        fd.append(`operations[${i}][excavation_type]`, op.excavation_type)
        fd.append(`operations[${i}][dimensions]`, op.dimensions)
        fd.append(`operations[${i}][meterage]`, op.meterage)
        op.sub_operations.forEach((sub, j) => {
          fd.append(`operations[${i}][sub_operations][${j}][type]`, sub.type)
          if (sub.meterage) fd.append(`operations[${i}][sub_operations][${j}][meterage]`, sub.meterage)
          if (sub.broj_kucice) fd.append(`operations[${i}][sub_operations][${j}][broj_kucice]`, sub.broj_kucice)
          ;(subPhotoFiles.value[i]?.[j] ?? []).forEach(file => fd.append(`sub_photos_${i}_${j}[]`, file))
        })
      } else if (op.kind === 'upuhivanje') {
        fd.append(`operations[${i}][address]`, op.address)
        fd.append(`operations[${i}][splajsovano]`, op.splajsovano ? '1' : '0')
        fd.append(`operations[${i}][aktivirano]`, op.aktivirano ? '1' : '0')
      }
      ;(photoFiles.value[i] ?? []).forEach(file => fd.append(`photos_${i}[]`, file))
    })

    const res = await fetch(BASE + '/api/radnik/entries', {
      method: 'POST',
      headers: {
        'X-XSRF-TOKEN': getCsrf(),
        'Accept': 'application/json',
      },
      body: fd,
    })

    const json = await res.json()

    if (!res.ok) {
      if (res.status === 422 && json.errors) {
        errors.value = json.errors
      } else {
        serverError.value = json.message ?? 'Greška pri čuvanju.'
      }
      return
    }

    window.location.href = BASE + '/radnik/unosi'
  } finally {
    saving.value = false
  }
}

function getCsrf() {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

async function getJsonOrThrow(response, fallbackMessage) {
  const contentType = response.headers.get('content-type') ?? ''
  const payload = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    throw new Error(payload?.message ?? fallbackMessage)
  }

  return payload
}

onMounted(async () => {
  document.addEventListener('click', onDocumentClick)
  serverError.value = ''

  try {
    const res = await fetch(BASE + '/api/radnik/form-config', { headers: { 'Accept': 'application/json' } })
    const data = await getJsonOrThrow(res, 'Ne mogu učitati projekte i pomoćne podatke iz baze.')
    config.value = {
      projects: data.projects ?? [],
      enclosures: data.enclosures ?? [],
      cable_types: data.cable_types ?? [],
      work_types: data.work_types ?? {},
    }
  } catch (error) {
    serverError.value = error instanceof Error ? error.message : 'Ne mogu učitati projekte i pomoćne podatke iz baze.'
  } finally {
    configLoading.value = false
  }
  addOperation()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
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
  @apply px-4 py-2 rounded-lg border border-neutral-200 dark:border-neutral-700 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors;
}
</style>
