<template>
  <div class="max-w-5xl">
    <div class="flex items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Gradovi i ulice</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Kreirajte gradove, dodajte ulice za odabrani grad i kasnije ih dopunjujte bez ulaska u formu projekta.</p>
      </div>
      <a :href="`${BASE}/vodja/novi-projekat`" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
        Novi projekat
      </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[320px,minmax(0,1fr)]">
      <section class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-5">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Novi grad</h2>
          <span class="text-xs text-zinc-400">{{ cities.length }} ukupno</span>
        </div>

        <form @submit.prevent="createCity" class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Naziv grada</label>
            <input v-model.trim="newCityName" type="text" placeholder="Npr. Sarajevo" class="input-field">
            <p v-if="cityError" class="mt-1 text-sm text-red-600">{{ cityError }}</p>
          </div>
          <button type="submit" :disabled="creatingCity || !newCityName" class="btn-primary w-full">
            {{ creatingCity ? 'Čuvanje...' : 'Sačuvaj grad' }}
          </button>
        </form>

        <div class="mt-5 border-t border-neutral-200 dark:border-neutral-700 pt-4">
          <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3">Gradovi</h3>
          <div v-if="loadingCities" class="text-sm text-zinc-500">Učitavanje...</div>
          <div v-else-if="cities.length === 0" class="text-sm text-zinc-400">Nema unesenih gradova.</div>
          <div v-else class="space-y-2 max-h-[28rem] overflow-y-auto pr-1">
            <button
              v-for="city in cities"
              :key="city.id"
              type="button"
              @click="selectCity(city)"
              class="w-full text-left rounded-lg border px-3 py-2 transition-colors"
              :class="selectedCityId === city.id
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200'
                : 'border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-zinc-700 dark:text-zinc-300'"
            >
              <div class="font-medium text-sm">{{ city.name }}</div>
            </button>
          </div>
        </div>
      </section>

      <section class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/40">
          <div v-if="selectedCity" class="flex items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ selectedCity.name }}</h2>
              <p class="text-sm text-zinc-500 dark:text-zinc-400">Dodajte nove ulice za ovaj grad ili pregledajte postojeće.</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-300">{{ streets.length }} ulica</span>
          </div>
          <div v-else>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Odaberite grad</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Sa lijeve strane izaberite grad za koji želite upravljati ulicama.</p>
          </div>
        </div>

        <div class="p-5">
          <template v-if="selectedCity">
            <form @submit.prevent="createStreet" class="rounded-xl border border-dashed border-neutral-300 dark:border-neutral-700 p-4 bg-neutral-50/60 dark:bg-neutral-900/40 mb-5">
              <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                  <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nova ulica za grad {{ selectedCity.name }}</label>
                  <input v-model.trim="newStreetName" type="text" placeholder="Npr. Zmaja od Bosne" class="input-field">
                  <p v-if="streetError" class="mt-1 text-sm text-red-600">{{ streetError }}</p>
                </div>
                <div class="sm:self-end">
                  <button type="submit" :disabled="creatingStreet || !newStreetName" class="btn-primary min-w-36">
                    {{ creatingStreet ? 'Čuvanje...' : 'Dodaj ulicu' }}
                  </button>
                </div>
              </div>
            </form>

            <div v-if="loadingStreets" class="text-sm text-zinc-500">Učitavanje ulica...</div>
            <div v-else-if="streets.length === 0" class="rounded-lg border border-yellow-200 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300 text-sm px-4 py-3">
              Ovaj grad još nema unesenih ulica.
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
              <div
                v-for="street in streets"
                :key="street.id"
                class="rounded-lg border border-neutral-200 dark:border-neutral-700 px-3 py-2 bg-white dark:bg-neutral-900"
              >
                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ street.name }}</p>
              </div>
            </div>
          </template>

          <div v-else class="rounded-lg border border-dashed border-neutral-300 dark:border-neutral-700 px-4 py-10 text-center text-zinc-400">
            Prvo odaberite grad iz kataloga.
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { BASE } from '../../utils/base'

const cities = ref([])
const streets = ref([])
const selectedCityId = ref(null)
const loadingCities = ref(true)
const loadingStreets = ref(false)
const creatingCity = ref(false)
const creatingStreet = ref(false)
const cityError = ref('')
const streetError = ref('')
const newCityName = ref('')
const newStreetName = ref('')

const selectedCity = computed(() => cities.value.find(city => city.id === selectedCityId.value) ?? null)

function hdrs() {
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

async function getJsonOrThrow(response, fallbackMessage) {
  const contentType = response.headers.get('content-type') ?? ''
  const payload = contentType.includes('application/json') ? await response.json() : null

  if (!response.ok) {
    throw new Error(payload?.message ?? fallbackMessage)
  }

  return payload
}

async function loadCities() {
  loadingCities.value = true
  try {
    const res = await fetch(`${BASE}/api/vodja/project-form-config`, { headers: { Accept: 'application/json' } })
    const data = await getJsonOrThrow(res, 'Ne mogu učitati gradove.')
    cities.value = [...(data.cities ?? [])].sort((a, b) => a.name.localeCompare(b.name, 'bs'))

    if (!selectedCityId.value && cities.value.length) {
      selectedCityId.value = cities.value[0].id
    }
  } catch (error) {
    cityError.value = error.message
  } finally {
    loadingCities.value = false
  }
}

async function loadStreets(cityId) {
  streets.value = []
  if (!cityId) return

  loadingStreets.value = true
  streetError.value = ''
  try {
    const res = await fetch(`${BASE}/api/vodja/cities/${cityId}/streets`, { headers: { Accept: 'application/json' } })
    const data = await getJsonOrThrow(res, 'Ne mogu učitati ulice.')
    streets.value = [...data].sort((a, b) => a.name.localeCompare(b.name, 'bs'))
  } catch (error) {
    streetError.value = error.message
  } finally {
    loadingStreets.value = false
  }
}

async function selectCity(city) {
  selectedCityId.value = city.id
  newStreetName.value = ''
  await loadStreets(city.id)
}

async function createCity() {
  cityError.value = ''
  creatingCity.value = true
  try {
    const res = await fetch(`${BASE}/api/vodja/cities`, {
      method: 'POST',
      headers: hdrs(),
      body: JSON.stringify({ name: newCityName.value }),
    })
    const json = await res.json()

    if (!res.ok) {
      cityError.value = json.message ?? json.errors?.name?.[0] ?? 'Greška pri čuvanju grada.'
      return
    }

    const city = json.city
    if (!cities.value.some(item => item.id === city.id)) {
      cities.value.push(city)
      cities.value.sort((a, b) => a.name.localeCompare(b.name, 'bs'))
    }

    newCityName.value = ''
    await selectCity(city)
  } finally {
    creatingCity.value = false
  }
}

async function createStreet() {
  streetError.value = ''
  if (!selectedCityId.value) {
    streetError.value = 'Prvo odaberite grad.'
    return
  }

  creatingStreet.value = true
  try {
    const res = await fetch(`${BASE}/api/vodja/streets`, {
      method: 'POST',
      headers: hdrs(),
      body: JSON.stringify({ city_id: selectedCityId.value, name: newStreetName.value }),
    })
    const json = await res.json()

    if (!res.ok) {
      streetError.value = json.message ?? json.errors?.name?.[0] ?? 'Greška pri čuvanju ulice.'
      return
    }

    const street = json.street
    if (!streets.value.some(item => item.id === street.id)) {
      streets.value.push(street)
      streets.value.sort((a, b) => a.name.localeCompare(b.name, 'bs'))
    }
    newStreetName.value = ''
  } finally {
    creatingStreet.value = false
  }
}

onMounted(async () => {
  await loadCities()
  if (selectedCityId.value) {
    await loadStreets(selectedCityId.value)
  }
})
</script>

<style scoped>
@reference "../../../css/app.css";

.input-field {
  @apply w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.btn-primary {
  @apply px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50;
}
</style>
