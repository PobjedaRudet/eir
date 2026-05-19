<template>
  <div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="BASE + '/vodja/projekti'"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Novi projekat</h1>
    </div>

    <div v-if="configLoading" class="text-center py-12 text-zinc-500">Učitavanje...</div>

    <form v-else @submit.prevent="save" class="space-y-6">

      <!-- Naziv -->
      <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Naziv projekta</label>
        <input type="text" v-model="form.name" placeholder="Unesite naziv projekta" required class="input-field">
        <p v-if="errors['name']" class="mt-1 text-sm text-red-600">{{ errors['name'][0] }}</p>
      </div>

      <!-- Datum -->
      <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Datum</label>
        <input type="date" v-model="form.date" required class="input-field">
        <p v-if="errors['date']" class="mt-1 text-sm text-red-600">{{ errors['date'][0] }}</p>
      </div>

      <!-- Grad -->
      <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Grad</label>
        <select v-model="form.city_id" @change="onCityChange" class="select-field">
          <option value="">Izaberite grad</option>
          <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
        </select>
        <p v-if="errors['city_id']" class="mt-1 text-sm text-red-600">{{ errors['city_id'][0] }}</p>
      </div>

      <!-- Ulice -->
      <template v-if="form.city_id">
        <div v-if="streetsLoading" class="text-sm text-zinc-500">Učitavanje ulica...</div>
        <div v-else-if="streets.length === 0" class="p-4 rounded-lg border border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm flex items-center gap-2">
          ⚠ Nema ulica za izabrani grad.
        </div>
        <div v-else>
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
            Ulice projekta <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500">odaberite jednu ili više</span>
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <label
              v-for="street in streets"
              :key="street.id"
              class="flex items-center gap-3 cursor-pointer px-3 py-2 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
              :class="form.street_ids.includes(street.id) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
            >
              <input type="checkbox" :value="street.id" v-model="form.street_ids" class="rounded border-neutral-300 text-blue-600">
              <span class="text-sm">{{ street.name }}</span>
            </label>
          </div>
          <p v-if="errors['street_ids']" class="mt-1 text-sm text-red-600">{{ errors['street_ids'][0] }}</p>
        </div>
      </template>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Čuvanje...' : 'Sačuvaj projekat' }}
        </button>
        <a :href="BASE + '/vodja/projekti'" class="btn-secondary">Odustani</a>
      </div>

      <div v-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
        {{ serverError }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const cities = ref([])
const streets = ref([])
const configLoading = ref(true)
const streetsLoading = ref(false)
const saving = ref(false)
const errors = ref({})
const serverError = ref('')

const form = reactive({
  name: '',
  date: new Date().toISOString().slice(0, 10),
  city_id: '',
  street_ids: [],
})

async function onCityChange() {
  form.street_ids = []
  streets.value = []
  if (!form.city_id) return
  streetsLoading.value = true
  try {
    const res = await fetch(`${BASE}/api/vodja/cities/${form.city_id}/streets`, { headers: { 'Accept': 'application/json' } })
    streets.value = await res.json()
  } finally {
    streetsLoading.value = false
  }
}

async function save() {
  errors.value = {}
  serverError.value = ''
  saving.value = true

  try {
    const res = await fetch(BASE + '/api/vodja/projects', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': getCsrf(),
      },
      body: JSON.stringify({
        name: form.name,
        date: form.date,
        city_id: form.city_id,
        street_ids: form.street_ids,
      }),
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

    window.location.href = BASE + '/vodja/projekti'
  } finally {
    saving.value = false
  }
}

function getCsrf() {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : ''
}

onMounted(async () => {
  try {
    const res = await fetch(BASE + '/api/vodja/project-form-config', { headers: { 'Accept': 'application/json' } })
    const data = await res.json()
    cities.value = data.cities
  } finally {
    configLoading.value = false
  }
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
