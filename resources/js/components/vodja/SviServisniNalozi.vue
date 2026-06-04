<template>
  <div class="max-w-3xl">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <div class="flex-1">
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Servisni nalozi</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Sva oprema poslana na servis</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="size-8 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- Error -->
    <div v-else-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
      {{ serverError }}
    </div>

    <template v-else>
      <!-- Filter tabs -->
      <div class="flex gap-1 p-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 w-fit mb-5">
        <button v-for="tab in tabs" :key="tab.key"
                @click="activeTab = tab.key"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                :class="activeTab === tab.key
                  ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm'
                  : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'">
          {{ tab.label }}
          <span v-if="tab.key !== 'all' && countByStatus[tab.key]"
                class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
            {{ countByStatus[tab.key] }}
          </span>
        </button>
      </div>

      <!-- Empty state -->
      <div v-if="!filteredOrders.length" class="text-center py-12 text-zinc-400 dark:text-zinc-500">
        <svg class="size-10 mx-auto mb-3 opacity-40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.96-4.62a2.625 2.625 0 1 0-5.25 0m5.25 0a2.625 2.625 0 0 1-5.25 0" />
        </svg>
        <p class="text-sm">Nema servisnih naloga u izabranom statusu.</p>
      </div>

      <!-- Orders list -->
      <div v-else class="space-y-3">
        <div v-for="so in filteredOrders" :key="so.id"
             class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
          <div class="flex items-start gap-4 px-4 py-3">
            <!-- Icon -->
            <div class="mt-0.5 shrink-0 size-8 rounded-lg flex items-center justify-center"
                 :class="so.status === 'pending_procurement'
                   ? 'bg-amber-100 dark:bg-amber-900/30'
                   : so.status === 'sent_to_supplier'
                     ? 'bg-blue-100 dark:bg-blue-900/30'
                     : 'bg-emerald-100 dark:bg-emerald-900/30'">
              <svg v-if="so.status === 'pending_procurement'" class="size-4 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.96-4.62a2.625 2.625 0 1 0-5.25 0m5.25 0a2.625 2.625 0 0 1-5.25 0" />
              </svg>
              <svg v-else-if="so.status === 'sent_to_supplier'" class="size-4 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3 15m0 0 3 3m-3-3h15M18 12l3-3m0 0-3-3m3 3H6" />
              </svg>
              <svg v-else class="size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
              </svg>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-sm text-zinc-900 dark:text-white">{{ so.item_name }}</span>
                <span class="text-xs text-zinc-400">{{ so.quantity_sent }} / {{ so.item_quantity }} {{ so.item_unit ?? '' }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="badgeClass(so.status)">
                  {{ so.status_label }}
                </span>
              </div>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ so.project_name }}</span>
                &nbsp;·&nbsp; Nalog: <span class="font-medium">{{ so.work_order_label }}</span>
                &nbsp;·&nbsp; Poslano: {{ so.sent_at }}
                <template v-if="so.forwarded_at">&nbsp;·&nbsp; Proslijeđeno: {{ so.forwarded_at }}</template>
                <template v-if="so.returned_at">&nbsp;·&nbsp; Vraćeno: {{ so.returned_at }}</template>
              </p>
              <p v-if="so.supplier_name || so.supplier_email" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                Dobavljač: {{ so.supplier_name || 'Nije upisano' }}<template v-if="so.supplier_email"> ({{ so.supplier_email }})</template>
              </p>
              <p v-if="so.handled_by" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                Nabavka: {{ so.handled_by }}
              </p>
              <p v-if="so.source_label" class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                Izvor: {{ so.source_label }}
              </p>
              <p v-if="so.note" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 italic">{{ so.note }}</p>
              <p v-if="so.procurement_note" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 italic">{{ so.procurement_note }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Return modal -->
    <div v-if="false && returnModal.show"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
      <div class="w-full max-w-md bg-white dark:bg-zinc-900 rounded-2xl shadow-xl p-6">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">Evidentiraj povratak</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">{{ returnModal.order?.item_name }}</p>
        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Napomena (opcionalno)</label>
        <textarea v-model="returnModal.note" rows="3"
                  class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"
                  placeholder="Stanje opreme, napomene..."></textarea>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="returnModal.show = false"
                  class="px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
            Odustani
          </button>
          <button @click="confirmReturn" :disabled="returning"
                  class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
            {{ returning ? 'Sprema se...' : 'Potvrdi povratak' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { BASE } from '../../utils/base.js'

const loading     = ref(true)
const serverError = ref('')
const orders      = ref([])
const activeTab   = ref('pending_procurement')
const returning   = ref(false)

const returnModal = reactive({ show: false, order: null, note: '' })

const tabs = [
  { key: 'pending_procurement', label: 'Čeka slanje na servis' },
  { key: 'sent_to_supplier', label: 'Kod dobavljača' },
  { key: 'returned', label: 'Vraćeno' },
  { key: 'all',      label: 'Svi' },
]

const filteredOrders = computed(() => {
  if (activeTab.value === 'all') return orders.value
  return orders.value.filter(o => o.status === activeTab.value)
})

const countByStatus = computed(() => orders.value.reduce((carry, order) => {
  carry[order.status] = (carry[order.status] ?? 0) + 1
  return carry
}, {}))

function badgeClass(status) {
  return {
    pending_procurement: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    sent_to_supplier: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    returned: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  }[status] ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
}

function hdrs() {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }
}

async function load() {
  loading.value = true
  serverError.value = ''
  try {
    const res  = await fetch(`${BASE}/api/vodja/service-orders`, { headers: hdrs() })
    orders.value = await res.json()
  } catch (e) {
    serverError.value = 'Greška pri učitavanju podataka.'
  } finally {
    loading.value = false
  }
}

function openReturnModal(so) {
  returnModal.order = so
  returnModal.note  = so.note ?? ''
  returnModal.show  = true
}

async function confirmReturn() {
  if (!returnModal.order) return
  returning.value = true
  try {
    const res = await fetch(`${BASE}/api/vodja/service-orders/${returnModal.order.id}/return`, {
      method: 'POST',
      headers: hdrs(),
      body: JSON.stringify({ note: returnModal.note }),
    })
    if (res.ok) {
      const updated = await res.json()
      const idx = orders.value.findIndex(o => o.id === updated.id)
      if (idx !== -1) orders.value[idx] = { ...orders.value[idx], ...updated }
      returnModal.show = false
    } else {
      const err = await res.json()
      alert(err.message ?? 'Greška.')
    }
  } finally {
    returning.value = false
  }
}

onMounted(load)
document.addEventListener('livewire:navigated', load)
</script>
