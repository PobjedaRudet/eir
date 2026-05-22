<template>
  <div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Katalog resursa</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Upravljajte opremom i materijalima.</p>
      </div>
    </div>

    <!-- Tab switcher -->
    <div class="flex gap-1 p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800 mb-6 w-fit">
      <button v-for="t in tabs" :key="t.key" @click="switchTab(t.key)"
              class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="tab === t.key ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'">
        {{ t.label }}
      </button>
    </div>

    <!-- Add / Edit form -->
    <div class="mb-6 p-5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900">
      <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-4">
        {{ editingId ? 'Uredi stavku' : 'Dodaj novu stavku' }}
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Naziv *</label>
          <input v-model="form.name" type="text" maxlength="100" placeholder="npr. Bager"
                 class="w-full px-3 py-2 rounded-lg border text-sm bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Kategorija *</label>
          <select v-model="form.category"
                  class="w-full px-3 py-2 rounded-lg border text-sm bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Odaberite kategoriju</option>
            <option v-for="cat in currentMeta.categories" :key="cat.key" :value="cat.key">{{ cat.label }}</option>
          </select>
        </div>
        <div v-if="tab !== 'equipment'">
          <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Jedinica mjere *</label>
          <select v-model="form.unit"
                  class="w-full px-3 py-2 rounded-lg border text-sm bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Odaberite</option>
            <option v-for="u in currentMeta.units" :key="u" :value="u">{{ u }}</option>
          </select>
        </div>
        <div :class="tab !== 'equipment' ? '' : 'sm:col-span-2'">
          <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Opis (opcionalno)</label>
          <input v-model="form.description" type="text" maxlength="255" placeholder="Kratki opis..."
                 class="w-full px-3 py-2 rounded-lg border text-sm bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <div v-if="formError" class="mt-3 text-sm text-red-600 dark:text-red-400">{{ formError }}</div>
      <div class="flex gap-2 mt-4">
        <button type="button" @click="saveItem" :disabled="saving"
                class="px-4 py-2 rounded-lg bg-zinc-800 dark:bg-zinc-100 text-white dark:text-zinc-900 text-sm font-medium hover:bg-zinc-700 dark:hover:bg-white transition-colors disabled:opacity-50">
          {{ saving ? 'Sprema se...' : (editingId ? 'Spremi izmjene' : 'Dodaj') }}
        </button>
        <button v-if="editingId" type="button" @click="cancelEdit"
                class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
          Otkazi
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="size-7 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- Items list (grouped by category) -->
    <template v-else>
      <div v-if="currentItems.length === 0"
           class="p-8 rounded-xl border-2 border-dashed border-neutral-300 dark:border-neutral-700 text-center text-sm text-zinc-400 dark:text-zinc-500">
        Nema stavki. Dodajte prvu stavku gore.
      </div>
      <div v-else class="space-y-5">
        <div v-for="(group, catLabel) in groupedItems" :key="catLabel">
          <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-2">{{ catLabel }}</h2>
          <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 divide-y divide-neutral-200 dark:divide-neutral-700 overflow-hidden">
            <div v-for="item in group" :key="item.id"
                 class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ item.name }}</p>
                <p v-if="item.description" class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ item.description }}</p>
                <p v-if="item.unit" class="text-xs text-zinc-400 dark:text-zinc-500">Jedinica: {{ item.unit }}</p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button @click="startEdit(item)"
                        class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z" />
                  </svg>
                </button>
                <button @click="confirmDelete(item)"
                        class="p-1.5 rounded-lg text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Delete confirm modal -->
    <div v-if="deleteTarget"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-xl">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-2">Potvrdite brisanje</h3>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-5">
          Sigurno zelite obrisati <strong>{{ deleteTarget.name }}</strong>? Ova akcija se ne moze ponistiti.
        </p>
        <div class="flex gap-3">
          <button @click="executeDelete" :disabled="deleting"
                  class="flex-1 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
            {{ deleting ? 'Brise se...' : 'Da, obrisi' }}
          </button>
          <button @click="deleteTarget = null"
                  class="flex-1 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors">
            Otkazi
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { BASE } from '../../utils/base'

// ¦¦ Tab config ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
const tabs = [
  { key: 'equipment', label: 'Oprema' },
  { key: 'material',  label: 'Materijali' },
]

const tabConfig = {
  equipment: { apiBase: `${BASE}/api/pm/equipment`,  hasUnit: false },
  material:  { apiBase: `${BASE}/api/pm/materials`,  hasUnit: true  },
}

// ¦¦ State ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
const tab         = ref('equipment')
const loading     = ref(true)
const saving      = ref(false)
const deleting    = ref(false)
const formError   = ref('')

const catalogData = reactive({ equipment: [], material: [] })
const metaData    = reactive({ equipment: { categories: [], units: [] }, material: { categories: [], units: [] } })

const editingId   = ref(null)
const deleteTarget = ref(null)

const form = reactive({ name: '', category: '', unit: '', description: '' })

// ¦¦ Computed ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
const currentItems = computed(() => catalogData[tab.value] ?? [])
const currentMeta  = computed(() => metaData[tab.value] ?? { categories: [], units: [] })

const groupedItems = computed(() => {
  return currentItems.value.reduce((acc, item) => {
    const label = item.category_label || item.category || 'Ostalo'
    if (!acc[label]) acc[label] = []
    acc[label].push(item)
    return acc
  }, {})
})

// ¦¦ Helpers ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
function hdrs() {
  return {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

function apiBase() { return tabConfig[tab.value].apiBase }

function resetForm() {
  form.name = ''; form.category = ''; form.unit = ''; form.description = ''
  editingId.value = null
  formError.value = ''
}

// ¦¦ Data loading ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
async function loadTab(key) {
  loading.value = true
  try {
    const res  = await fetch(tabConfig[key].apiBase, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    catalogData[key]       = data.items ?? []
    metaData[key].categories = data.categories ?? []
    metaData[key].units      = data.units ?? []
  } finally {
    loading.value = false
  }
}

async function switchTab(key) {
  resetForm()
  tab.value = key
  if (catalogData[key].length === 0) {
    await loadTab(key)
  } else {
    loading.value = false
  }
}

// ¦¦ CRUD ¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦
async function saveItem() {
  formError.value = ''
  if (!form.name.trim()) { formError.value = 'Naziv je obavezan.'; return }
  if (!form.category)    { formError.value = 'Kategorija je obavezna.'; return }
  if (tab.value !== 'equipment' && !form.unit) { formError.value = 'Jedinica mjere je obavezna.'; return }

  saving.value = true
  try {
    const method = editingId.value ? 'PUT' : 'POST'
    const url    = editingId.value ? `${apiBase()}/${editingId.value}` : apiBase()
    const res    = await fetch(url, { method, headers: hdrs(), body: JSON.stringify({ ...form }) })
    const data   = await res.json()
    if (!res.ok) { formError.value = data.message ?? 'Greska.'; return }
    resetForm()
    await loadTab(tab.value)
  } finally {
    saving.value = false
  }
}

function startEdit(item) {
  editingId.value  = item.id
  form.name        = item.name
  form.category    = item.category
  form.unit        = item.unit ?? ''
  form.description = item.description ?? ''
  formError.value  = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancelEdit() { resetForm() }

function confirmDelete(item) { deleteTarget.value = item }

async function executeDelete() {
  deleting.value = true
  try {
    await fetch(`${apiBase()}/${deleteTarget.value.id}`, { method: 'DELETE', headers: hdrs() })
    deleteTarget.value = null
    await loadTab(tab.value)
  } finally {
    deleting.value = false
  }
}

onMounted(() => loadTab('equipment'))
</script>
