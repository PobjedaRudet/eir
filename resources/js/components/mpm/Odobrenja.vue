<template>
  <div class="max-w-3xl">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Odobrenja</h1>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Pregledajte i odobrite projekte i naloge resursa.</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 p-1 bg-neutral-100 dark:bg-neutral-800 rounded-lg w-fit">
      <button
        @click="tab = 'projekti'"
        :class="tab === 'projekti'
          ? 'bg-white dark:bg-neutral-700 text-zinc-900 dark:text-white shadow-sm'
          : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all flex items-center gap-1.5"
      >
        Projekti
        <span v-if="pendingProjects.length" class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-amber-500 text-white">
          {{ pendingProjects.length }}
        </span>
      </button>
      <button
        @click="tab = 'nalozi'"
        :class="tab === 'nalozi'
          ? 'bg-white dark:bg-neutral-700 text-zinc-900 dark:text-white shadow-sm'
          : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300'"
        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all flex items-center gap-1.5"
      >
        Radni nalozi
        <span v-if="orders.length" class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-blue-500 text-white">
          {{ orders.length }}
        </span>
      </button>
    </div>

    <!-- ── PROJECT APPROVALS ──────────────────────────────────────────────── -->
    <template v-if="tab === 'projekti'">
      <div v-if="projectsLoading" class="flex justify-center py-16">
        <div class="size-8 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
      </div>

      <div v-else-if="pendingProjects.length === 0"
           class="p-12 rounded-xl border-2 border-dashed border-neutral-300 dark:border-neutral-700 text-center">
        <svg class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
        </svg>
        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Nema projekata na čekanju</p>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Svi projekti su pregledani.</p>
      </div>

      <div v-else class="space-y-4">
        <div v-for="project in pendingProjects" :key="project.id"
             class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
          <div class="p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ project.name }}</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                  Datum: <strong class="text-zinc-700 dark:text-zinc-300">{{ project.date }}</strong>
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                  Grad: <strong class="text-zinc-700 dark:text-zinc-300">{{ project.city ?? '—' }}</strong>
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                  Vođa: <strong class="text-zinc-700 dark:text-zinc-300">{{ project.leader ?? '—' }}</strong>
                </p>
                <p v-if="project.cable_type" class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                  Kablo: <strong class="text-zinc-700 dark:text-zinc-300">{{ CABLE_LABELS[project.cable_type] ?? project.cable_type }}</strong>
                </p>
              </div>
              <span class="shrink-0 px-2.5 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                Na čekanju
              </span>
            </div>
            <div v-if="project.streets?.length" class="mt-3 flex flex-wrap gap-1">
              <span
                v-for="street in project.streets" :key="street"
                class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300"
              >{{ street }}</span>
            </div>

            <!-- Teams -->
            <div v-if="project.teams?.length" class="mt-3">
              <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1.5">Timovi:</p>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="team in project.teams" :key="team.id"
                      class="px-2.5 py-0.5 text-xs rounded-full bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 text-teal-700 dark:text-teal-300">
                  {{ team.name }}
                </span>
              </div>
            </div>

            <!-- NTV Assignments -->
            <div v-if="project.ntvs?.length" class="mt-3 space-y-2">
              <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1.5">NTV — Razvodne kutije:</p>
              <div v-for="(ntv, i) in project.ntvs" :key="i"
                   class="rounded-lg border border-neutral-200 dark:border-neutral-700 px-3 py-2 bg-neutral-50 dark:bg-neutral-800/40 text-xs space-y-1">
                <div class="flex items-center justify-between gap-2">
                  <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ ntv.ntv_name }}</span>
                  <span v-if="ntv.team_name" class="px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 text-teal-700 dark:text-teal-300 shrink-0">{{ ntv.team_name }}</span>
                  <span v-else class="text-zinc-400">bez tima</span>
                </div>
                <div v-if="ntv.streets?.length" class="flex flex-wrap gap-1 mt-1">
                  <span v-for="s in ntv.streets" :key="s"
                        class="px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">{{ s }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Reject form -->
          <div v-if="rejectingProjectId === project.id" class="px-5 pb-3">
            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Razlog odbijanja *</label>
            <textarea v-model="projectRejectNote" rows="2" placeholder="Opišite razlog odbijanja..."
                      class="w-full px-3 py-2 rounded-lg border text-sm bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
            ></textarea>
            <p v-if="projectActionError" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ projectActionError }}</p>
          </div>

          <div class="px-5 py-4 bg-neutral-50 dark:bg-neutral-800/50 border-t border-neutral-200 dark:border-neutral-700">
            <div class="flex gap-2 flex-wrap">
              <template v-if="rejectingProjectId === project.id">
                <button @click="submitRejectProject(project.id)" :disabled="projectActioning"
                        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
                  {{ projectActioning ? 'Šalje se...' : 'Potvrdi odbijanje' }}
                </button>
                <button @click="cancelProjectAction"
                        class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
                  Odustani
                </button>
              </template>
              <template v-else>
                <button @click="approveProject(project.id)" :disabled="projectActioning"
                        class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
                  {{ projectActioning ? 'Odobrava se...' : 'Odobri projekat' }}
                </button>
                <button @click="startRejectProject(project.id)" :disabled="projectActioning"
                        class="px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition-colors disabled:opacity-50">
                  Odbij projekat
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ── WORK ORDER APPROVALS ───────────────────────────────────────────── -->
    <template v-if="tab === 'nalozi'">
      <div v-if="ordersLoading" class="flex justify-center py-16">
        <div class="size-8 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
      </div>

      <!-- Empty state -->
      <div v-else-if="orders.length === 0"
           class="p-12 rounded-xl border-2 border-dashed border-neutral-300 dark:border-neutral-700 text-center">
        <svg class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
        </svg>
        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Nema naloga na čekanju</p>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Svi nalozi su pregledani ili još uvijek u izradi.</p>
      </div>

      <!-- Orders list -->
      <div v-else class="space-y-4">
      <div v-for="order in orders" :key="order.id"
           class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">

        <!-- Order header -->
        <div class="p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ order.name }}</h2>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                {{ order.project?.name }}<span v-if="order.project?.city"> · {{ order.project.city }}</span>
              </p>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                Kreirao: <strong class="text-zinc-700 dark:text-zinc-300">{{ order.created_by ?? '—' }}</strong>
                · {{ order.date }}
              </p>
              <p v-if="order.description" class="mt-1 text-xs text-zinc-400 italic">{{ order.description }}</p>
            </div>
            <div class="shrink-0 text-right">
              <p class="text-xs text-zinc-400 dark:text-zinc-500">Stavki</p>
              <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ order.items_count }}</p>
            </div>
          </div>

          <button @click="toggleOrderExpand(order.id)"
                  class="mt-3 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">
            {{ expandedOrders.has(order.id) ? '▲ Sakrij stavke' : '▼ Prikaži stavke' }}
          </button>
        </div>

        <!-- Items list (expandable) -->
        <div v-if="expandedOrders.has(order.id)" class="border-t border-neutral-200 dark:border-neutral-700">
          <div v-if="!order.items?.length" class="px-5 py-3 text-sm text-zinc-400 italic">Nema stavki.</div>
          <div v-else class="divide-y divide-neutral-100 dark:divide-neutral-800">
            <div v-for="item in order.items" :key="item.id"
                 class="flex items-center gap-3 px-5 py-3">
              <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    :class="typeClass(item.resource_type)">
                {{ typeLabel(item.resource_type) }}
              </span>
              <span class="flex-1 text-sm text-zinc-800 dark:text-zinc-200">{{ item.resource_name }}</span>
              <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ item.quantity }} {{ item.unit ?? '' }}</span>
            </div>
          </div>
        </div>

        <!-- Action bar -->
        <div class="px-5 py-4 bg-neutral-50 dark:bg-neutral-800/50 border-t border-neutral-200 dark:border-neutral-700">
          <!-- Reject form -->
          <div v-if="rejectingOrderId === order.id" class="mb-3">
            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Razlog odbijanja *</label>
            <textarea v-model="rejectNote" rows="2" placeholder="Opišite razlog odbijanja..."
                      class="w-full px-3 py-2 rounded-lg border text-sm bg-white dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
            ></textarea>
            <p v-if="actionError" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ actionError }}</p>
          </div>

          <div class="flex gap-2 flex-wrap">
            <template v-if="rejectingOrderId === order.id">
              <button @click="submitRejectOrder(order.id)" :disabled="actioning"
                      class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
                {{ actioning ? 'alje se...' : 'Potvrdi odbijanje' }}
              </button>
              <button @click="cancelAction"
                      class="px-4 py-2 rounded-lg border border-neutral-300 dark:border-neutral-600 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
                Odustani
              </button>
            </template>
            <template v-else>
              <button @click="approveOrder(order.id)" :disabled="actioning"
                      class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
                {{ actioning ? 'Odobrava se...' : 'Odobri nalog' }}
              </button>
              <button @click="startRejectOrder(order.id)" :disabled="actioning"
                      class="px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium transition-colors disabled:opacity-50">
                Odbij nalog
              </button>
            </template>
          </div>
        </div>
      </div>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const tab = ref('projekti')

const CABLE_LABELS = {
  '8Y0001_1': '8Y0001_1',
  '8Y0001_2': '8Y0001_2',
  '8Y0001_3': '8Y0001_3',
}

// ── Project approvals ──
const pendingProjects     = ref([])
const projectsLoading     = ref(false)
const rejectingProjectId  = ref(null)
const projectRejectNote   = ref('')
const projectActioning    = ref(false)
const projectActionError  = ref('')

async function loadPendingProjects() {
  projectsLoading.value = true
  try {
    const res  = await fetch(`${BASE}/api/pm/projects/pending-approval`, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    pendingProjects.value = data.projects ?? []
  } finally {
    projectsLoading.value = false
  }
}

function startRejectProject(id) {
  rejectingProjectId.value = id; projectRejectNote.value = ''; projectActionError.value = ''
}

function cancelProjectAction() {
  rejectingProjectId.value = null; projectRejectNote.value = ''; projectActionError.value = ''
}

async function approveProject(id) {
  projectActioning.value = true; projectActionError.value = ''
  try {
    const res = await fetch(`${BASE}/api/pm/projects/${id}/approve`, { method: 'POST', headers: hdrs() })
    if (res.ok) pendingProjects.value = pendingProjects.value.filter(p => p.id !== id)
    else { const d = await res.json(); projectActionError.value = d.message ?? 'Greška.' }
  } finally { projectActioning.value = false }
}

async function submitRejectProject(id) {
  if (!projectRejectNote.value.trim()) { projectActionError.value = 'Razlog odbijanja je obavezan.'; return }
  projectActioning.value = true; projectActionError.value = ''
  try {
    const res = await fetch(`${BASE}/api/pm/projects/${id}/reject-approval`, {
      method: 'POST', headers: hdrs(), body: JSON.stringify({ note: projectRejectNote.value }),
    })
    if (res.ok) { pendingProjects.value = pendingProjects.value.filter(p => p.id !== id); cancelProjectAction() }
    else { const d = await res.json(); projectActionError.value = d.message ?? 'Greška.' }
  } finally { projectActioning.value = false }
}

// ── Work order approvals ──
const orders          = ref([])
const ordersLoading   = ref(false)
const expandedOrders  = reactive(new Set())
const rejectingOrderId = ref(null)

const rejectNote  = ref('')
const actioning   = ref(false)
const actionError = ref('')

function hdrs() {
  return {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

function typeLabel(type) {
  return { equipment: 'Oprema', material: 'Materijal', service: 'Servis' }[type] ?? type
}

function typeClass(type) {
  return {
    equipment: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    material:  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    service:   'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
  }[type] ?? 'bg-zinc-100 text-zinc-700'
}

async function loadOrders() {
  ordersLoading.value = true
  try {
    const res  = await fetch(`${BASE}/api/pm/orders/pending`, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    orders.value = data.orders ?? []
  } finally {
    ordersLoading.value = false
  }
}

function toggleOrderExpand(id) {
  if (expandedOrders.has(id)) expandedOrders.delete(id)
  else expandedOrders.add(id)
}

function startRejectOrder(id) {
  rejectingOrderId.value = id; rejectNote.value = ''; actionError.value = ''
}

function cancelAction() {
  rejectingOrderId.value = null; rejectNote.value = ''; actionError.value = ''
}

async function approveOrder(id) {
  actioning.value = true; actionError.value = ''
  try {
    const res = await fetch(`${BASE}/api/pm/orders/${id}/approve`, { method: 'POST', headers: hdrs() })
    if (res.ok) orders.value = orders.value.filter(o => o.id !== id)
    else { const d = await res.json(); actionError.value = d.message ?? 'Greška.' }
  } finally { actioning.value = false }
}

async function submitRejectOrder(id) {
  if (!rejectNote.value.trim()) { actionError.value = 'Razlog odbijanja je obavezan.'; return }
  actioning.value = true; actionError.value = ''
  try {
    const res = await fetch(`${BASE}/api/pm/orders/${id}/reject`, {
      method: 'POST', headers: hdrs(), body: JSON.stringify({ note: rejectNote.value }),
    })
    if (res.ok) { orders.value = orders.value.filter(o => o.id !== id); cancelAction() }
    else { const d = await res.json(); actionError.value = d.message ?? 'Greška.' }
  } finally { actioning.value = false }
}

onMounted(() => {
  loadPendingProjects()
  loadOrders()
  document.addEventListener('livewire:navigated', () => { loadPendingProjects(); loadOrders() })
})
</script>
