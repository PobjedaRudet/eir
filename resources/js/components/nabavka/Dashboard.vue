<template>
  <div class="p-4 sm:p-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Nabavka</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Narudžbenice kreirane iz odobrenih naloga</p>
      </div>
      <button @click="load" :disabled="loading"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors disabled:opacity-50">
        <svg :class="loading ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
          <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd" />
        </svg>
        Osvježi
      </button>
    </div>

    <!-- Status tabs -->
    <div class="flex gap-1 p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800 w-fit mb-6">
      <button v-for="tab in tabs" :key="tab.key"
              @click="activeTab = tab.key"
              class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors relative"
              :class="activeTab === tab.key
                ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm'
                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'">
        {{ tab.label }}
        <span v-if="countByStatus[tab.key]"
              class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold"
              :class="tab.key === 'kreirana' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400'
                    : tab.key === 'narucena' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
                    : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'">
          {{ countByStatus[tab.key] }}
        </span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <svg class="animate-spin size-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>

    <!-- Empty state -->
    <div v-else-if="!filteredOrders.length" class="py-16 text-center text-zinc-400 text-sm italic">
      Nema narudžbenica u statusu "{{ tabs.find(t => t.key === activeTab)?.label }}".
    </div>

    <!-- Cards -->
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="po in filteredOrders" :key="po.id"
           class="flex flex-col rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
        <!-- Card header -->
        <div class="px-4 py-3 flex items-start justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-semibold text-zinc-900 dark:text-white text-sm">
                Nalog {{ po.work_order.name }}
              </span>
              <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold"
                    :class="statusClass(po.status)">
                {{ statusLabel(po.status) }}
              </span>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
              {{ po.work_order.project.name }}
              <span v-if="po.work_order.project.city"> · {{ po.work_order.project.city }}</span>
            </p>
          </div>
          <div class="shrink-0 text-right">
            <p class="text-[11px] text-zinc-400">{{ po.work_order.date }}</p>
            <p class="text-[11px] text-zinc-400">Vodja: {{ po.work_order.created_by }}</p>
          </div>
        </div>

        <!-- Items -->
        <div class="flex-1 px-4 py-2 divide-y divide-zinc-100 dark:divide-zinc-800">
          <div v-for="item in po.work_order.items" :key="item.id"
               class="flex items-center gap-2 py-1.5">
            <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-medium"
                  :class="item.resource_type === 'equipment'
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'">
              {{ item.resource_type === 'equipment' ? 'Oprema' : 'Mat.' }}
            </span>
            <span class="flex-1 text-xs text-zinc-700 dark:text-zinc-300 truncate">{{ item.resource_name }}</span>
            <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ item.quantity }} {{ item.unit ?? '' }}</span>
          </div>
          <p v-if="!po.work_order.items.length" class="py-2 text-xs text-zinc-400 italic">Nema stavki.</p>
        </div>

        <!-- Timestamps -->
        <div v-if="po.ordered_at || po.delivered_at"
             class="px-4 py-2 bg-zinc-50 dark:bg-zinc-800/40 text-[11px] text-zinc-400 space-y-0.5">
          <p v-if="po.ordered_at">Naručeno: {{ po.ordered_at }}</p>
          <p v-if="po.delivered_at">Isporučeno: {{ po.delivered_at }}</p>
        </div>

        <!-- Notes field (visible when actioning) -->
        <div v-if="actioning[po.id]" class="px-4 py-2 border-t border-zinc-100 dark:border-zinc-800">
          <textarea v-model="notes[po.id]" rows="2"
                    placeholder="Napomena (opcionalno)..."
                    class="w-full px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-xs text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
        </div>

        <!-- Actions -->
        <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-800 flex gap-2 flex-wrap">
          <!-- Kreirana → Naručena -->
          <template v-if="po.status === 'kreirana'">
            <template v-if="actioning[po.id]">
              <button @click="confirmOrdered(po)" :disabled="busy[po.id]"
                      class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors disabled:opacity-50">
                {{ busy[po.id] ? 'Šalje se...' : 'Potvrdi narudžbu' }}
              </button>
              <button @click="cancelAction(po.id)"
                      class="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                Odustani
              </button>
            </template>
            <button v-else @click="startAction(po.id)"
                    class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors">
              Označi kao naručenu
            </button>
          </template>

          <!-- Naručena → Isporučena -->
          <template v-else-if="po.status === 'narucena'">
            <template v-if="actioning[po.id]">
              <button @click="confirmDelivered(po)" :disabled="busy[po.id]"
                      class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition-colors disabled:opacity-50">
                {{ busy[po.id] ? 'Šalje se...' : 'Potvrdi isporuku' }}
              </button>
              <button @click="cancelAction(po.id)"
                      class="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                Odustani
              </button>
            </template>
            <button v-else @click="startAction(po.id)"
                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium transition-colors">
              Označi kao isporučenu
            </button>
          </template>

          <!-- Isporučena — done -->
          <template v-else>
            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
              </svg>
              Isporučeno
            </span>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const orders    = ref([])
const loading   = ref(true)
const activeTab = ref('kreirana')

const actioning = reactive({})
const busy      = reactive({})
const notes     = reactive({})

const tabs = [
  { key: 'kreirana',   label: 'Kreirana' },
  { key: 'narucena',   label: 'Naručena' },
  { key: 'isporucena', label: 'Isporučena' },
]

const countByStatus = computed(() => {
  const counts = { kreirana: 0, narucena: 0, isporucena: 0 }
  orders.value.forEach(o => { if (counts[o.status] !== undefined) counts[o.status]++ })
  return counts
})

const filteredOrders = computed(() =>
  orders.value.filter(o => o.status === activeTab.value)
)

function hdrs() {
  return {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

function statusLabel(s) {
  return { kreirana: 'Kreirana', narucena: 'Naručena', isporucena: 'Isporučena' }[s] ?? s
}
function statusClass(s) {
  return {
    kreirana:   'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    narucena:   'bg-blue-100   text-blue-700   dark:bg-blue-900/30   dark:text-blue-400',
    isporucena: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  }[s] ?? ''
}

async function load() {
  loading.value = true
  try {
    const res  = await fetch(`${BASE}/api/nabavka/purchase-orders`, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    orders.value = data.orders ?? []
  } finally {
    loading.value = false
  }
}

function startAction(id) {
  actioning[id] = true
  notes[id]     = ''
}
function cancelAction(id) {
  actioning[id] = false
  notes[id]     = ''
}

async function confirmOrdered(po) {
  busy[po.id] = true
  try {
    const res  = await fetch(`${BASE}/api/nabavka/purchase-orders/${po.id}/order`, {
      method: 'POST', headers: hdrs(),
      body: JSON.stringify({ notes: notes[po.id] || null }),
    })
    const data = await res.json()
    if (res.ok) {
      const idx = orders.value.findIndex(o => o.id === po.id)
      if (idx !== -1) orders.value[idx] = data.order
      cancelAction(po.id)
    }
  } finally { busy[po.id] = false }
}

async function confirmDelivered(po) {
  busy[po.id] = true
  try {
    const res  = await fetch(`${BASE}/api/nabavka/purchase-orders/${po.id}/deliver`, {
      method: 'POST', headers: hdrs(),
      body: JSON.stringify({ notes: notes[po.id] || null }),
    })
    const data = await res.json()
    if (res.ok) {
      const idx = orders.value.findIndex(o => o.id === po.id)
      if (idx !== -1) orders.value[idx] = data.order
      cancelAction(po.id)
    }
  } finally { busy[po.id] = false }
}

onMounted(() => {
  load()
  document.addEventListener('livewire:navigated', load)
})
</script>
