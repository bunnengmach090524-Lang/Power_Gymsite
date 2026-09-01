<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import { useLang } from '@/composables/useLang'
import { useTheme } from '@/composables/useTheme'
import { useSidebar } from '@/composables/useSidebar'

const page = usePage()
const { t, toggleLang, lang } = useLang()
const { isDark, toggleTheme } = useTheme()
const { collapsed, toggleSidebar } = useSidebar()

const user = computed(() => page.props.auth?.user ?? { name: 'Admin' })
const tenantBranding = computed(() => page.props.tenantBranding ?? { name: 'GymSite', logoUrl: null, publicUrl: null })
const userMenuOpen = ref(false)
const notifMenuOpen = ref(false)
const mobileOpen = ref(false)
const userMenuRef = ref(null)
const notifMenuRef = ref(null)

const notifications = computed(() => page.props.notifications ?? [])
const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0)

const todayOpen = ref(true)
const upcomingOpen = ref(true)
const todayStats = computed(() => page.props.todayStats ?? { newMembers: 0, revenue: 0 })
const upcomingExpiring = computed(() => page.props.upcomingExpiring ?? [])

const todayClasses = computed(() => page.props.todayClasses ?? [])
const classesOpen = ref(true)

// Quick search
const searchQuery = ref('')
const searchResults = ref([])
const searchOpen = ref(false)
const searchLoading = ref(false)
let searchTimer = null

function onSearchInput() {
  clearTimeout(searchTimer)
  if (searchQuery.value.trim().length < 2) {
    searchResults.value = []
    searchOpen.value = false
    return
  }
  searchTimer = setTimeout(async () => {
    searchLoading.value = true
    try {
      const res = await fetch(`/dashboard/search?q=${encodeURIComponent(searchQuery.value)}`)
      searchResults.value = await res.json()
      searchOpen.value = true
    } finally {
      searchLoading.value = false
    }
  }, 300)
}

function goToMember(id) {
  searchOpen.value = false
  searchQuery.value = ''
  router.visit(`/dashboard/members/${id}`)
}

// Subscription badge
const subscriptionBadge = computed(() => {
  const status = tenantBranding.value.subscriptionStatus
  const map = {
    active: { label: lang.value === 'km' ? 'សកម្ម' : 'Active', class: 'bg-emerald-500/20 text-emerald-400' },
    trial: { label: lang.value === 'km' ? 'សាកល្បង' : 'Trial', class: 'bg-amber-500/20 text-amber-400' },
    suspended: { label: lang.value === 'km' ? 'បានផ្អាក' : 'Suspended', class: 'bg-red-500/20 text-red-400' },
  }
  return map[status] ?? null
})



function timeAgo(dateStr) {
  const diffMs = Date.now() - new Date(dateStr).getTime()
  const diffMin = Math.round(diffMs / 60000)
  const rtf = new Intl.RelativeTimeFormat(lang.value === 'km' ? 'km' : 'en', { numeric: 'auto' })
  if (diffMin < 60) return rtf.format(-diffMin, 'minute')
  const diffHr = Math.round(diffMin / 60)
  if (diffHr < 24) return rtf.format(-diffHr, 'hour')
  return rtf.format(-Math.round(diffHr / 24), 'day')
}

function markRead(notification) {
  router.patch(`/dashboard/notifications/${notification.id}/read`, {}, { preserveScroll: true })
}

function markAllRead() {
  router.patch('/dashboard/notifications/read-all', {}, { preserveScroll: true })
}

function deleteNotification(notification) {
  router.delete(`/dashboard/notifications/${notification.id}`, { preserveScroll: true })
}

watch(() => page.url, () => { mobileOpen.value = false })

function handleClickOutside(event) {
  if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
    userMenuOpen.value = false
  }
  if (notifMenuRef.value && !notifMenuRef.value.contains(event.target)) {
    notifMenuOpen.value = false
  }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const sidebarCounts = computed(() => page.props.sidebarCounts ?? {})

const navItems = computed(() => {
  const items = [
    { href: '/dashboard', key: 'admin_nav_dashboard', icon: 'M4 6h16M4 12h16M4 18h7' },
    { href: '/dashboard/members', key: 'admin_nav_members', icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3.13a4 4 0 00-8 0', badge: sidebarCounts.value.members },
    { href: '/dashboard/classes', key: 'admin_nav_classes', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', badge: sidebarCounts.value.classes },
    { href: '/dashboard/promotions', key: 'admin_nav_promotions', icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 9V4a1 1 0 011-1z', badge: sidebarCounts.value.promotions, adminOnly: true },
    { href: '/dashboard/plans', key: 'admin_nav_plans', label: lang.value === 'km' ? 'គម្រោងសមាជិកភាព' : 'Membership Plans', icon: 'M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', adminOnly: true },
    { href: '/dashboard/payments', key: 'admin_nav_payments', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 12h11M17 9l3 3-3 3', badge: sidebarCounts.value.payments, adminOnly: true },
    { href: '/dashboard/trainers', key: 'admin_nav_trainers', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', adminOnly: true },
    { href: '/dashboard/trainer-attendance', key: 'admin_nav_trainer_attendance', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { href: '/dashboard/trainer-attendance/scan', key: 'admin_nav_scan_qr', icon: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m0 0h.01M8 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4z' },
    { href: '/dashboard/check-in', key: 'admin_nav_checkin', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', badge: sidebarCounts.value.checkins },
    { href: '/dashboard/website-editor', key: 'admin_nav_website_editor', icon: 'M4 4h16v16H4V4zm4 4h8m-8 4h8m-8 4h4', adminOnly: true },
    { href: '/dashboard/notifications', key: 'admin_nav_notifications', icon: 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', badge: unreadCount.value || null },
    { href: '/dashboard/team', key: 'admin_nav_team', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', adminOnly: true },
    { href: '/dashboard/staff', key: 'admin_nav_staff', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m0-9a9 9 0 100 8', staffManagement: true },
    { href: '/dashboard/staff-attendance', key: 'admin_nav_staff_attendance', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', staffManagement: true },
    { href: '/dashboard/salary', key: 'admin_nav_salary', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m0-9a9 9 0 100 8', staffManagement: true },
    { href: '/dashboard/salary-report', key: 'admin_nav_salary_report', icon: 'M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', staffManagement: true },
    { href: '/dashboard/reports', key: 'admin_nav_reports', icon: 'M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', adminOnly: true },
  ]

  return items.filter(item => {
    if (item.adminOnly) return user.value.role === 'gym_admin'
    if (item.staffManagement) return ['gym_admin', 'manager'].includes(user.value.role)
    return true
  })
})

function isActive(href) {
  const path = page.url.split('?')[0]
  return href === '/dashboard' ? path === '/dashboard' : path.startsWith(href)
}

function logout() {
  router.post('/logout')
}

const today = computed(() =>
  new Date().toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
)
</script>

<template>
  <div class="min-h-screen flex bg-slate-50 dark:bg-slate-950 transition-colors duration-300">

    <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="mobileOpen" @click="mobileOpen = false" class="fixed inset-0 bg-slate-950/60 z-40 md:hidden"></div>
    </Transition>

    <!-- SIDEBAR -->
    <aside
      class="fixed md:sticky top-0 left-0 h-screen flex flex-col border-r border-slate-800 bg-slate-900 z-50 transition-all duration-300 ease-in-out"
      :class="[
        collapsed ? 'md:w-[76px]' : 'md:w-64',
        mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        'w-64',
      ]"
    >
      <div class="flex items-center gap-2.5 px-4 h-16 border-b border-slate-800 shrink-0">
        <img
          v-if="tenantBranding.logoUrl"
          :src="tenantBranding.logoUrl"
          class="w-9 h-9 rounded-lg object-cover shrink-0 shadow-lg shadow-emerald-500/20 transition-transform duration-200"
        />
        <div
          v-else
          class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-bold shrink-0 text-lg shadow-lg shadow-emerald-500/20"
        >{{ tenantBranding.name?.[0]?.toUpperCase() ?? 'G' }}</div>
        <div class="min-w-0 overflow-hidden transition-all duration-300" :class="collapsed ? 'md:w-0 md:opacity-0' : 'w-auto opacity-100'">
          <p class="font-semibold text-lg text-white truncate whitespace-nowrap">{{ tenantBranding.name }}</p>
          <span
            v-if="subscriptionBadge && !collapsed"
            class="text-[10px] font-medium px-1.5 py-0.5 rounded inline-block mt-0.5"
            :class="subscriptionBadge.class"
          >{{ subscriptionBadge.label }}</span>
        </div>
        <button @click="mobileOpen = false" class="ml-auto md:hidden text-slate-400 p-1">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Today stats (collapsible) -->
      <div v-if="!collapsed" class="px-2.5 pt-3 shrink-0">
        <button
          @click="todayOpen = !todayOpen"
          class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800/70 transition-colors duration-200"
        >
          <span class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            {{ lang === 'km' ? 'ថ្ងៃនេះ' : 'Today' }}
          </span>
          <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="todayOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition-all duration-150 ease-in" leave-to-class="opacity-0">
          <div v-if="todayOpen" class="mt-1 mx-1 bg-slate-800/50 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-3 py-2.5 border-b border-slate-700/50">
              <span class="flex items-center gap-2 text-xs text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3.13a4 4 0 00-8 0"/></svg>
                {{ lang === 'km' ? 'សមាជិកថ្មី' : 'New members' }}
              </span>
              <span class="text-sm font-semibold text-white">{{ todayStats.newMembers }}</span>
            </div>
            <div class="flex items-center justify-between px-3 py-2.5">
              <span class="flex items-center gap-2 text-xs text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 12h11M17 9l3 3-3 3"/></svg>
                {{ lang === 'km' ? 'ចំណូល' : 'Revenue' }}
              </span>
              <span class="text-sm font-semibold text-emerald-400">${{ todayStats.revenue.toFixed(2) }}</span>
            </div>
          </div>
        </Transition>
      </div>

      <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 px-2.5 space-y-1">
        <Link
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-3 px-3 py-3 rounded-lg text-base transition-all duration-200 relative group"
          :class="isActive(item.href)
            ? 'bg-emerald-500/15 text-emerald-400 font-medium'
            : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100 hover:translate-x-0.5'"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
          </svg>
          <span class="truncate whitespace-nowrap transition-all duration-200 flex-1" :class="collapsed ? 'md:w-0 md:opacity-0' : 'opacity-100'">
            {{ item.label ?? t[item.key] }}
          </span>
          <span
            v-if="item.badge && !collapsed"
            class="text-xs font-semibold px-2 py-0.5 rounded-full transition-colors duration-200"
            :class="isActive(item.href) ? 'bg-emerald-400 text-slate-900' : 'bg-slate-800 text-slate-300 group-hover:bg-slate-700'"
          >
            {{ item.badge }}
          </span>
          <span
            v-if="collapsed"
            class="hidden md:block absolute left-full ml-2 px-2.5 py-1.5 rounded-md bg-slate-800 text-white text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 shadow-lg z-50"
          >
            {{ t[item.key] }}
          </span>
        </Link>

        <a 
          v-if="tenantBranding.publicUrl"
          :href="tenantBranding.publicUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 px-3 py-3 rounded-lg text-base transition-all duration-200 relative group text-slate-400 hover:bg-slate-800/70 hover:text-slate-100 hover:translate-x-0.5"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M12 3a15.3 15.3 0 010 18M12 3a15.3 15.3 0 000 18" />
          </svg>
          <span class="truncate whitespace-nowrap transition-all duration-200 flex-1" :class="collapsed ? 'md:w-0 md:opacity-0' : 'opacity-100'">
            {{ lang === 'km' ? 'មើលគេហទំព័រ' : 'View Website' }}
          </span>
          <svg class="w-3.5 h-3.5 shrink-0" :class="collapsed ? 'md:w-0 md:opacity-0' : 'opacity-60'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          <span
            v-if="collapsed"
            class="hidden md:block absolute left-full ml-2 px-2.5 py-1.5 rounded-md bg-slate-800 text-white text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 shadow-lg z-50"
          >
            {{ lang === 'km' ? 'មើលគេហទំព័រ' : 'View Website' }}
          </span>
        </a>
      </nav>

      <!-- Upcoming expiring memberships (collapsible) -->
      <div v-if="!collapsed" class="px-2.5 pb-2 shrink-0">
        <button
          @click="upcomingOpen = !upcomingOpen"
          class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800/70 transition-colors duration-200"
        >
          <span class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ lang === 'km' ? 'ជិតផុតកំណត់' : 'Expiring soon' }}
            <span v-if="upcomingExpiring.length" class="text-xs bg-amber-500/20 text-amber-400 px-1.5 py-0.5 rounded-full">{{ upcomingExpiring.length }}</span>
          </span>
          <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="upcomingOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition-all duration-150 ease-in" leave-to-class="opacity-0">
          <div v-if="upcomingOpen" class="mt-1 mx-1 bg-slate-800/50 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
            <Link
              v-for="sub in upcomingExpiring"
              :key="sub.id"
              :href="`/dashboard/members/${sub.memberId}`"
              class="block px-3 py-2.5 border-b border-slate-700/50 last:border-0 hover:bg-slate-700/40 transition-colors duration-150"
            >
              <p class="text-xs font-medium text-white truncate">{{ sub.memberName }}</p>
              <p class="text-[11px] mt-0.5" :class="sub.daysLeft <= 1 ? 'text-red-400' : 'text-amber-400'">
                {{ sub.daysLeft <= 0
                  ? (lang === 'km' ? 'ផុតកំណត់ថ្ងៃនេះ' : 'Expires today')
                  : (lang === 'km' ? `ផុតកំណត់ក្នុង ${sub.daysLeft} ថ្ងៃ` : `Expires in ${sub.daysLeft}d`) }}
              </p>
            </Link>
            <p v-if="!upcomingExpiring.length" class="px-3 py-4 text-xs text-slate-500 text-center">
              {{ lang === 'km' ? 'គ្មានទិន្នន័យ' : 'No data' }}
            </p>
          </div>
        </Transition>
      </div>

      <div class="border-t border-slate-800 p-2.5 shrink-0">
        <button
          @click="toggleSidebar"
          class="hidden md:flex w-full items-center gap-3 px-3 py-2.5 rounded-lg text-base text-slate-400 hover:bg-slate-800/70 hover:text-slate-100 transition-colors duration-200"
        >
          <svg class="w-5 h-5 shrink-0 transition-transform duration-300" :class="collapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
          </svg>
          <span class="transition-all duration-200 whitespace-nowrap" :class="collapsed ? 'md:w-0 md:opacity-0' : 'opacity-100'">{{ t.sidebar_collapse }}</span>
        </button>
        <button
          @click="logout"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-base text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-colors duration-200"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span class="transition-all duration-200 whitespace-nowrap" :class="collapsed ? 'md:w-0 md:opacity-0' : 'opacity-100'">{{ t.admin_logout }}</span>
        </button>

        <Link
          href="/dashboard/profile"
          class="mt-2 pt-2.5 border-t border-slate-800 flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-800/70 transition-colors duration-200"
        >
          <img
            v-if="user.avatar"
            :src="user.avatar"
            class="w-9 h-9 rounded-full object-cover shrink-0 ring-2 ring-slate-700"
          />
          <div
            v-else
            class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-sm font-semibold shrink-0"
          >
            {{ user.name?.[0]?.toUpperCase() ?? 'A' }}
          </div>
          <div class="min-w-0 overflow-hidden transition-all duration-300" :class="collapsed ? 'md:w-0 md:opacity-0' : 'w-auto opacity-100'">
            <p class="text-sm font-medium text-white truncate whitespace-nowrap">{{ user.name }}</p>
            <p class="text-xs text-slate-500 truncate whitespace-nowrap">{{ user.role === 'gym_admin' ? 'Administrator' : (user.role ?? 'Staff') }}</p>
          </div>
        </Link>
      </div>
      
    </aside>

    <div class="flex-1 min-w-0 flex flex-col">
      <header class="h-16 sticky top-0 z-30 bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 sm:px-6 transition-colors duration-300">
        <div class="flex items-center gap-3 min-w-0">
          <button @click="mobileOpen = true" class="md:hidden text-slate-500 dark:text-slate-400 p-1.5 -ml-1.5 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 truncate">{{ today }}</p>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
          <span class="hidden lg:flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1.5 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ t.system_online }}
          </span>

          <button
            @click="toggleTheme"
            class="p-2.5 rounded-full border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-emerald-400 hover:text-emerald-500 transition-all duration-200 hover:scale-105"
          >
            <svg v-if="isDark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36 6.36l-.7-.7M6.34 6.34l-.7-.7m12.02 0l-.7.7M6.34 17.66l-.7.7M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          </button>

          <div class="relative" ref="notifMenuRef">
            <button
              @click="notifMenuOpen = !notifMenuOpen"
              class="relative p-2.5 rounded-full border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-emerald-400 hover:text-emerald-500 transition-all duration-200 hover:scale-105"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
              <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center"
              >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
            </button>
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
              <div
                v-if="notifMenuOpen"
                class="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl py-2 z-50"
              >
                <div class="flex items-center justify-between px-3.5 py-1.5">
                  <p class="text-base text-slate-700 dark:text-slate-200 font-medium">{{ t.notifications_title }}</p>
                  <button
                    v-if="unreadCount > 0"
                    @click="markAllRead"
                    class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline"
                  >{{ t.notifications_mark_all_read ?? 'Mark all read' }}</button>
                </div>
                <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                <p v-if="!notifications.length" class="px-3.5 py-4 text-sm text-slate-500 dark:text-slate-400 text-center">{{ t.notifications_empty }}</p>
                <div
                  v-for="n in notifications"
                  :key="n.id"
                  class="flex items-start gap-2 px-3.5 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors duration-150 cursor-pointer"
                  :class="!n.read_at ? 'bg-emerald-50/50 dark:bg-emerald-500/5' : ''"
                  @click="markRead(n)"
                >
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ n.title }}</p>
                    <p v-if="n.message" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">{{ n.message }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ timeAgo(n.created_at) }}</p>
                  </div>
                  <button @click.stop="deleteNotification(n)" class="text-slate-300 dark:text-slate-600 hover:text-red-500 shrink-0 p-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <button
            @click="toggleLang"
            class="flex items-center gap-1.5 text-sm font-medium px-3 py-2.5 rounded-full border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-emerald-400 hover:text-emerald-500 transition-all duration-200 hover:scale-105"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2c0 4.418-2.239 8-5 8m6-4c0 2 1 4 4 4M13 21l4-8 4 8M14 18h6"/></svg>
            <span>{{ lang === 'km' ? 'ខ្មែរ' : 'EN' }}</span>
          </button>

          <div class="relative" ref="userMenuRef">
            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 pl-1">
              <img
                v-if="user.avatar"
                :src="user.avatar"
                class="w-9 h-9 rounded-full object-cover hover:ring-2 hover:ring-emerald-400 transition-all duration-200"
              />
              <div
                v-else
                class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center text-white text-sm font-semibold hover:ring-2 hover:ring-emerald-400 transition-all duration-200"
              >
                {{ user.name?.[0]?.toUpperCase() ?? 'A' }}
              </div>
            </button>
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
              <div
                v-if="userMenuOpen"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl py-1.5 z-50"
              >
                <p class="px-3 py-2 text-base text-slate-700 dark:text-slate-200 font-medium truncate">{{ user.name }}</p>
                <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                <Link
                  href="/dashboard/profile"
                  class="block px-3 py-2 text-base text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors duration-150"
                >
                  {{ t.admin_nav_profile }}
                </Link>
                <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                <button @click="logout" class="w-full text-left px-3 py-2 text-base text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-150">
                  {{ t.admin_logout }}
                </button>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <main class="flex-1 min-w-0 overflow-x-hidden p-6 sm:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>