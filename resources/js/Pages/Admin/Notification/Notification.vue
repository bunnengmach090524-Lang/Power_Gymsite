<script setup>
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'
import { useLang } from '../../../composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  notifications: Object,
  unreadCount: Number,
  types: Array,
  filters: Object,
})

const { t, lang } = useLang()

const statusOptions = [
  { v: null, l: lang.value === 'km' ? 'ទាំងអស់' : 'All' },
  { v: 'unread', l: lang.value === 'km' ? 'មិនទាន់អាន' : 'Unread' },
  { v: 'read', l: lang.value === 'km' ? 'បានអាន' : 'Read' },
]

const typeLabels = {
  new_inquiry: { km: 'អ្នកចាប់អារម្មណ៍ថ្មី', en: 'New Inquiry' },
  payment_received: { km: 'ការទូទាត់ថ្មី', en: 'Payment Received' },
  membership_expiring: { km: 'សមាជិកភាពជិតផុតកំណត់', en: 'Membership Expiring' },
  team_invite_accepted: { km: 'សមាជិកក្រុមចូលរួម', en: 'Team Invite Accepted' },
}

function typeLabel(type) {
  const entry = typeLabels[type]
  if (!entry) return type // fallback បើមាន type ថ្មីមិនទាន់បន្ថែម mapping
  return lang.value === 'km' ? entry.km : entry.en
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000
  if (diff < 60) return lang.value === 'km' ? 'ទើបតែម្តងនេះ' : 'just now'
  if (diff < 3600) return `${Math.floor(diff / 60)} ${lang.value === 'km' ? 'នាទីមុន' : 'min ago'}`
  if (diff < 86400) return `${Math.floor(diff / 3600)} ${lang.value === 'km' ? 'ម៉ោងមុន' : 'hr ago'}`
  return `${Math.floor(diff / 86400)} ${lang.value === 'km' ? 'ថ្ងៃមុន' : 'days ago'}`
}

function markRead(n) {
  router.patch(`/dashboard/notifications/${n.id}/read`, {}, { preserveScroll: true })
}

function markAllRead() {
  if (!props.unreadCount) return
  router.patch('/dashboard/notifications/read-all', {}, { preserveScroll: true })
}

function destroy(n) {
  router.delete(`/dashboard/notifications/${n.id}`, { preserveScroll: true })
}

function updateFilter(key, value) {
  router.get('/dashboard/notifications', {
    ...(props.filters.status ? { status: props.filters.status } : {}),
    ...(props.filters.type ? { type: props.filters.type } : {}),
    [key]: value || undefined,
  }, { preserveState: true, preserveScroll: true })
}

// Laravel paginate() links() ដាក់ "Previous"/"Next" ជា link ដំបូង/ចុងក្រោយជានិច្ច
// យក link ដែលជាលេខទំព័រសុទ្ធសាធ (មិនមែន prev/next arrow) មកបង្ហាញជា numbered box
function isNumberLink(link) {
  return !/Previous|Next|&laquo;|&raquo;/.test(link.label)
}

function prevLink() {
  return props.notifications.links?.find(l => /Previous|&laquo;/.test(l.label))
}

function nextLink() {
  return props.notifications.links?.find(l => /Next|&raquo;/.test(l.label))
}
</script>

<template>
  <div class="w-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
        </span>
        {{ lang === 'km' ? 'ការជូនដំណឹង' : 'Notifications' }}
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-75" enter-to-class="opacity-100 scale-100">
          <span
            v-if="unreadCount"
            class="bg-red-500 text-white text-xs font-semibold rounded-full min-w-[22px] h-[22px] flex items-center justify-center px-1.5"
          >{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </Transition>
      </h1>

      <button
        @click="markAllRead"
        :disabled="!unreadCount"
        class="flex items-center gap-1.5 text-sm font-medium rounded-lg px-4 py-2.5 transition-all duration-200"
        :class="unreadCount
          ? 'bg-emerald-500 hover:bg-emerald-400 active:scale-95 text-slate-950 hover:shadow-lg hover:shadow-emerald-500/20 hover:-translate-y-0.5 cursor-pointer'
          : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 cursor-not-allowed'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{ lang === 'km' ? 'សម្គាល់ថាបានអានទាំងអស់' : 'Mark all as read' }}
      </button>
    </div>

    <!-- Filter card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl px-5 py-4 mb-4 shadow-sm">
      <div class="flex items-center gap-6 flex-wrap">
        <!-- Status filter -->
        <div class="flex items-center gap-3">
          <span class="flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            {{ lang === 'km' ? 'ក្រងតាមស្ថានភាព' : 'Filter by status' }}:
          </span>
          <div class="relative">
            <select
              :value="filters.status ?? ''"
              @change="updateFilter('status', $event.target.value)"
              class="appearance-none text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg pl-3 pr-9 py-2 min-w-[140px] cursor-pointer transition-colors duration-200 hover:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-400"
            >
              <option v-for="opt in statusOptions" :key="opt.v ?? 'all'" :value="opt.v ?? ''">{{ opt.l }}</option>
            </select>
            <svg class="w-3.5 h-3.5 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
        </div>

        <!-- Type/category filter -->
        <div class="flex items-center gap-3">
          <span class="flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 9V4a1 1 0 011-1z"/></svg>
            {{ lang === 'km' ? 'ក្រងតាមប្រភេទ' : 'Filter by type' }}:
          </span>
          <div class="relative">
            <select
              :value="filters.type ?? ''"
              @change="updateFilter('type', $event.target.value)"
              class="appearance-none text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg pl-3 pr-9 py-2 min-w-[140px] cursor-pointer transition-colors duration-200 hover:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 focus:border-emerald-400"
            >
              <option value="">{{ lang === 'km' ? 'ទាំងអស់' : 'All' }}</option>
              <option v-for="ty in types" :key="ty" :value="ty">{{ typeLabel(ty) }}</option>
            </select>
            <svg class="w-3.5 h-3.5 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- List -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800 shadow-sm">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        leave-active-class="transition duration-200 ease-in absolute w-full"
        leave-to-class="opacity-0 scale-95"
        move-class="transition-transform duration-300"
      >
        <div
          v-for="n in notifications.data"
          :key="n.id"
          class="flex items-start gap-3 px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors duration-200 group"
        >
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 mt-0.5 transition-colors duration-200"
            :class="n.read_at
              ? 'bg-slate-100 dark:bg-slate-800 text-slate-400'
              : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500'"
          >
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <Link
                v-if="n.link"
                :href="n.link"
                class="text-sm font-medium text-slate-900 dark:text-white hover:text-emerald-500 transition-colors duration-150"
              >{{ n.title }}</Link>
              <span v-else class="text-sm font-medium text-slate-900 dark:text-white">{{ n.title }}</span>
              <span v-if="!n.read_at" class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0 animate-pulse"></span>
              <span class="text-[10px] tracking-wide text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ typeLabel(n.type) }}</span>
            </div>
            <p v-if="n.message" class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">{{ n.message }}</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ timeAgo(n.created_at) }}</p>
          </div>

          <div class="flex items-center gap-1 shrink-0 opacity-60 group-hover:opacity-100 transition-opacity duration-200">
            <button
              v-if="!n.read_at"
              @click="markRead(n)"
              title="Mark as read"
              class="p-2 rounded-lg text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-all duration-150 hover:scale-110"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
            <button
              @click="destroy(n)"
              title="Delete"
              class="p-2 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all duration-150 hover:scale-110"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M5 7h14"/></svg>
            </button>
          </div>
        </div>
      </TransitionGroup>

      <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0">
        <div v-if="!notifications.data.length" class="py-20 flex flex-col items-center gap-3 text-center">
          <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <p class="text-slate-400 dark:text-slate-500 text-sm">{{ lang === 'km' ? 'មិនទាន់មានការជូនដំណឹងនៅឡើយ' : 'No notifications yet' }}</p>
        </div>
      </Transition>
    </div>

    <!-- Pagination (EventPlace-style numbered boxes) -->
    <div v-if="notifications.links?.length > 3" class="flex justify-center items-center gap-2 mt-6">
      <!-- Prev arrow -->
      <Link
        :href="prevLink()?.url ?? ''"
        class="w-9 h-9 flex items-center justify-center rounded-lg border transition-all duration-150"
        :class="prevLink()?.url
          ? 'border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-emerald-400 hover:text-emerald-500 hover:-translate-y-0.5'
          : 'border-slate-100 dark:border-slate-800 text-slate-300 dark:text-slate-700 pointer-events-none'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      </Link>

      <!-- Page numbers -->
      <template v-for="link in notifications.links" :key="link.label">
        <Link
          v-if="isNumberLink(link)"
          :href="link.url ?? ''"
          class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all duration-150"
          :class="link.active
            ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20'
            : link.url
              ? 'border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-emerald-400 hover:text-emerald-500 hover:-translate-y-0.5'
              : 'text-slate-300 dark:text-slate-700 pointer-events-none'"
          v-text="link.label"
        />
      </template>

      <!-- Next arrow -->
      <Link
        :href="nextLink()?.url ?? ''"
        class="w-9 h-9 flex items-center justify-center rounded-lg border transition-all duration-150"
        :class="nextLink()?.url
          ? 'border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-emerald-400 hover:text-emerald-500 hover:-translate-y-0.5'
          : 'border-slate-100 dark:border-slate-800 text-slate-300 dark:text-slate-700 pointer-events-none'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      </Link>
    </div>
  </div>
</template>