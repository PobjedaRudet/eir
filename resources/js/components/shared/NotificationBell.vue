<template>
  <div class="relative" ref="bellRef">
    <!-- Bell button -->
    <button @click="toggleOpen"
            class="relative flex items-center justify-center w-8 h-8 rounded-lg text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
            :title="unreadCount > 0 ? `${unreadCount} nepročitanih notifikacija` : 'Notifikacije'">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
        <path fill-rule="evenodd" d="M4 8a6 6 0 1 1 12 0v2.17c0 .91.18 1.81.53 2.65a.5.5 0 0 1-.46.68H3.93a.5.5 0 0 1-.46-.68C3.82 11.98 4 11.08 4 10.17V8Zm6 10a2 2 0 0 1-2-2h4a2 2 0 0 1-2 2Z" clip-rule="evenodd" />
      </svg>
      <!-- Unread badge -->
      <span v-if="unreadCount > 0"
            class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none">
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown rendered at body level to escape sidebar overflow:hidden -->
    <Teleport to="body">
      <div v-if="open"
           data-notification-dropdown
           :style="dropdownStyle"
           class="fixed w-80 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-xl z-[9999] overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
          <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Notifikacije</span>
          <button v-if="unreadCount > 0"
                  @click="markAllRead"
                  class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
            Označi sve kao pročitano
          </button>
        </div>

        <!-- List -->
      <div class="max-h-80 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
        <p v-if="!notifications.length" class="px-4 py-6 text-sm text-center text-zinc-400 italic">
          Nema notifikacija.
        </p>
        <div v-for="n in notifications" :key="n.id"
             @click="markRead(n)"
             class="flex gap-3 px-4 py-3 cursor-pointer transition-colors"
             :class="n.read_at ? 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' : 'bg-blue-50 dark:bg-blue-900/10 hover:bg-blue-100 dark:hover:bg-blue-900/20'">
          <!-- Icon -->
          <div class="shrink-0 mt-0.5">
            <span v-if="n.data.type === 'order_submitted' || n.data.type === 'project_submitted'"
                  class="flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                <path d="M2.5 3A1.5 1.5 0 0 0 1 4.5v.793c.026.009.051.02.076.032L7.674 8.51c.206.1.446.1.652 0l6.598-3.185A.755.755 0 0 1 15 5.293V4.5A1.5 1.5 0 0 0 13.5 3h-11Z" />
                <path d="M15 6.954 8.978 9.86a2.25 2.25 0 0 1-1.956 0L1 6.954V11.5A1.5 1.5 0 0 0 2.5 13h11a1.5 1.5 0 0 0 1.5-1.5V6.954Z" />
              </svg>
            </span>
            <span v-else-if="n.data.type === 'order_approved' || n.data.type === 'project_approved'"
                  class="flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
              </svg>
            </span>
            <span v-else
                  class="flex items-center justify-center w-7 h-7 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" />
              </svg>
            </span>
          </div>
          <!-- Text -->
          <div class="flex-1 min-w-0">
            <p class="text-xs text-zinc-700 dark:text-zinc-300 leading-snug">{{ n.data.message }}</p>
            <p class="mt-1 text-[11px] text-zinc-400">{{ relativeTime(n.created_at) }}</p>
          </div>
          <!-- Unread dot -->
          <div v-if="!n.read_at" class="shrink-0 mt-1.5 w-2 h-2 rounded-full bg-blue-500"></div>
        </div>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { BASE } from '../../utils/base'

const open          = ref(false)
const notifications = ref([])
const unreadCount   = ref(0)
const bellRef       = ref(null)
const dropdownPos   = ref({ top: 0, left: 0 })

const dropdownStyle = computed(() => ({
  top:  dropdownPos.value.top  + 'px',
  left: dropdownPos.value.left + 'px',
}))

let pollInterval = null

function hdrs() {
  return {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
  }
}

async function load() {
  try {
    const res  = await fetch(`${BASE}/api/notifications`, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    notifications.value = data.notifications ?? []
    unreadCount.value   = data.unread_count   ?? 0
  } catch { /* silent */ }
}

async function markRead(n) {
  if (n.read_at) return
  n.read_at = new Date().toISOString()
  notifications.value = notifications.value.filter(item => item.id !== n.id)
  unreadCount.value = Math.max(0, unreadCount.value - 1)
  await fetch(`${BASE}/api/notifications/${n.id}/read`, { method: 'POST', headers: hdrs() })
}

async function markAllRead() {
  notifications.value = []
  unreadCount.value = 0
  await fetch(`${BASE}/api/notifications/read-all`, { method: 'POST', headers: hdrs() })
}

function toggleOpen() {
  if (!open.value && bellRef.value) {
    const rect = bellRef.value.getBoundingClientRect()
    dropdownPos.value = {
      top:  rect.bottom + window.scrollY + 4,
      left: rect.left   + window.scrollX,
    }
  }
  open.value = !open.value
}

function relativeTime(iso) {
  const diff = Math.floor((Date.now() - new Date(iso)) / 1000)
  if (diff < 60)   return 'Upravo'
  if (diff < 3600) return `Prije ${Math.floor(diff / 60)} min`
  if (diff < 86400) return `Prije ${Math.floor(diff / 3600)} h`
  return `Prije ${Math.floor(diff / 86400)} dana`
}

function onClickOutside(e) {
  // Check both the bell wrapper and the teleported dropdown (which lives in body)
  const dropdown = document.querySelector('[data-notification-dropdown]')
  if (
    bellRef.value && !bellRef.value.contains(e.target) &&
    (!dropdown || !dropdown.contains(e.target))
  ) {
    open.value = false
  }
}

onMounted(() => {
  load()
  pollInterval = setInterval(load, 30000)
  document.addEventListener('click', onClickOutside)
  document.addEventListener('livewire:navigated', load)
})

onBeforeUnmount(() => {
  clearInterval(pollInterval)
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('livewire:navigated', load)
})
</script>
