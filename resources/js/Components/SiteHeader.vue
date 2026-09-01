<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import { useTheme } from '../composables/useTheme'
import { useLang } from '../composables/useLang'

const props = defineProps({
  tenant: Object,
  settings: Object,
  navLinks: { type: Array, default: () => [] },
  homeHref: { type: String, default: '/' },
  loginHref: { type: String, default: '/login' },
  registerHref: { type: String, default: '/register' },

  // ⚠️ NEW/FIX: separate href for the "Staff / Admin Login" menu item.
  // Points to the GLOBAL /login route (AuthenticatedSessionController::
  // create → Auth/Login.vue), distinct from `loginHref` above which is the
  // tenant-scoped MEMBER login page (GymSiteController::login →
  // Client/GymMemberLogin.vue). Previously "Staff/Admin Login" reused
  // `loginHref` by mistake, so it opened the member login page instead of
  // the admin one.
  staffLoginHref: { type: String, default: '/login' },

  // ⚠️ NEW: filled by StaffSelfServiceController only when the current
  // viewer is a trainer authenticated via the self-service magic link
  // (session('trainer_staff_id') only — no Laravel Auth::login(), so
  // page.props.auth.user is null for them). When present, treat the
  // viewer as staff-here even though `authUser` below is null. This is
  // ALWAYS null for normal Laravel-authenticated staff/admin — they are
  // already correctly detected via isStaffHere below.
  staffViewer: { type: Object, default: null },
})

const { theme, toggleTheme, isDark } = useTheme()
const { t, lang, toggleLang } = useLang()
const page = usePage()

const gymName = computed(() => props.tenant?.name ?? 'GymSite')
const logoUrl = computed(() => props.settings?.logo_image?.image_url ?? null)
const initials = computed(() => gymName.value.trim().charAt(0).toUpperCase() || 'G')

const authUser = computed(() => page.props.auth?.user ?? null)

// ⚠️ FIX: isMemberHere previously relied only on `member_tenant_slug`, which
// is computed server-side (HandleInertiaRequests) the same way the old
// redirectAfterLogin() bug worked — via `$user->member?->tenant?->slug`
// with no fallback. If that relation chain is ever null (Member record not
// yet linked, timing issue, etc.) an authenticated member gets rendered as
// a guest here: no "My Account"/"Logout", and instead sees "Login/Register"
// + "Staff/Admin Login" — which then bounces them into a 403 (guest
// middleware redirects an authenticated user away from /login into
// dashboard.overview, which their member role can't access).
//
// The real fix belongs server-side (HandleInertiaRequests should resolve
// member_tenant_slug with the same tenant_id-first fallback used in
// GoogleAuthController::redirectAfterLogin). This client-side check adds a
// second line of defense: treat the user as "a member here" purely based
// on role + tenant match, without requiring the (possibly-stale) explicit
// slug prop to be present.
const isMemberHere = computed(() => {
  if (authUser.value?.role !== 'member') return false
  if (!props.tenant?.slug) return false
  return authUser.value?.member_tenant_slug
    ? authUser.value.member_tenant_slug === props.tenant.slug
    : true
})

// A logged-in user whose role is staff/manager/gym_admin AND whose
// tenant_id matches this tenant's id is staff/admin AT THIS gym.
//
// ⚠️ FIX: also true when `staffViewer` is present (trainer authenticated
// via the self-service magic link, which never goes through Laravel Auth
// so `authUser` is null for them — see prop comment above).
const isStaffHere = computed(() => {
  if (props.staffViewer) return true

  if (!authUser.value) return false
  if (!['staff', 'manager', 'gym_admin'].includes(authUser.value.role)) return false
  if (!props.tenant?.id) return false
  return authUser.value.tenant_id === props.tenant.id
})

const isAuthenticatedElsewhere = computed(() => !!authUser.value && !isMemberHere.value && !isStaffHere.value)

// ⚠️ NEW: name/initial shown in the avatar needs a source even when
// authUser is null but staffViewer is present (trainer magic-link session).
const displayName = computed(() => authUser.value?.name ?? props.staffViewer?.name ?? null)

const accountHref = computed(() => `/gym/${props.tenant?.slug}/account`)

const userInitials = computed(() => displayName.value?.trim()?.charAt(0)?.toUpperCase() ?? '?')
const memberNotifications = computed(() => page.props.memberNotifications ?? [])
const unreadCount = computed(() => memberNotifications.value.filter(n => !n.read_at).length)
const memberTodayClasses = computed(() => page.props.memberTodayClasses ?? [])
const todayClassOpen = ref(false)

const menuOpen = ref(false)
const notifOpen = ref(false)
const headerRef = ref(null)

function onDocClick(e) {
  if (headerRef.value && !headerRef.value.contains(e.target)) {
    menuOpen.value = false
    notifOpen.value = false
    todayClassOpen.value = false
  }
}
onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))

function toggleMenu() {
  notifOpen.value = false
  menuOpen.value = !menuOpen.value
}
function toggleNotif() {
  menuOpen.value = false
  notifOpen.value = !notifOpen.value
}
function toggleTodayClass() {
  menuOpen.value = false
  notifOpen.value = false
  todayClassOpen.value = !todayClassOpen.value
}

function markNotificationRead(notif) {
  if (notif.read_at) return
  router.patch(
    `/gym/${props.tenant?.slug}/account/notifications/${notif.id}/read`,
    {},
    { preserveScroll: true, preserveState: true }
  )
}

function markAllNotificationsRead() {
  if (unreadCount.value === 0) return
  router.patch(
    `/gym/${props.tenant?.slug}/account/notifications/read-all`,
    {},
    { preserveScroll: true, preserveState: true }
  )
}

const mobileMenuOpen = ref(false)
</script>

<template>
  <nav ref="headerRef" :class="[theme.navBg, theme.border, 'sticky top-0 z-30 backdrop-blur-md border-b transition-colors duration-300']">
    <div class="max-w-6xl mx-auto px-5 py-3 flex items-center justify-between gap-4">
      <slot name="brand">
        <a :href="homeHref" class="flex items-center gap-2.5 shrink-0 group min-w-0">
          <img
            v-if="logoUrl"
            :src="logoUrl"
            :alt="gymName"
            class="w-9 h-9 rounded-lg object-cover ring-1 ring-white/10 group-hover:scale-105 group-hover:ring-emerald-400/50 transition-all duration-200"
          />
          <div
            v-else
            class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-slate-950 font-bold text-sm shrink-0 group-hover:scale-105 transition-transform duration-200"
          >
            {{ initials }}
          </div>
          <span class="font-bold text-lg tracking-tight truncate group-hover:text-emerald-400 transition-colors">{{ gymName }}</span>
        </a>
      </slot>

      <div class="hidden md:flex items-center gap-7 text-sm" :class="theme.textMuted">
        <a
          v-for="link in navLinks"
          :key="link.href"
          :href="link.href"
          class="relative py-1 hover:text-emerald-400 transition-colors after:content-[''] after:absolute after:-bottom-0.5 after:left-0 after:h-px after:w-0 after:bg-emerald-400 after:transition-all after:duration-300 hover:after:w-full"
        >
          {{ t[link.key] ?? link.fallback }}
        </a>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <a
          v-if="!isMemberHere && !isStaffHere && !isAuthenticatedElsewhere"
          :href="registerHref"
          :class="theme.border"
          class="hidden sm:flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-full border hover:border-emerald-400 hover:text-emerald-400 transition-all hover:scale-105"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
          </svg>
          {{ t.nav_join_login ?? 'ចូល/ចុះឈ្មោះ' }}
        </a>

        <div v-if="isMemberHere" class="relative">
          <button
            @click.stop="toggleNotif"
            :class="theme.border"
            class="relative p-1.5 rounded-full border hover:border-emerald-400 transition-all hover:scale-105"
            :title="t.nav_notifications ?? 'Notifications'"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span
              v-if="unreadCount > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
            >
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </button>

          <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="notifOpen" :class="[theme.card, theme.border]" class="absolute right-0 mt-2 w-72 border rounded-xl shadow-xl overflow-hidden">
              <div class="px-4 py-3 border-b flex items-center justify-between" :class="theme.border">
                <p class="text-sm font-semibold">{{ t.nav_notifications ?? 'Notifications' }}</p>
                <button
                  v-if="unreadCount > 0"
                  @click.stop="markAllNotificationsRead"
                  class="text-xs text-emerald-500 hover:text-emerald-400 transition-colors"
                >
                  {{ t.nav_mark_all_read ?? 'អានទាំងអស់' }}
                </button>
              </div>
              <div class="max-h-80 overflow-y-auto">
                <p v-if="!memberNotifications.length" class="px-4 py-6 text-sm text-center" :class="theme.textMuted">
                  {{ t.nav_no_notifications ?? 'មិនទាន់មាន notification ទេ' }}
                </p>
                <a
                  v-for="notif in memberNotifications"
                  :key="notif.id"
                  :href="notif.link ?? '#'"
                  @click.stop="markNotificationRead(notif)"
                  class="block px-4 py-3 text-sm border-b last:border-0 hover:bg-emerald-500/5 transition-colors"
                  :class="[theme.border, !notif.read_at ? 'font-medium' : theme.textMuted]"
                >
                  {{ notif.message }}
                </a>
              </div>
            </div>
          </Transition>
        </div>

        <div v-if="isMemberHere" class="relative">
          <button
            @click.stop="toggleTodayClass"
            :class="theme.border"
            class="relative p-1.5 rounded-full border hover:border-emerald-400 transition-all hover:scale-105"
            :title="t.nav_today_classes ?? 'Class ថ្ងៃនេះ'"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span
              v-if="memberTodayClasses.length > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-emerald-500 text-slate-950 text-[10px] font-bold flex items-center justify-center"
            >
              {{ memberTodayClasses.length }}
            </span>
          </button>

          <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="todayClassOpen" :class="[theme.card, theme.border]" class="absolute right-0 mt-2 w-72 border rounded-xl shadow-xl overflow-hidden">
              <div class="px-4 py-3 border-b" :class="theme.border">
                <p class="text-sm font-semibold">{{ t.nav_today_classes ?? 'Class ថ្ងៃនេះ' }}</p>
              </div>
              <div class="max-h-80 overflow-y-auto">
                <p v-if="!memberTodayClasses.length" class="px-4 py-6 text-sm text-center" :class="theme.textMuted">
                  {{ t.nav_no_classes_today ?? 'ថ្ងៃនេះមិនមាន class ដែលអ្នក book ទេ' }}
                </p>
                <div
                  v-for="cls in memberTodayClasses"
                  :key="cls.id"
                  class="px-4 py-3 text-sm border-b last:border-0"
                  :class="theme.border"
                >
                  <p class="font-medium">{{ cls.name }}</p>
                  <p :class="theme.textMuted" class="text-xs mt-0.5">{{ cls.start_time }} - {{ cls.end_time }}</p>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <div class="relative">
          <button @click.stop="toggleMenu" class="flex items-center gap-1 rounded-full transition-transform hover:scale-105">
            <img
              v-if="authUser?.avatar"
              :src="authUser.avatar"
              class="w-8 h-8 rounded-full object-cover ring-1 ring-white/10"
            />
            <div
              v-else
              :class="theme.border"
              class="w-8 h-8 rounded-full border flex items-center justify-center text-xs font-semibold bg-white/5"
            >
              {{ (authUser || staffViewer) ? userInitials : '👤' }}
            </div>
          </button>

          <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="menuOpen" :class="[theme.card, theme.border]" class="absolute right-0 mt-2 w-56 border rounded-xl shadow-xl overflow-hidden py-1.5 text-sm">
              <p v-if="authUser || staffViewer" class="px-4 py-2 font-semibold truncate border-b mb-1" :class="theme.border">
                {{ displayName }}
                <span v-if="isAuthenticatedElsewhere" class="block text-[11px] font-normal mt-0.5" :class="theme.textMuted">
                  {{ t.nav_signed_in_elsewhere ?? 'អ្នកកំពុង login ជាមួយគណនីមួយផ្សេង' }}
                </span>
              </p>

              <button @click="toggleTheme" class="w-full flex items-center justify-between px-4 py-2 hover:bg-emerald-500/5 transition-colors">
                <span class="flex items-center gap-2.5">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path v-if="isDark" stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36 6.36l-.7-.7M6.34 6.34l-.7-.7m12.02 0l-.7.7M6.34 17.66l-.7.7M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                  </svg>
                  {{ t.theme_toggle ?? 'ស្បែក' }}
                </span>
                <span :class="theme.textMuted" class="text-xs">{{ isDark ? (t.theme_dark ?? 'ងងឹត') : (t.theme_light ?? 'ភ្លឺ') }}</span>
              </button>

              <button @click="toggleLang" class="w-full flex items-center justify-between px-4 py-2 hover:bg-emerald-500/5 transition-colors">
                <span class="flex items-center gap-2.5">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9l4.418-14M12 20l4-16"/>
                  </svg>
                  {{ t.language_label ?? 'ភាសា' }}
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 font-semibold">
                  {{ lang === 'km' ? 'ខ្មែរ' : 'EN' }}
                </span>
              </button>

              <!-- Member of THIS gym -->
              <template v-if="isMemberHere">
                <div class="border-t my-1" :class="theme.border"></div>
                <Link :href="accountHref" @click="menuOpen = false" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-emerald-500/5 transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                  {{ t.nav_my_account ?? 'គណនីរបស់ខ្ញុំ' }}
                </Link>
                <Link href="/logout" method="post" as="button" @click="menuOpen = false" class="w-full flex items-center gap-2.5 px-4 py-2 text-red-400 hover:bg-red-500/5 transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                  </svg>
                  {{ t.nav_logout ?? 'ចាកចេញ' }}
                </Link>
              </template>

              <!-- Staff/manager/admin of THIS gym (incl. trainer magic-link
                   sessions via staffViewer) -->
              <template v-else-if="isStaffHere">
                <div class="border-t my-1" :class="theme.border"></div>
                <a href="/my/staff" @click="menuOpen = false" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-emerald-500/5 transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                  {{ t.nav_my_staff ?? 'ទំព័ររបស់ខ្ញុំ' }}
                </a>
                <div class="border-t my-1" :class="theme.border"></div>
                <Link
                  href="/logout"
                  method="post"
                  as="button"
                  @click="menuOpen = false"
                  class="w-full flex items-center gap-2.5 px-4 py-2 text-red-400 hover:bg-red-500/5 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                  </svg>
                  {{ t.nav_logout_switch ?? 'ចាកចេញ ដើម្បីប្តូរគណនី' }}
                </Link>
              </template>

              <!-- Authenticated elsewhere -->
              <template v-else-if="isAuthenticatedElsewhere">
                <div class="border-t my-1" :class="theme.border"></div>
                <Link
                  href="/logout"
                  method="post"
                  as="button"
                  @click="menuOpen = false"
                  class="w-full flex items-center gap-2.5 px-4 py-2 text-red-400 hover:bg-red-500/5 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                  </svg>
                  {{ t.nav_logout_switch ?? 'ចាកចេញ ដើម្បីប្តូរគណនី' }}
                </Link>
              </template>

              <!-- True guest -->
              <template v-else>
                <div class="border-t my-1" :class="theme.border"></div>
                <a :href="registerHref" class="w-full flex items-center gap-2.5 px-4 py-2 text-emerald-500 hover:bg-emerald-500/5 transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                  </svg>
                  {{ t.nav_join_login ?? 'ចូល/ចុះឈ្មោះ' }}
                </a>
                <a :href="staffLoginHref" class="w-full flex items-center gap-2.5 px-4 py-2 hover:bg-emerald-500/5 transition-colors" :class="theme.textMuted">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0l4-4m-4 4l4 4m13-4v6a2 2 0 01-2 2h-4a2 2 0 01-2-2v-1"/>
                  </svg>
                  {{ t.footer_staff_login ?? 'Staff / Admin Login' }}
                </a>
              </template>
            </div>
          </Transition>
        </div>

        <button class="md:hidden p-1.5 relative w-7 h-7" @click="mobileMenuOpen = !mobileMenuOpen" :aria-expanded="mobileMenuOpen" aria-label="Menu">
          <span class="absolute left-1 right-1 h-0.5 rounded-full transition-all duration-300" style="background-color: currentColor" :class="mobileMenuOpen ? 'top-1/2 -translate-y-1/2 rotate-45' : 'top-1.5'"></span>
          <span class="absolute left-1 right-1 h-0.5 rounded-full transition-all duration-200" style="background-color: currentColor" :class="mobileMenuOpen ? 'opacity-0' : 'top-1/2 -translate-y-1/2 opacity-100'"></span>
          <span class="absolute left-1 right-1 h-0.5 rounded-full transition-all duration-300" style="background-color: currentColor" :class="mobileMenuOpen ? 'top-1/2 -translate-y-1/2 -rotate-45' : 'bottom-1.5'"></span>
        </button>
      </div>
    </div>

    <Transition
      enter-active-class="transition-all duration-250 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="mobileMenuOpen" :class="theme.border" class="md:hidden border-t px-5 py-3 flex flex-col gap-3 text-sm overflow-hidden">
        <a v-for="link in navLinks" :key="link.href" :href="link.href" @click="mobileMenuOpen = false" :class="theme.textMuted" class="hover:text-emerald-400 transition-colors">
          {{ t[link.key] ?? link.fallback }}
        </a>
      </div>
    </Transition>
  </nav>
</template>