<template>
  <div class="p-4 sm:p-6">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Nabavka</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">Upravljanje narudžbenicama i praćenje nabavke po nalozima</p>
      </div>
      <div class="flex items-center gap-2">
        <button @click="loadAll" :disabled="loading"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors disabled:opacity-50">
          <svg :class="loading ? 'animate-spin' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd" />
          </svg>
          Osvježi
        </button>
        <button v-if="activeMainTab === 'nalozi'" @click="openCreateModal"
                class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
          </svg>
          Nova narudžbenica
        </button>
      </div>
    </div>

    <!-- Main tabs -->
    <div class="flex gap-1 p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800 mb-6 w-full sm:w-auto sm:inline-flex">
      <button @click="activeMainTab = 'nalozi'"
              class="flex-1 sm:flex-none px-5 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="activeMainTab === 'nalozi'
                ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm'
                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'">
        Radni nalozi
        <span v-if="workOrders.length" class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
          {{ workOrders.length }}
        </span>
      </button>
      <button @click="activeMainTab = 'narudzbenice'"
              class="flex-1 sm:flex-none px-5 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="activeMainTab === 'narudzbenice'
                ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm'
                : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'">
        Narudžbenice
        <span v-if="orders.length" class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
          {{ orders.length }}
        </span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <svg class="animate-spin size-9 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>

    <!-- TAB: Radni nalozi -->
    <template v-else-if="activeMainTab === 'nalozi'">
      <div v-if="!workOrders.length" class="py-20 flex flex-col items-center gap-3 text-zinc-400">
        <svg class="size-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
        </svg>
        <p class="text-sm">Nema odobrenih radnih naloga.</p>
      </div>

      <div v-else class="grid gap-5 grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3">
        <div v-for="wo in workOrders" :key="wo.id"
             class="flex flex-col rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">

          <!-- WO header -->
          <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <div class="flex items-start justify-between gap-3 mb-2">
              <div class="min-w-0">
                <p class="text-base font-bold text-zinc-900 dark:text-white tracking-tight">{{ wo.name }}</p>
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 truncate">
                  {{ wo.project.name }}
                  <span v-if="wo.project.city" class="font-normal text-zinc-400 dark:text-zinc-500"> · {{ wo.project.city }}</span>
                </p>
              </div>
              <!-- Overall status badge -->
              <span v-if="woAllOrdered(wo)" class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                Sve naručeno
              </span>
              <span v-else-if="woPendingCount(wo) === wo.items.length" class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                Nije naručeno
              </span>
              <span v-else class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                Parcijalno
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
              <span class="flex items-center gap-1 text-xs text-zinc-400">
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                </svg>
                {{ wo.date }}
              </span>
              <span class="flex items-center gap-1 text-xs text-zinc-400">
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-5.5-2.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM10 12a5.99 5.99 0 0 0-4.793 2.39A6.483 6.483 0 0 0 10 16.5a6.483 6.483 0 0 0 4.793-2.11A5.99 5.99 0 0 0 10 12Z" clip-rule="evenodd" />
                </svg>
                {{ wo.created_by }}
              </span>
            </div>
          </div>

          <!-- Items table -->
          <div class="flex-1 px-5 py-3">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-2">Stavke</p>
            <div v-if="wo.items.length" class="space-y-1">
              <div v-for="item in wo.items" :key="item.id"
                   class="flex items-center gap-2 rounded-lg px-3 py-1.5"
                   :class="item.ordered_qty >= item.quantity
                     ? 'bg-emerald-50 dark:bg-emerald-900/10'
                     : item.ordered_qty > 0
                       ? 'bg-blue-50 dark:bg-blue-900/10'
                       : 'bg-zinc-50 dark:bg-zinc-800/60'">
                <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide"
                      :class="item.resource_type === 'equipment'
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'">
                  {{ item.resource_type === 'equipment' ? 'Oprema' : 'Materijal' }}
                </span>
                <span class="flex-1 text-sm text-zinc-800 dark:text-zinc-200 truncate">{{ item.resource_name }}</span>
                <!-- qty info -->
                <span class="shrink-0 text-xs tabular-nums text-zinc-500 dark:text-zinc-400 text-right leading-tight">
                  <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ item.ordered_qty }}</span>
                  <span class="text-zinc-400">/{{ item.quantity }}</span>
                  <span v-if="item.unit" class="ml-0.5 text-zinc-400">{{ item.unit }}</span>
                </span>
                <!-- status icon -->
                <span v-if="item.ordered_qty >= item.quantity" class="shrink-0 text-emerald-500">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                  </svg>
                </span>
                <span v-else-if="item.ordered_qty > 0" class="shrink-0 text-blue-400">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                  </svg>
                </span>
                <span v-else class="shrink-0 text-zinc-300 dark:text-zinc-600">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v4.5h-2.5a.75.75 0 0 0 0 1.5h3.25a.75.75 0 0 0 .75-.75v-5.25Z" clip-rule="evenodd" />
                  </svg>
                </span>
              </div>
            </div>
            <p v-else class="text-sm text-zinc-400 italic">Nema stavki.</p>
          </div>

          <!-- Action button -->
          <div class="px-5 py-4 border-t border-zinc-100 dark:border-zinc-800">
            <button @click="openCreateModalFor(wo)"
                    class="w-full py-2.5 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-700 text-blue-600 dark:text-blue-400 text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors flex items-center justify-center gap-2">
              <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
              </svg>
              Napravi narudžbenicu
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- TAB: Narudžbenice -->
    <template v-else>
      <!-- Status sub-tabs -->
      <div class="flex gap-1 p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800 mb-6 w-full sm:w-auto sm:inline-flex">
        <button v-for="tab in statusTabs" :key="tab.key"
                @click="activeStatusTab = tab.key"
                class="flex-1 sm:flex-none px-5 py-2 rounded-lg text-sm font-medium transition-colors"
                :class="activeStatusTab === tab.key
                  ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm'
                  : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'">
          {{ tab.label }}
          <span v-if="countByStatus[tab.key]"
                class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                :class="tab.key === 'kreirana'   ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400'
                      : tab.key === 'narucena'   ? 'bg-blue-100   text-blue-700   dark:bg-blue-900/40   dark:text-blue-400'
                      :                           'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'">
            {{ countByStatus[tab.key] }}
          </span>
        </button>
      </div>

      <div v-if="!filteredOrders.length" class="py-20 flex flex-col items-center gap-3 text-zinc-400">
        <svg class="size-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
        </svg>
        <p class="text-sm">Nema narudžbenica u statusu <strong class="text-zinc-600 dark:text-zinc-300">{{ statusTabs.find(t => t.key === activeStatusTab)?.label }}</strong>.</p>
      </div>

      <!-- PO cards -->
      <div v-else class="grid gap-5 grid-cols-1 md:grid-cols-2 2xl:grid-cols-3">
        <div v-for="po in filteredOrders" :key="po.id"
             class="flex flex-col rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">

          <!-- Status stripe -->
          <div class="h-1.5"
               :class="po.status === 'kreirana'   ? 'bg-yellow-400'
                     : po.status === 'narucena'   ? 'bg-blue-500'
                     :                             'bg-emerald-500'">
          </div>

          <!-- PO header -->
          <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <div class="flex items-start justify-between gap-3 mb-1">
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-base font-bold text-zinc-900 dark:text-white tracking-tight">
                    Narudžbenica #{{ po.id }}
                  </span>
                  <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                        :class="statusClass(po.status)">
                    {{ statusLabel(po.status) }}
                  </span>
                </div>
                <!-- linked work orders -->
                <div v-if="po.work_orders?.length" class="mt-1 flex flex-wrap gap-1">
                  <span v-for="wo in po.work_orders" :key="wo.id"
                        class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ wo.name }} · {{ wo.project.name }}<span v-if="wo.project.city"> ({{ wo.project.city }})</span>
                  </span>
                </div>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5">
              <span class="flex items-center gap-1 text-xs text-zinc-400">
                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                </svg>
                Kreirana: {{ po.created_at }}
              </span>
            </div>
          </div>

          <!-- PO items -->
          <div class="flex-1 px-5 py-3">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-2">Stavke</p>
            <div v-if="po.items?.length" class="space-y-1">
              <div v-for="item in po.items" :key="item.id"
                   class="flex items-center gap-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 px-3 py-1.5">
                <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide"
                      :class="item.resource_type === 'equipment'
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'">
                  {{ item.resource_type === 'equipment' ? 'Oprema' : 'Materijal' }}
                </span>
                <span class="flex-1 text-sm text-zinc-800 dark:text-zinc-200 truncate">{{ item.resource_name }}</span>
                <span class="shrink-0 text-sm font-semibold text-zinc-600 dark:text-zinc-400 tabular-nums">
                  {{ item.quantity }}<span v-if="item.unit" class="font-normal text-zinc-400 ml-0.5">{{ item.unit }}</span>
                </span>
              </div>
            </div>
            <p v-else class="text-sm text-zinc-400 italic">Nema stavki.</p>
          </div>

          <!-- Timestamps -->
          <div v-if="po.ordered_at || po.delivered_at"
               class="mx-5 mb-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800 px-3 py-2 space-y-1">
            <div v-if="po.ordered_at" class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
              <svg class="size-3.5 text-blue-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.154.75.75 0 0 0 0-1.115A28.897 28.897 0 0 0 3.105 2.288Z" />
              </svg>
              <span>Naručeno: <strong class="text-zinc-700 dark:text-zinc-300">{{ po.ordered_at }}</strong></span>
            </div>
            <div v-if="po.delivered_at" class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
              <svg class="size-3.5 text-emerald-500 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
              </svg>
              <span>Isporučeno: <strong class="text-zinc-700 dark:text-zinc-300">{{ po.delivered_at }}</strong></span>
            </div>
          </div>

          <!-- Notes textarea when actioning (manual order) -->
          <div v-if="actioning[po.id] === 'order'" class="px-5 pb-3">
            <textarea v-model="notes[po.id]" rows="2"
                      placeholder="Napomena (opcionalno)..."
                      class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
          </div>

          <!-- Supplier form when sending by email -->
          <div v-if="actioning[po.id] === 'send'" class="px-5 pb-3 space-y-2">
            <div>
              <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Naziv dobavljača</label>
              <input type="text" v-model="supplierData[po.id].name" placeholder="Naziv firme (opcionalno)"
                     class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Email dobavljača <span class="text-red-500">*</span></label>
              <input type="email" v-model="supplierData[po.id].email" placeholder="dobavljac@firma.ba"
                     class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Napomena</label>
              <textarea v-model="notes[po.id]" rows="2"
                        placeholder="Napomena za narudžbenicu (opcionalno)..."
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
          </div>

          <!-- Notes textarea when actioning (deliver) -->
          <div v-if="actioning[po.id] === 'deliver'" class="px-5 pb-3">
            <textarea v-model="notes[po.id]" rows="2"
                      placeholder="Napomena (opcionalno)..."
                      class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
          </div>

          <!-- Actions -->
          <div class="px-5 py-4 border-t border-zinc-100 dark:border-zinc-800">

            <!-- Kreirana → Naručena -->
            <template v-if="po.status === 'kreirana'">
              <!-- Confirm manual order -->
              <div v-if="actioning[po.id] === 'order'" class="flex gap-2">
                <button @click="confirmOrdered(po)" :disabled="busy[po.id]"
                        class="flex-1 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
                  {{ busy[po.id] ? 'šalje se...' : 'Potvrdi narudžbu' }}
                </button>
                <button @click="cancelAction(po.id)"
                        class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                  Odustani
                </button>
              </div>
              <!-- Confirm send to supplier -->
              <div v-else-if="actioning[po.id] === 'send'" class="flex gap-2">
                <button @click="confirmSendToSupplier(po)" :disabled="busy[po.id] || !supplierData[po.id]?.email"
                        class="flex-1 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
                  {{ busy[po.id] ? 'Slanje...' : 'Pošalji PDF i naruči' }}
                </button>
                <button @click="cancelAction(po.id)"
                        class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                  Odustani
                </button>
              </div>
              <!-- Default: show action buttons -->
              <div v-else class="flex flex-col gap-2">
                <button @click="startSendAction(po.id)"
                        class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.154.75.75 0 0 0 0-1.115A28.897 28.897 0 0 0 3.105 2.288Z" />
                  </svg>
                  Pošalji PDF dobavljaču
                </button>
                <div class="flex gap-2">
                  <button @click="startAction(po.id, 'order')"
                          class="flex-1 py-2 rounded-lg border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-400 text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                    Samo označi kao naručenu
                  </button>
                  <a :href="`${BASE}/api/nabavka/purchase-orders/${po.id}/pdf`" target="_blank"
                     class="px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors flex items-center gap-1.5">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                      <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                    </svg>
                    PDF
                  </a>
                </div>
              </div>
            </template>

            <!-- Naručena → Isporučena -->
            <template v-else-if="po.status === 'narucena'">
              <div v-if="actioning[po.id] === 'deliver'" class="flex gap-2">
                <button @click="confirmDelivered(po)" :disabled="busy[po.id]"
                        class="flex-1 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors disabled:opacity-50">
                  {{ busy[po.id] ? 'šalje se...' : 'Potvrdi isporuku' }}
                </button>
                <button @click="cancelAction(po.id)"
                        class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                  Odustani
                </button>
              </div>
              <div v-else class="flex gap-2">
                <button @click="startAction(po.id, 'deliver')"
                        class="flex-1 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 1a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 1ZM5.05 3.05a.75.75 0 0 1 1.06 0l1.062 1.06A.75.75 0 1 1 6.11 5.173L5.05 4.11a.75.75 0 0 1 0-1.06Zm9.9 0a.75.75 0 0 1 0 1.06l-1.06 1.062a.75.75 0 0 1-1.062-1.061l1.061-1.061a.75.75 0 0 1 1.061 0Zm-9.95 6a4.5 4.5 0 0 1 8.285-2.434.75.75 0 0 1-1.298.752 3 3 0 1 0 .478 3.35.75.75 0 1 1 1.342.668A4.5 4.5 0 1 1 5 9.05Zm7.25 1.95a.75.75 0 0 1 .75-.75h2a.75.75 0 0 1 0 1.5h-2a.75.75 0 0 1-.75-.75Zm-9 0a.75.75 0 0 1 .75-.75h2a.75.75 0 0 1 0 1.5H4a.75.75 0 0 1-.75-.75Zm13.154 5.257a.75.75 0 0 1-1.06 0l-1.061-1.06a.75.75 0 1 1 1.06-1.062l1.061 1.061a.75.75 0 0 1 0 1.061Zm-10.243 0a.75.75 0 0 1 0-1.06l1.061-1.062a.75.75 0 0 1 1.062 1.061l-1.061 1.061a.75.75 0 0 1-1.062 0ZM10 15.5a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                  </svg>
                  Označi kao isporučenu
                </button>
                <a :href="`${BASE}/api/nabavka/purchase-orders/${po.id}/pdf`" target="_blank"
                   class="px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors flex items-center gap-1.5">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                    <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                  </svg>
                  PDF
                </a>
              </div>
              <!-- Show supplier info if order was sent by email -->
              <div v-if="po.supplier_email" class="mt-2 flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500">
                <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                  <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                </svg>
                Poslano na: <span class="text-zinc-600 dark:text-zinc-300">{{ po.supplier_name ? po.supplier_name + ' (' + po.supplier_email + ')' : po.supplier_email }}</span>
              </div>
            </template>

            <!-- Isporučena -->
            <template v-else>
              <div class="flex gap-2">
                <div class="flex-1 flex items-center justify-center gap-2 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4 text-emerald-600 dark:text-emerald-400">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Isporučeno</span>
                </div>
                <a :href="`${BASE}/api/nabavka/purchase-orders/${po.id}/pdf`" target="_blank"
                   class="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors flex items-center gap-1.5">
                  <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                    <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                  </svg>
                  PDF
                </a>
              </div>
              <div v-if="po.supplier_email" class="mt-2 flex items-center gap-1.5 text-xs text-zinc-400 dark:text-zinc-500">
                <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                  <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                </svg>
                Poslano na: <span class="text-zinc-600 dark:text-zinc-300">{{ po.supplier_name ? po.supplier_name + ' (' + po.supplier_email + ')' : po.supplier_email }}</span>
              </div>
            </template>

          </div>
        </div>
      </div>
    </template>

    <!-- ===== Create Purchase Order Modal ===== -->
    <Teleport to="body">
      <div v-if="showModal"
           class="fixed inset-0 z-50 flex items-start justify-center p-4 sm:p-8 overflow-y-auto"
           @click.self="closeModal">
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 shadow-2xl my-auto">

          <!-- Modal header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
            <h2 class="text-base font-bold text-zinc-900 dark:text-white">Nova narudžbenica</h2>
            <button @click="closeModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
              <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
              </svg>
            </button>
          </div>

          <div class="px-6 py-5 space-y-6">

            <!-- Work order filter (if multiple work orders) -->
            <div v-if="workOrders.length > 1">
              <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">Filtriraj po nalogu</label>
              <div class="flex flex-wrap gap-2">
                <button @click="modalWoFilter = null"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        :class="modalWoFilter === null
                          ? 'bg-blue-600 text-white'
                          : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                  Svi nalozi
                </button>
                <button v-for="wo in workOrders" :key="wo.id"
                        @click="modalWoFilter = wo.id"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        :class="modalWoFilter === wo.id
                          ? 'bg-blue-600 text-white'
                          : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'">
                  {{ wo.name }}
                </button>
              </div>
            </div>

            <!-- Items selection -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Stavke</label>
                <button @click="selectAllPendingItems" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                  Odaberi sve nenaručene
                </button>
              </div>

              <div v-if="modalFilteredItems.length" class="space-y-2 max-h-64 overflow-y-auto pr-1">
                <label v-for="item in modalFilteredItems" :key="item.id"
                       class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer transition-colors"
                       :class="selectedItems[item.id]
                         ? 'border-blue-400 dark:border-blue-600 bg-blue-50 dark:bg-blue-900/20'
                         : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'">
                  <input type="checkbox" v-model="selectedItems[item.id]" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wide"
                            :class="item.resource_type === 'equipment'
                              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                              : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'">
                        {{ item.resource_type === 'equipment' ? 'Oprema' : 'Materijal' }}
                      </span>
                      <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ item.resource_name }}</span>
                      <span class="text-xs text-zinc-400">· nalog {{ item._wo_name }}</span>
                    </div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                      Ukupno: <strong class="text-zinc-700 dark:text-zinc-300">{{ item.quantity }}{{ item.unit ? ' ' + item.unit : '' }}</strong>
                      · Naručeno: <strong class="text-zinc-700 dark:text-zinc-300">{{ item.ordered_qty }}{{ item.unit ? ' ' + item.unit : '' }}</strong>
                      · Preostalo: <strong :class="itemRemaining(item) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
                        {{ itemRemaining(item) }}{{ item.unit ? ' ' + item.unit : '' }}
                      </strong>
                    </div>
                  </div>
                  <!-- Quantity input when selected -->
                  <div v-if="selectedItems[item.id]" class="shrink-0 flex items-center gap-1" @click.stop>
                    <input type="number"
                           v-model.number="itemQty[item.id]"
                           :min="0.01"
                           :max="item.quantity"
                           step="0.01"
                           class="w-20 px-2 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 text-right focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <span class="text-xs text-zinc-400">{{ item.unit ?? '' }}</span>
                  </div>
                </label>
              </div>
              <p v-else class="text-sm text-zinc-400 italic py-3 text-center">
                {{ modalWoFilter ? 'Nema stavki za ovaj nalog.' : 'Nema dostupnih stavki.' }}
              </p>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">Napomena</label>
              <textarea v-model="modalNotes" rows="2"
                        placeholder="Napomena za narudžbenicu (opcionalno)..."
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>

          </div>

          <!-- Modal footer -->
          <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
            <span class="text-sm text-zinc-500">
              {{ selectedItemsCount }} stavki odabrano
            </span>
            <div class="flex gap-2">
              <button @click="closeModal"
                      class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-medium text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                Odustani
              </button>
              <button @click="submitCreatePO" :disabled="creatingPO || selectedItemsCount === 0"
                      class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors disabled:opacity-50">
                {{ creatingPO ? 'Kreiranje...' : 'Kreiraj narudžbenicu' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- Backdrop -->
      <div v-if="showModal" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { BASE } from '../../utils/base'

// ─── State ────────────────────────────────────────────────────────────────────
const loading       = ref(true)
const workOrders    = ref([])
const orders        = ref([])

const activeMainTab   = ref('nalozi')
const activeStatusTab = ref('kreirana')

const actioning = reactive({})   // value: 'order' | 'send' | 'deliver' | false
const busy      = reactive({})
const notes     = reactive({})
const supplierData = reactive({}) // keyed by po.id: { name, email }

// Modal state
const showModal      = ref(false)
const modalWoFilter  = ref(null)
const modalNotes     = ref('')
const selectedItems  = reactive({})
const itemQty        = reactive({})
const creatingPO     = ref(false)

// ─── Status tabs ──────────────────────────────────────────────────────────────
const statusTabs = [
  { key: 'kreirana',   label: 'Kreirana' },
  { key: 'narucena',   label: 'Naručena' },
  { key: 'isporucena', label: 'Isporučena' },
]

// ─── Computed ─────────────────────────────────────────────────────────────────
const countByStatus = computed(() => {
  const counts = { kreirana: 0, narucena: 0, isporucena: 0 }
  orders.value.forEach(o => { if (counts[o.status] !== undefined) counts[o.status]++ })
  return counts
})

const filteredOrders = computed(() =>
  orders.value.filter(o => o.status === activeStatusTab.value)
)

// All items from all work orders, flattened, with wo_name attached
const allModalItems = computed(() =>
  workOrders.value.flatMap(wo =>
    wo.items.map(i => ({ ...i, _wo_id: wo.id, _wo_name: wo.name }))
  )
)

const modalFilteredItems = computed(() =>
  modalWoFilter.value === null
    ? allModalItems.value
    : allModalItems.value.filter(i => i._wo_id === modalWoFilter.value)
)

const selectedItemsCount = computed(() =>
  Object.values(selectedItems).filter(Boolean).length
)

// ─── Helpers ──────────────────────────────────────────────────────────────────
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

function woAllOrdered(wo) {
  return wo.items.length > 0 && wo.items.every(i => i.ordered_qty >= i.quantity)
}
function woPendingCount(wo) {
  return wo.items.filter(i => i.ordered_qty < i.quantity).length
}
function itemRemaining(item) {
  return Math.max(0, item.quantity - item.ordered_qty)
}

// ─── Data loading ─────────────────────────────────────────────────────────────
async function loadAll() {
  loading.value = true
  try {
    const [woRes, poRes] = await Promise.all([
      fetch(`${BASE}/api/nabavka/work-orders`, { headers: { Accept: 'application/json' } }),
      fetch(`${BASE}/api/nabavka/purchase-orders`, { headers: { Accept: 'application/json' } }),
    ])
    const [woData, poData] = await Promise.all([woRes.json(), poRes.json()])
    workOrders.value = woData.work_orders ?? []
    orders.value     = poData.orders ?? []
  } finally {
    loading.value = false
  }
}

// ─── PO status actions ────────────────────────────────────────────────────────
function startAction(id, type) { actioning[id] = type; notes[id] = '' }
function startSendAction(id) {
  actioning[id] = 'send'
  notes[id] = ''
  supplierData[id] = { name: '', email: '' }
}
function cancelAction(id) { actioning[id] = false; notes[id] = ''; delete supplierData[id] }

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

async function confirmSendToSupplier(po) {
  busy[po.id] = true
  try {
    const sd = supplierData[po.id] ?? {}
    const res = await fetch(`${BASE}/api/nabavka/purchase-orders/${po.id}/send-to-supplier`, {
      method: 'POST', headers: hdrs(),
      body: JSON.stringify({
        supplier_name:  sd.name  || null,
        supplier_email: sd.email,
        notes: notes[po.id] || null,
      }),
    })
    const data = await res.json()
    if (res.ok) {
      const idx = orders.value.findIndex(o => o.id === po.id)
      if (idx !== -1) orders.value[idx] = data.order
      cancelAction(po.id)
      activeStatusTab.value = 'narucena'
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
      // Refresh work orders to update ordered_qty counts
      const woRes  = await fetch(`${BASE}/api/nabavka/work-orders`, { headers: { Accept: 'application/json' } })
      const woData = await woRes.json()
      workOrders.value = woData.work_orders ?? []
    }
  } finally { busy[po.id] = false }
}

// ─── Create PO modal ──────────────────────────────────────────────────────────
function openCreateModal() {
  modalWoFilter.value = null
  resetModalSelection()
  showModal.value = true
}

function openCreateModalFor(wo) {
  modalWoFilter.value = workOrders.value.length > 1 ? wo.id : null
  resetModalSelection()
  // Pre-select unordered items for this work order
  wo.items.forEach(item => {
    const remaining = itemRemaining(item)
    if (remaining > 0) {
      selectedItems[item.id] = true
      itemQty[item.id] = remaining
    }
  })
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  resetModalSelection()
}

function resetModalSelection() {
  modalNotes.value = ''
  Object.keys(selectedItems).forEach(k => { selectedItems[k] = false })
  Object.keys(itemQty).forEach(k => { delete itemQty[k] })
}

function selectAllPendingItems() {
  modalFilteredItems.value.forEach(item => {
    const remaining = itemRemaining(item)
    if (remaining > 0) {
      selectedItems[item.id] = true
      if (!itemQty[item.id]) itemQty[item.id] = remaining
    }
  })
}

async function submitCreatePO() {
  const items = allModalItems.value
    .filter(i => selectedItems[i.id])
    .map(i => ({
      work_order_item_id: i.id,
      quantity: itemQty[i.id] ?? i.quantity,
    }))

  if (!items.length) return

  creatingPO.value = true
  try {
    const res  = await fetch(`${BASE}/api/nabavka/purchase-orders`, {
      method: 'POST', headers: hdrs(),
      body: JSON.stringify({ notes: modalNotes.value || null, items }),
    })
    const data = await res.json()
    if (res.ok) {
      orders.value.unshift(data.order)
      closeModal()
      // Refresh work orders to update ordered_qty
      const woRes  = await fetch(`${BASE}/api/nabavka/work-orders`, { headers: { Accept: 'application/json' } })
      const woData = await woRes.json()
      workOrders.value = woData.work_orders ?? []
      // Switch to narudžbenice tab to show the new PO
      activeMainTab.value   = 'narudzbenice'
      activeStatusTab.value = 'kreirana'
    }
  } finally { creatingPO.value = false }
}

// ─── Init ─────────────────────────────────────────────────────────────────────
onMounted(() => {
  loadAll()
  document.addEventListener('livewire:navigated', loadAll)
})
</script>

