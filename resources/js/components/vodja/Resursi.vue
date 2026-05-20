<template>
  <div class="max-w-3xl">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <a :href="BASE + '/vodja/projekti'"
         class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </a>
      <div class="flex-1">
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Resursi projekta</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ project?.name ?? '...' }}</p>
      </div>
      <a :href="BASE + '/vodja/projekti/' + getProjectId() + '/servis'"
         class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.96-4.62a2.625 2.625 0 1 0-5.25 0m5.25 0a2.625 2.625 0 0 1-5.25 0" />
        </svg>
        Servisni nalozi
      </a>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="size-8 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- Error -->
    <div v-else-if="serverError" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
      {{ serverError }}
    </div>

    <!-- ===== LIST VIEW ===== -->
    <template v-else-if="view === 'list'">
      <!-- No active plan -->
      <div v-if="!activeSummary"
           class="p-8 rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 text-center">
        <svg class="size-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nema aktivnog plana</p>
        <p class="text-xs text-zinc-400 dark:text-zinc-500">Čekajte dok projekt menadžer ne kreira radni plan za ovaj projekat.</p>
      </div>

      <!-- Active plan card -->
      <div v-if="activeSummary"
           class="p-4 rounded-xl border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 mb-4">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-bold uppercase tracking-wider text-green-700 dark:text-green-400">Aktivan radni plan</span>
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200">
                v.{{ activeSummary.version }}
              </span>
            </div>
            <p v-if="activeSummary.description" class="text-sm text-green-800 dark:text-green-300 italic">"{{ activeSummary.description }}"</p>
            <p class="text-xs text-green-600 dark:text-green-500 mt-1">
              <span v-if="activeSummary.created_by">Kreirao: {{ activeSummary.created_by }} · </span>{{ activeSummary.created_at }}
            </p>
          </div>
        </div>

        <!-- Teams -->
        <div v-if="activeSummary.teams?.length" class="mb-3 space-y-2">
          <div v-for="team in activeSummary.teams" :key="team.id"
               class="p-2.5 rounded-lg bg-white dark:bg-zinc-800 border border-green-200 dark:border-green-700">
            <p class="text-xs font-semibold text-green-700 dark:text-green-400 mb-1.5">
              {{ team.name }}
              <span class="font-normal text-green-600 dark:text-green-500 ml-1">({{ team.workers.length }})</span>
            </p>
            <div class="flex flex-wrap gap-1">
              <span v-for="w in team.workers" :key="w.id"
                    class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-50 dark:bg-zinc-700 border border-green-200 dark:border-green-600 text-xs text-zinc-700 dark:text-zinc-300">
                <span class="size-4 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center text-xs font-bold text-green-700 dark:text-green-400">{{ w.name[0].toUpperCase() }}</span>
                {{ w.name }}
              </span>
            </div>
          </div>
        </div>

        <button @click="openOrders"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-700 hover:bg-green-800 text-white text-sm font-medium transition-colors">
          <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
          </svg>
          Nalozi ({{ ordersCount }})
        </button>
      </div>

      <!-- Plan history -->
      <div v-if="plans.length > 1">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-3">Prethodni planovi</h2>
        <div class="space-y-2">
          <div v-for="plan in plans.slice(1)" :key="plan.id"
               class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
              v.{{ plan.version }}
            </span>
            <div class="flex-1 min-w-0">
              <p v-if="plan.description" class="text-xs text-zinc-500 italic truncate">"{{ plan.description }}"</p>
              <p class="text-xs text-zinc-400">{{ plan.teams?.length ?? 0 }} timova · {{ plan.created_at }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ===== ORDERS VIEW ===== -->
    <template v-else-if="view === 'orders'">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-5">
        <button @click="view = 'list'"
                class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-zinc-500">
          <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
          </svg>
        </button>
        <div>
          <h2 class="text-base font-bold text-zinc-900 dark:text-white">Nalozi resursa</h2>
          <p class="text-xs text-zinc-500 dark:text-zinc-400">Plan v.{{ activeSummary?.version }} — {{ project?.name }}</p>
        </div>
      </div>

      <!-- Create order form -->
      <div class="mb-5 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
        <h3 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">Novi nalog</h3>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-3">Broj naloga se dodjeljuje automatski (npr. 1/26, 2/26...).</p>
        <div class="grid gap-3 sm:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Datum *</label>
            <input v-model="newOrderForm.date" type="date"
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Opis (opcionalno)</label>
            <input v-model="newOrderForm.description" type="text" placeholder="Kratki opis naloga..."
                   class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-zinc-400" />
          </div>
        </div>
        <p v-if="newOrderError" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ newOrderError }}</p>
        <button @click="createOrder" :disabled="creatingOrder"
                class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-800 dark:bg-zinc-100 hover:bg-zinc-700 dark:hover:bg-white text-white dark:text-zinc-900 text-sm font-medium transition-colors disabled:opacity-50">
          {{ creatingOrder ? 'Kreiranje...' : '+ Kreiraj nalog' }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="ordersLoading" class="flex justify-center py-8">
        <div class="size-7 border-4 border-zinc-200 border-t-blue-500 rounded-full animate-spin"></div>
      </div>

      <!-- Empty state -->
      <div v-else-if="orders.length === 0"
           class="p-8 rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 text-center text-sm text-zinc-400">
        Nema kreiranih naloga. Kreirajte prvi nalog gore.
      </div>

      <!-- Orders list -->
      <div v-else class="space-y-3">
        <div v-for="order in orders" :key="order.id"
             class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">

          <!-- Order header -->
          <div class="flex items-start justify-between gap-3 px-4 py-3">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ order.name }}</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass(order.status)">
                  {{ statusLabel(order.status) }}
                </span>
              </div>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                {{ order.date }}<span v-if="order.created_by"> · {{ order.created_by }}</span>
              </p>
              <p v-if="order.description" class="text-xs text-zinc-400 dark:text-zinc-500 italic mt-0.5">{{ order.description }}</p>
              <!-- Rejection note -->
              <div v-if="order.status === 'rejected' && order.review_note"
                   class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                <span class="font-medium">Razlog odbijanja:</span> {{ order.review_note }}
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button v-if="order.status === 'draft'"
                      @click="submitOrder(order)"
                      :disabled="!order.items.length"
                      :title="!order.items.length ? 'Dodajte stavke prije podnošenja' : ''"
                      class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-blue-600">
                Podnesi
              </button>
              <button @click="toggleOrderExpand(order.id)"
                      class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-700 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                {{ expandedOrders.has(order.id) ? '▲ Zatvori' : '▼ Stavke (' + order.items.length + ')' }}
              </button>
              <button v-if="order.status === 'draft' || order.status === 'rejected'"
                      @click="deleteOrder(order)"
                      class="p-1.5 rounded-lg text-zinc-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Expanded: items + add form -->
          <div v-if="expandedOrders.has(order.id)" class="border-t border-zinc-200 dark:border-zinc-700">

            <!-- Items list -->
            <div v-if="order.items.length" class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3 px-4 py-2.5">
                <span class="shrink-0 px-2 py-0.5 rounded text-xs font-medium"
                      :class="{
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': item.resource_type === 'equipment',
                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300': item.resource_type === 'material',
                      }">
                  {{ { equipment: 'Oprema', material: 'Mat.' }[item.resource_type] }}
                </span>
                <span class="flex-1 text-sm text-zinc-800 dark:text-zinc-200">{{ item.resource_name }}</span>
                <span class="text-sm text-zinc-500 dark:text-zinc-400 shrink-0">{{ item.quantity }} {{ item.unit ?? '' }}</span>
                <!-- Service status badge -->
                <span v-if="item.service_qty_sent > 0"
                      class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                  Na servisu ({{ item.service_qty_sent }})
                </span>
                <span v-else-if="item.service_status === 'returned'"
                      class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                  Vraćeno
                </span>
                <!-- Send to service button: equipment, approved order, still has remaining quantity -->
                <button v-if="item.resource_type === 'equipment' && order.status === 'approved' && (item.service_qty_sent ?? 0) < item.quantity"
                        @click="openServiceModal(item)"
                        class="shrink-0 px-2 py-0.5 rounded text-xs font-medium bg-orange-50 text-orange-600 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400 dark:hover:bg-orange-900/40 transition-colors">
                  Pošalji na servis
                </button>
                <button @click="removeOrderItem(order, item)"
                        class="p-1 text-zinc-400 hover:text-red-500 transition-colors shrink-0">
                  <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                  </svg>
                </button>
              </div>
            </div>
            <p v-else class="px-4 py-3 text-xs text-zinc-400 italic">Nema stavki. Dodajte resurse ispod.</p>

            <!-- Add item form -->
            <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700">
              <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Dodaj stavku</p>
              <div class="flex gap-1 p-1 rounded-lg bg-zinc-200 dark:bg-zinc-700 mb-3 w-fit">
                <button v-for="t in resourceTypes" :key="t.key"
                        @click="orderItemForms[order.id].resource_type = t.key; orderItemForms[order.id].resource_id = ''; orderItemForms[order.id].unit = ''"
                        class="px-3 py-1 rounded-md text-xs font-medium transition-colors"
                        :class="orderItemForms[order.id].resource_type === t.key ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'">
                  {{ t.label }}
                </button>
              </div>
              <div class="grid gap-2 sm:grid-cols-3 mb-2">
                <div class="sm:col-span-1">
                  <select v-model="orderItemForms[order.id].resource_id"
                          @change="onOrderResourceSelected(order.id)"
                          class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Odaberite resurs —</option>
                    <optgroup v-for="(group, cat) in orderCatalogGrouped(orderItemForms[order.id].resource_type)" :key="cat" :label="cat">
                      <option v-for="r in group" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </optgroup>
                  </select>
                </div>
                <div>
                  <input v-model.number="orderItemForms[order.id].quantity" type="number" min="0.01" step="0.01" placeholder="Količina"
                         class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                  <input v-model="orderItemForms[order.id].unit" type="text" placeholder="Jedinica (kom, m...)"
                         class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
              </div>
              <button @click="addOrderItem(order)"
                      :disabled="addingOrderItem[order.id] || !orderItemForms[order.id]?.resource_id"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-800 dark:bg-zinc-100 hover:bg-zinc-700 dark:hover:bg-white text-white dark:text-zinc-900 text-xs font-medium transition-colors disabled:opacity-50">
                {{ addingOrderItem[order.id] ? 'Dodaje se...' : '+ Dodaj' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>

  <!-- Send to service modal -->
  <div v-if="serviceModal.show"
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-xl p-6">
      <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-1">Pošalji na servis</h3>
      <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">{{ serviceModal.item?.resource_name }}</p>
      <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">
        Količina (dostupno: {{ serviceModal.item ? serviceModal.item.quantity - (serviceModal.item.service_qty_sent ?? 0) : '' }} {{ serviceModal.item?.unit ?? '' }})
      </label>
      <input v-model.number="serviceModal.quantity" type="number" min="0.01"
             :max="serviceModal.item ? serviceModal.item.quantity - (serviceModal.item.service_qty_sent ?? 0) : undefined"
             step="0.01"
             class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-orange-500" />
      <div class="flex justify-end gap-2 mt-4">
        <button @click="serviceModal.show = false"
                class="px-4 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
          Odustani
        </button>
        <button @click="confirmSendToService" :disabled="serviceModal.sending || !serviceModal.quantity"
                class="px-4 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
          {{ serviceModal.sending ? 'Šalje se...' : 'Pošalji' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

const view         = ref('list')
const loading      = ref(true)
const serverError  = ref('')

const project      = ref(null)
const plans        = ref([])
const activeSummary = ref(null)
const ordersCount  = ref(0)

const orders         = ref([])
const ordersLoading  = ref(false)
const expandedOrders = reactive(new Set())
const creatingOrder  = ref(false)
const newOrderError  = ref('')
const newOrderForm   = reactive({ date: new Date().toISOString().slice(0, 10), description: '' })
const orderItemForms = reactive({})
const addingOrderItem = reactive({})

const catalog = ref({ equipment: [], materials: [] })

const resourceTypes = [
  { key: 'equipment', label: 'Oprema' },
  { key: 'material',  label: 'Materijali' },
]

function getProjectId() {
  return window.location.pathname.split('/').at(-2)
}

function hdrs() {
  return {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

function statusLabel(s) {
  return { draft: 'Nacrt', submitted: 'Na čekanju', approved: 'Odobren', rejected: 'Odbijen' }[s] ?? s
}
function statusClass(s) {
  return {
    draft:     'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
    submitted: 'bg-blue-100  dark:bg-blue-900/30  text-blue-700  dark:text-blue-400',
    approved:  'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    rejected:  'bg-red-100   dark:bg-red-900/30   text-red-700   dark:text-red-400',
  }[s] ?? ''
}

async function loadList() {
  try {
    const res  = await fetch(`${BASE}/api/vodja/projects/${getProjectId()}/plans`, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    project.value       = data.project
    plans.value         = data.plans ?? []
    activeSummary.value = data.active  ?? null
    ordersCount.value   = data.orders_count ?? 0
  } catch {
    serverError.value = 'Greška pri učitavanju podataka.'
  } finally {
    loading.value = false
  }
}

async function openOrders() {
  view.value          = 'orders'
  ordersLoading.value = true
  try {
    const [ordersRes, catalogRes] = await Promise.all([
      fetch(`${BASE}/api/vodja/projects/${getProjectId()}/orders`, { headers: { Accept: 'application/json' } }),
      catalog.value.equipment.length
        ? Promise.resolve(null)
        : fetch(`${BASE}/api/vodja/catalog`, { headers: { Accept: 'application/json' } }),
    ])
    orders.value = (await ordersRes.json()).orders ?? []
    if (catalogRes) {
      catalog.value = await catalogRes.json()
    }
  } finally {
    ordersLoading.value = false
  }
}

async function createOrder() {
  newOrderError.value = ''
  if (!newOrderForm.date)        { newOrderError.value = 'Datum je obavezan.'; return }
  creatingOrder.value = true
  try {
    const res  = await fetch(`${BASE}/api/vodja/projects/${getProjectId()}/orders`, {
      method: 'POST',
      headers: hdrs(),
      body: JSON.stringify({ ...newOrderForm, plan_id: activeSummary.value.id }),
    })
    const data = await res.json()
    if (!res.ok) { newOrderError.value = data.message ?? 'Greška.'; return }
    orders.value.unshift(data)
    ordersCount.value++
    newOrderForm.date = new Date().toISOString().slice(0, 10); newOrderForm.description = ''
  } finally {
    creatingOrder.value = false
  }
}

function toggleOrderExpand(id) {
  if (expandedOrders.has(id)) {
    expandedOrders.delete(id)
    delete orderItemForms[id]
  } else {
    expandedOrders.add(id)
    orderItemForms[id] = { resource_type: 'equipment', resource_id: '', quantity: 1, unit: '' }
  }
}

function currentOrderCatalog(resourceType) {
  if (resourceType === 'equipment') return catalog.value.equipment ?? []
  return catalog.value.materials ?? []
}

function orderCatalogGrouped(resourceType) {
  return currentOrderCatalog(resourceType).reduce((acc, item) => {
    const cat = item.category || 'Ostalo'
    if (!acc[cat]) acc[cat] = []
    acc[cat].push(item)
    return acc
  }, {})
}

function onOrderResourceSelected(orderId) {
  const form  = orderItemForms[orderId]
  const found = currentOrderCatalog(form.resource_type).find(r => r.id === form.resource_id)
  if (found) form.unit = found.unit || ''
}

async function addOrderItem(order) {
  const form = orderItemForms[order.id]
  if (!form.resource_id) return
  addingOrderItem[order.id] = true
  try {
    const res  = await fetch(`${BASE}/api/vodja/orders/${order.id}/items`, {
      method: 'POST',
      headers: hdrs(),
      body: JSON.stringify({ ...form }),
    })
    const data = await res.json()
    if (!res.ok) return
    order.items.push(data)
    form.resource_id = ''; form.quantity = 1; form.unit = ''
  } finally {
    addingOrderItem[order.id] = false
  }
}

async function removeOrderItem(order, item) {
  await fetch(`${BASE}/api/vodja/orders/${order.id}/items/${item.id}`, { method: 'DELETE', headers: hdrs() })
  order.items = order.items.filter(i => i.id !== item.id)
}

const serviceModal = reactive({ show: false, item: null, quantity: 1, sending: false })

function openServiceModal(item) {
  const remaining = item.quantity - (item.service_qty_sent ?? 0)
  serviceModal.item     = item
  serviceModal.quantity = remaining > 0 ? Math.min(1, remaining) : 1
  serviceModal.show     = true
}

async function confirmSendToService() {
  const item = serviceModal.item
  serviceModal.sending = true
  try {
    const res = await fetch(`${BASE}/api/vodja/service-orders`, {
      method: 'POST',
      headers: hdrs(),
      body: JSON.stringify({ work_order_item_id: item.id, quantity_sent: serviceModal.quantity }),
    })
    if (res.ok) {
      const data = await res.json()
      item.service_status    = 'sent'
      item.service_qty_sent  = (item.service_qty_sent ?? 0) + data.quantity_sent
      item.service_order_id  = data.id
      serviceModal.show = false
    } else {
      const err = await res.json()
      alert(err.message ?? 'Greška pri slanju na servis.')
    }
  } finally {
    serviceModal.sending = false
  }
}

async function deleteOrder(order) {
  if (!confirm(`Sigurno obrisati nalog "${order.name}"?`)) return
  await fetch(`${BASE}/api/vodja/orders/${order.id}`, { method: 'DELETE', headers: hdrs() })
  orders.value = orders.value.filter(o => o.id !== order.id)
  ordersCount.value = Math.max(0, ordersCount.value - 1)
  expandedOrders.delete(order.id)
}

async function submitOrder(order) {
  const res = await fetch(`${BASE}/api/vodja/orders/${order.id}/submit`, { method: 'POST', headers: hdrs() })
  if (res.ok) {
    order.status = 'submitted'
    expandedOrders.delete(order.id)
  }
}

onMounted(() => {
  loadList()
  document.addEventListener('livewire:navigated', loadList)
})
</script>
