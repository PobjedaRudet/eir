<template>
  <div class="max-w-2xl">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="`${BASE}/vodja/projekti`"
        class="inline-flex items-center justify-center rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
      >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
        </svg>
      </a>
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Gradilište</h1>
        <p v-if="projectName" class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ projectName }}</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-12 text-zinc-500">Učitavanje...</div>
    <div
      v-else-if="serverError"
      class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm"
    >{{ serverError }}</div>

    <template v-else>

      <!-- ── OPREMA GRADILIŠTA ──────────────────────────────────────────── -->
      <section class="mb-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-1">
            Oprema gradilišta
          </h2>
          <button
            v-if="!editingGradiliste"
            type="button"
            @click="startEditGradiliste"
            class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline"
          >
            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
              <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
            </svg>
            Uredi
          </button>
        </div>

        <!-- View mode -->
        <div v-if="!editingGradiliste">
          <p
            v-if="gradilisteEquipment.length === 0"
            class="text-sm text-zinc-400 dark:text-zinc-500 italic px-1"
          >Nema dodijeljene opreme.</p>
          <div
            v-else
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 divide-y divide-neutral-100 dark:divide-neutral-800"
          >
            <div
              v-for="e in gradilisteEquipment"
              :key="e.id"
              class="flex items-center gap-3 px-4 py-2.5"
            >
              <span class="text-xs px-1.5 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-zinc-500 dark:text-zinc-400 shrink-0">
                {{ e.category_label }}
              </span>
              <span class="text-sm text-zinc-800 dark:text-zinc-200 flex-1">{{ e.name }}</span>
              <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">× {{ e.quantity }}</span>
            </div>
          </div>
        </div>

        <!-- Edit mode -->
        <div
          v-else
          class="rounded-xl border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-950/30 p-4"
        >
          <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Označite opremu i unesite količinu:</p>
          <div
            v-for="(group, cat) in equipmentCatalogByCategory"
            :key="cat"
            class="mb-4"
          >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">
              {{ group.label }}
            </p>
            <div class="space-y-1.5">
              <label
                v-for="item in group.items"
                :key="item.id"
                class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border text-sm cursor-pointer transition-colors"
                :class="isInForm(gradilisteForm, 'equipment_id', item.id)
                  ? 'border-blue-400 bg-blue-100 dark:bg-blue-900/30'
                  : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800'"
              >
                <input
                  type="checkbox"
                  :checked="isInForm(gradilisteForm, 'equipment_id', item.id)"
                  @change="toggleItem(gradilisteForm, 'equipment_id', item.id, $event)"
                  class="rounded border-neutral-300 text-teal-600"
                >
                <span class="flex-1 text-zinc-800 dark:text-zinc-200">{{ item.name }}</span>
                <input
                  v-if="isInForm(gradilisteForm, 'equipment_id', item.id)"
                  type="number"
                  min="1"
                  step="1"
                  :value="getQty(gradilisteForm, 'equipment_id', item.id)"
                  @input="setQty(gradilisteForm, 'equipment_id', item.id, $event, true)"
                  @click.stop
                  class="w-16 rounded-md border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 px-2 py-0.5 text-sm text-zinc-800 dark:text-zinc-200 text-center"
                >
              </label>
            </div>
          </div>

          <div v-if="gradilisteMsg" class="text-sm text-green-600 dark:text-green-400 mt-2 mb-2">{{ gradilisteMsg }}</div>
          <div v-if="gradilisteErr" class="text-sm text-red-600 dark:text-red-400 mt-2 mb-2">{{ gradilisteErr }}</div>

          <div class="flex items-center gap-2 mt-4 pt-3 border-t border-blue-200 dark:border-blue-800">
            <button
              type="button"
              @click="saveGradiliste"
              :disabled="savingGradiliste"
              class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50"
            >
              {{ savingGradiliste ? 'Čuvanje...' : 'Sačuvaj' }}
            </button>
            <button
              type="button"
              @click="editingGradiliste = false"
              class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors"
            >
              Odustani
            </button>
          </div>
        </div>
      </section>

      <!-- ── MATERIJAL GRADILIŠTA ───────────────────────────────────────── -->
      <section class="mb-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-1">
            Materijal gradilišta
          </h2>
          <button
            v-if="!editingMaterials"
            type="button"
            @click="startEditMaterials"
            class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline"
          >
            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
              <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
            </svg>
            Uredi
          </button>
        </div>

        <!-- View mode -->
        <div v-if="!editingMaterials">
          <p
            v-if="gradillisteMaterials.length === 0"
            class="text-sm text-zinc-400 dark:text-zinc-500 italic px-1"
          >Nema dodijeljenog materijala.</p>
          <div
            v-else
            class="rounded-xl border border-neutral-200 dark:border-neutral-700 divide-y divide-neutral-100 dark:divide-neutral-800"
          >
            <div
              v-for="m in gradillisteMaterials"
              :key="m.id"
              class="flex items-center gap-3 px-4 py-2.5"
            >
              <span class="text-xs px-1.5 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-zinc-500 dark:text-zinc-400 shrink-0">
                {{ m.category_label }}
              </span>
              <span class="text-sm text-zinc-800 dark:text-zinc-200 flex-1">{{ m.name }}</span>
              <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ m.quantity }} {{ m.unit }}</span>
            </div>
          </div>
        </div>

        <!-- Edit mode -->
        <div
          v-else
          class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/30 p-4"
        >
          <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Označite materijal i unesite količinu:</p>
          <div
            v-for="(group, cat) in materialCatalogByCategory"
            :key="cat"
            class="mb-4"
          >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">
              {{ group.label }}
            </p>
            <div class="space-y-1.5">
              <label
                v-for="item in group.items"
                :key="item.id"
                class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border text-sm cursor-pointer transition-colors"
                :class="isInForm(materialsForm, 'material_id', item.id)
                  ? 'border-emerald-400 bg-emerald-100 dark:bg-emerald-900/30'
                  : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800'"
              >
                <input
                  type="checkbox"
                  :checked="isInForm(materialsForm, 'material_id', item.id)"
                  @change="toggleItem(materialsForm, 'material_id', item.id, $event)"
                  class="rounded border-neutral-300 text-emerald-600"
                >
                <span class="flex-1 text-zinc-800 dark:text-zinc-200">{{ item.name }}</span>
                <span class="text-xs text-zinc-400 dark:text-zinc-500 shrink-0 mr-1">{{ item.unit }}</span>
                <input
                  v-if="isInForm(materialsForm, 'material_id', item.id)"
                  type="number"
                  min="0.01"
                  step="0.01"
                  :value="getQty(materialsForm, 'material_id', item.id)"
                  @input="setQty(materialsForm, 'material_id', item.id, $event, false)"
                  @click.stop
                  class="w-20 rounded-md border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 px-2 py-0.5 text-sm text-zinc-800 dark:text-zinc-200 text-center"
                >
              </label>
            </div>
          </div>

          <div v-if="materialsMsg" class="text-sm text-green-600 dark:text-green-400 mt-2 mb-2">{{ materialsMsg }}</div>
          <div v-if="materialsErr" class="text-sm text-red-600 dark:text-red-400 mt-2 mb-2">{{ materialsErr }}</div>

          <div class="flex items-center gap-2 mt-4 pt-3 border-t border-emerald-200 dark:border-emerald-800">
            <button
              type="button"
              @click="saveMaterials"
              :disabled="savingMaterials"
              class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50"
            >
              {{ savingMaterials ? 'Čuvanje...' : 'Sačuvaj' }}
            </button>
            <button
              type="button"
              @click="editingMaterials = false"
              class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors"
            >
              Odustani
            </button>
          </div>
        </div>
      </section>

      <!-- ── OPREMA PO TIMOVIMA ─────────────────────────────────────────── -->
      <section>
        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-1 mb-3">
          Oprema po timovima
          <span class="ml-1 px-1.5 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
            {{ activeTeams.length }}
          </span>
        </h2>

        <p
          v-if="activeTeams.length === 0"
          class="text-sm text-zinc-400 dark:text-zinc-500 italic px-1"
        >Nema aktivnih timova na projektu.</p>

        <div
          v-for="team in activeTeams"
          :key="team.id"
          class="mb-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden"
        >
          <!-- Team header -->
          <div class="flex items-center justify-between px-4 py-3 bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
            <span class="font-medium text-zinc-900 dark:text-white">{{ team.name }}</span>
            <button
              v-if="!team.editingEq"
              type="button"
              @click="startEditTeam(team)"
              class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline"
            >
              <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
              </svg>
              Uredi opremu
            </button>
          </div>

          <!-- Workers (read-only) -->
          <div class="px-4 py-2.5 border-b border-neutral-100 dark:border-neutral-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">Radnici:</p>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-if="team.workers.length === 0"
                class="text-xs italic text-zinc-400 dark:text-zinc-500"
              >–</span>
              <span
                v-for="w in team.workers"
                :key="w.id"
                class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 dark:bg-neutral-800 text-zinc-700 dark:text-zinc-300"
              >{{ w.name }}</span>
            </div>
          </div>

          <!-- Equipment view -->
          <div v-if="!team.editingEq" class="px-4 py-3">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1.5">Oprema:</p>
            <p
              v-if="team.equipment.length === 0"
              class="text-sm text-zinc-400 dark:text-zinc-500 italic"
            >Nema dodijeljene opreme.</p>
            <div v-else class="flex flex-wrap gap-2">
              <span
                v-for="e in team.equipment"
                :key="e.id"
                class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 font-medium"
              >
                {{ e.name }}
                <span class="text-indigo-500 dark:text-indigo-400">× {{ e.quantity }}</span>
              </span>
            </div>
          </div>

          <!-- Equipment edit -->
          <div v-else class="px-4 py-3 bg-indigo-50 dark:bg-indigo-950/20 border-t border-indigo-100 dark:border-indigo-900/40">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Označite opremu i unesite količinu:</p>
            <div
              v-for="(group, cat) in equipmentCatalogByCategory"
              :key="cat"
              class="mb-4"
            >
              <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">
                {{ group.label }}
              </p>
              <div class="space-y-1.5">
                <label
                  v-for="item in group.items"
                  :key="item.id"
                  class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border text-sm cursor-pointer transition-colors"
                  :class="isInForm(team.eqForm, 'equipment_id', item.id)
                    ? 'border-indigo-400 bg-indigo-100 dark:bg-indigo-900/30'
                    : 'border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800'"
                >
                  <input
                    type="checkbox"
                    :checked="isInForm(team.eqForm, 'equipment_id', item.id)"
                    @change="toggleItem(team.eqForm, 'equipment_id', item.id, $event)"
                    class="rounded border-neutral-300 text-indigo-600"
                  >
                  <span class="flex-1 text-zinc-800 dark:text-zinc-200">{{ item.name }}</span>
                  <input
                    v-if="isInForm(team.eqForm, 'equipment_id', item.id)"
                    type="number"
                    min="1"
                    step="1"
                    :value="getQty(team.eqForm, 'equipment_id', item.id)"
                    @input="setQty(team.eqForm, 'equipment_id', item.id, $event, true)"
                    @click.stop
                    class="w-16 rounded-md border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 px-2 py-0.5 text-sm text-zinc-800 dark:text-zinc-200 text-center"
                  >
                </label>
              </div>
            </div>

            <div v-if="team.msg" class="text-sm text-green-600 dark:text-green-400 mb-2">{{ team.msg }}</div>
            <div v-if="team.err" class="text-sm text-red-600 dark:text-red-400 mb-2">{{ team.err }}</div>

            <div class="flex items-center gap-2 pt-3 border-t border-indigo-200 dark:border-indigo-800/40">
              <button
                type="button"
                @click="saveTeamEquipment(team)"
                :disabled="team.saving"
                class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition-colors disabled:opacity-50"
              >
                {{ team.saving ? 'Čuvanje...' : 'Sačuvaj' }}
              </button>
              <button
                type="button"
                @click="team.editingEq = false"
                class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors"
              >
                Odustani
              </button>
            </div>
          </div>
        </div>
      </section>

    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const segments = window.location.pathname.split('/')
const projektiIdx = segments.indexOf('projekti')
const projectId = projektiIdx >= 0 ? segments[projektiIdx + 1] : null

function hdrs() {
  return {
    'Content-Type': 'application/json',
    Accept:         'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

const loading      = ref(true)
const serverError  = ref('')
const projectName  = ref('')

// ── Gradilište — oprema ───────────────────────────────────────────────────────
const gradilisteEquipment = ref([])
const editingGradiliste   = ref(false)
const gradilisteForm      = ref([])
const savingGradiliste    = ref(false)
const gradilisteMsg       = ref('')
const gradilisteErr       = ref('')

// ── Gradilište — materijal ────────────────────────────────────────────────────
const gradillisteMaterials = ref([])
const editingMaterials     = ref(false)
const materialsForm        = ref([])
const savingMaterials      = ref(false)
const materialsMsg         = ref('')
const materialsErr         = ref('')

// ── Teams + catalogs ─────────────────────────────────────────────────────────
const activeTeams      = ref([])
const equipmentCatalog = ref([])
const materialCatalog  = ref([])

const equipmentCatalogByCategory = computed(() => groupByCategory(equipmentCatalog.value))
const materialCatalogByCategory  = computed(() => groupByCategory(materialCatalog.value))

function groupByCategory(items) {
  const groups = {}
  for (const item of items) {
    if (!groups[item.category]) {
      groups[item.category] = { label: item.category_label, items: [] }
    }
    groups[item.category].items.push(item)
  }
  return groups
}

// ── Generic form helpers (idKey = 'equipment_id' | 'material_id') ────────────

const isInForm = (form, idKey, id) => form.some(f => f[idKey] === id)

const getQty = (form, idKey, id) => form.find(f => f[idKey] === id)?.quantity ?? 1

const toggleItem = (form, idKey, id, evt) => {
  if (evt.target.checked) {
    form.push({ [idKey]: id, quantity: 1 })
  } else {
    const idx = form.findIndex(f => f[idKey] === id)
    if (idx >= 0) form.splice(idx, 1)
  }
}

const setQty = (form, idKey, id, evt, isInt = true) => {
  const item = form.find(f => f[idKey] === id)
  if (!item) return
  const v = parseFloat(evt.target.value) || 0.01
  item.quantity = isInt ? Math.max(1, Math.round(v)) : Math.max(0.01, v)
}

// ── Load ──────────────────────────────────────────────────────────────────────

onMounted(async () => {
  try {
    const res = await fetch(`${BASE}/api/vodja/projects/${projectId}/gradiliste`)
    if (!res.ok) throw new Error('Greška pri učitavanju podataka.')
    const data = await res.json()

    projectName.value          = data.project_name
    gradilisteEquipment.value  = data.gradiliste_equipment
    gradillisteMaterials.value = data.gradiliste_materials
    equipmentCatalog.value     = data.equipment_catalog
    materialCatalog.value      = data.material_catalog

    activeTeams.value = data.active_teams.map(t => ({
      ...t,
      editingEq: false,
      eqForm:    [],
      saving:    false,
      msg:       '',
      err:       '',
    }))
  } catch (e) {
    serverError.value = e.message
  } finally {
    loading.value = false
  }
})

// ── Gradilište — save oprema ──────────────────────────────────────────────────

const startEditGradiliste = () => {
  gradilisteForm.value = gradilisteEquipment.value.map(e => ({
    equipment_id: e.id,
    quantity:     e.quantity,
  }))
  gradilisteMsg.value     = ''
  gradilisteErr.value     = ''
  editingGradiliste.value = true
}

const saveGradiliste = async () => {
  savingGradiliste.value = true
  gradilisteMsg.value    = ''
  gradilisteErr.value    = ''
  try {
    const res = await fetch(`${BASE}/api/vodja/projects/${projectId}/gradiliste/equipment`, {
      method:  'PUT',
      headers: hdrs(),
      body:    JSON.stringify({ equipment: gradilisteForm.value }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.message ?? 'Greška pri čuvanju.')

    gradilisteEquipment.value = gradilisteForm.value.map(f => {
      const cat = equipmentCatalog.value.find(e => e.id === f.equipment_id)
      return { ...cat, quantity: f.quantity }
    })
    gradilisteMsg.value     = data.message
    editingGradiliste.value = false
  } catch (e) {
    gradilisteErr.value = e.message
  } finally {
    savingGradiliste.value = false
  }
}

// ── Gradilište — save materijal ───────────────────────────────────────────────

const startEditMaterials = () => {
  materialsForm.value = gradillisteMaterials.value.map(m => ({
    material_id: m.id,
    quantity:    m.quantity,
  }))
  materialsMsg.value     = ''
  materialsErr.value     = ''
  editingMaterials.value = true
}

const saveMaterials = async () => {
  savingMaterials.value = true
  materialsMsg.value    = ''
  materialsErr.value    = ''
  try {
    const res = await fetch(`${BASE}/api/vodja/projects/${projectId}/gradiliste/materials`, {
      method:  'PUT',
      headers: hdrs(),
      body:    JSON.stringify({ materials: materialsForm.value }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.message ?? 'Greška pri čuvanju.')

    gradillisteMaterials.value = materialsForm.value.map(f => {
      const cat = materialCatalog.value.find(m => m.id === f.material_id)
      return { ...cat, quantity: f.quantity }
    })
    materialsMsg.value     = data.message
    editingMaterials.value = false
  } catch (e) {
    materialsErr.value = e.message
  } finally {
    savingMaterials.value = false
  }
}

// ── Team equipment save ───────────────────────────────────────────────────────

const startEditTeam = (team) => {
  team.eqForm = team.equipment.map(e => ({
    equipment_id: e.id,
    quantity:     e.quantity,
  }))
  team.msg       = ''
  team.err       = ''
  team.editingEq = true
}

const saveTeamEquipment = async (team) => {
  team.saving = true
  team.msg    = ''
  team.err    = ''
  try {
    const res = await fetch(`${BASE}/api/vodja/project-teams/${team.id}/equipment`, {
      method:  'PUT',
      headers: hdrs(),
      body:    JSON.stringify({ equipment: team.eqForm }),
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.message ?? 'Greška pri čuvanju.')

    team.equipment = team.eqForm.map(f => {
      const cat = equipmentCatalog.value.find(e => e.id === f.equipment_id)
      return { ...cat, quantity: f.quantity }
    })
    team.msg       = data.message
    team.editingEq = false
  } catch (e) {
    team.err = e.message
  } finally {
    team.saving = false
  }
}
</script>
