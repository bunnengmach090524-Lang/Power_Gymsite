<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useTheme } from '../composables/useTheme'
import { useLang } from '../composables/useLang'
import { useCart } from '../composables/useCart'
import SiteHeader from '../Components/SiteHeader.vue'
import SiteFooter from '../Components/SiteFooter.vue'
import CartDrawer from '../Components/CartDrawer.vue'

const props = defineProps({
  tenant: Object,
  settings: Object,

  // ⚠️ NEW: only present on pages rendered by StaffSelfServiceController for
  // a trainer authenticated via the self-service magic link (no Laravel
  // Auth::login(), so page.props.auth.user is null for them). Declaring it
  // here lets Inertia's automatic prop pass-through actually reach
  // SiteHeader — without this, Vue silently drops it as a plain fallthrough
  // attribute instead of binding it as a real prop. Every other page keeps
  // sending `null`/undefined here, so nothing changes for member/staff/guest
  // pages that don't pass it.
  staffViewer: { type: Object, default: null },
})

const { theme } = useTheme()
const { t } = useLang()
const cart = useCart(props.tenant?.slug)

const showFloatingCta = ref(false)
const showBackToTop = ref(false)

function onScroll() {
  const y = window.scrollY
  showFloatingCta.value = y > 500
  showBackToTop.value = y > 900
}
onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

function scrollTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const homeHref = computed(() => (props.tenant?.slug ? `/gym/${props.tenant.slug}` : '/'))

// Member login page — tenant-scoped, password-based, renders
// Client/GymMemberLogin.vue via GymSiteController::login. Google button
// *inside* this page still carries ?tenant= for member Google sign-in.
const loginHref = computed(() => (props.tenant?.slug ? `/gym/${props.tenant.slug}/login` : '/login'))

// Member registration for THIS specific gym — password-based form with a
// Google option inside it (see Client/MemberRegister.vue).
const registerHref = computed(() => (props.tenant?.slug ? `/gym/${props.tenant.slug}/register` : '/register'))

// ⚠️ NEW/FIX: Staff/Admin login is a SEPARATE page (Auth/Login.vue) served
// by the GLOBAL /login route (AuthenticatedSessionController::create) —
// NOT the tenant-scoped member login above. Previously the "Staff/Admin
// Login" link in SiteHeader.vue reused `loginHref`, so it opened the
// member-styled login page instead of the admin one. This is always the
// same global path regardless of tenant, so it doesn't need to be a
// computed based on props.tenant.
const staffLoginHref = '/login'

const contactHref = computed(() => (props.tenant?.slug ? `/gym/${props.tenant.slug}/contact` : `${homeHref.value}#contact`))

// All main sections now live on their own dedicated pages (real routes).
// Only "About" stays as an in-page anchor on the homepage — Gallery moved
// to its own route (GymSiteController@gallery / Client/GymGallery.vue).
const navLinks = computed(() => [
  { href: `${homeHref.value}#about`, key: 'nav_about', fallback: 'About' },
  { href: props.tenant?.slug ? `/gym/${props.tenant.slug}/pricing` : '#', key: 'nav_pricing', fallback: 'Pricing' },
  { href: props.tenant?.slug ? `/gym/${props.tenant.slug}/trainers` : '#', key: 'nav_trainers', fallback: 'Trainers' },
  { href: props.tenant?.slug ? `/gym/${props.tenant.slug}/classes` : '#', key: 'nav_classes', fallback: 'Classes' },
  { href: props.tenant?.slug ? `/gym/${props.tenant.slug}/gallery` : '#', key: 'nav_gallery', fallback: 'Gallery' },
  { href: contactHref.value, key: 'nav_contact', fallback: 'Contact' },
])

// ===== Floating chat button (WhatsApp or Telegram) =====
const socials = computed(() => props.settings?.social_links ?? {})
const chatLink = computed(() => socials.value.whatsapp || socials.value.telegram || null)
const chatIsWhatsapp = computed(() => !!socials.value.whatsapp)
const chatOpen = ref(false)
</script>

<template>
  <div :class="[theme.bg, theme.text, 'min-h-screen transition-colors duration-300 scroll-smooth']">

    <!--
      ===== HEADER =====
      Standalone component (Components/SiteHeader.vue) — EventPlace-style
      avatar dropdown (theme + language + account + logout in one menu) plus a
      notification bell for logged-in members. staffViewer is forwarded here
      so trainer magic-link sessions (Staff/SelfService.vue) render the
      correct staff header instead of a guest one.
    -->
    <SiteHeader
      :tenant="tenant"
      :settings="settings"
      :nav-links="navLinks"
      :home-href="homeHref"
      :login-href="loginHref"
      :register-href="registerHref"
      :staff-login-href="staffLoginHref"
      :staff-viewer="staffViewer"
    >
      <template #brand>
        <slot name="brand" />
      </template>
    </SiteHeader>

    <!-- ===== PAGE CONTENT (each Client page fills this) ===== -->
    <slot />

    <!-- ===== SHARED FOOTER ===== -->
    <SiteFooter
      :tenant="tenant"
      :settings="settings"
      :nav-links="navLinks"
      :home-href="homeHref"
      :login-href="loginHref"
      :register-href="registerHref"
    />

    <!-- ===== CART DRAWER (slide-over, toggled by the floating cart button below) ===== -->
    <CartDrawer :tenant="tenant" />

    <!-- ===== FLOATING JOIN CTA ===== -->
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <a
        v-if="showFloatingCta"
        :href="registerHref"
        class="fixed bottom-24 right-6 z-40 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold rounded-full px-5 py-3 shadow-lg shadow-emerald-500/30 transition-transform hover:scale-105 flex items-center gap-2"
      >
        {{ t.hero_cta }}
      </a>
    </Transition>

    <!-- ===== FLOATING CHAT BUTTON (WhatsApp/Telegram) ===== -->
    <a
      v-if="chatLink"
      :href="chatLink"
      target="_blank"
      rel="noopener"
      class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110"
      :class="chatIsWhatsapp ? 'bg-[#25D366] shadow-[#25D366]/40' : 'bg-sky-500 shadow-sky-500/40'"
      @mouseenter="chatOpen = true"
      @mouseleave="chatOpen = false"
    >
      <span class="absolute inset-0 rounded-full animate-ping opacity-20" :class="chatIsWhatsapp ? 'bg-[#25D366]' : 'bg-sky-500'"></span>
      <svg v-if="chatIsWhatsapp" class="w-7 h-7 text-white relative" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.5 14.4c-.3-.1-1.7-.9-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.3-.4.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3 4.8 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 2-1.4.2-.7.2-1.2.1-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2a8.1 8.1 0 01-4.2-1.1l-.3-.2-3.1.8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/>
      </svg>
      <svg v-else class="w-7 h-7 text-white relative" fill="currentColor" viewBox="0 0 24 24">
        <path d="M21.94 3.6a1.5 1.5 0 0 0-1.53-.26L2.7 10.4a1.4 1.4 0 0 0 .06 2.63l4.55 1.5 1.76 5.6c.2.62.98.82 1.46.37l2.55-2.4 4.4 3.24c.7.51 1.7.13 1.9-.72l3.5-15.7a1.5 1.5 0 0 0-.94-1.72Z"/>
      </svg>

      <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 translate-x-2" enter-to-class="opacity-100 translate-x-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <span
          v-if="chatOpen"
          :class="[theme.card, theme.border]"
          class="absolute right-full mr-3 whitespace-nowrap text-sm px-3 py-1.5 rounded-lg border shadow-md"
        >
          {{ chatIsWhatsapp ? (t.chat_whatsapp ?? 'ជជែកតាម WhatsApp') : (t.chat_telegram ?? 'ជជែកតាម Telegram') }}
        </span>
      </Transition>
    </a>

    <!-- ===== FLOATING CART BUTTON ===== -->
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-75" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 scale-75">
      <button
        v-if="cart.count.value > 0"
        @click="cart.open()"
        class="fixed bottom-44 right-6 z-40 w-14 h-14 rounded-full bg-emerald-500 hover:bg-emerald-400 shadow-lg shadow-emerald-500/40 flex items-center justify-center transition-transform hover:scale-110"
      >
        <svg class="w-6 h-6 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-[11px] font-bold flex items-center justify-center">
          {{ cart.count.value }}
        </span>
      </button>
    </Transition>

    <!-- ===== BACK TO TOP ===== -->
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-75" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 scale-75">
      <button
        v-if="showBackToTop"
        @click="scrollTop"
        :class="[theme.card, theme.border]"
        class="fixed bottom-6 left-6 z-40 border rounded-full p-3 shadow-lg transition-transform hover:scale-110"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7-7"/></svg>
      </button>
    </Transition>
  </div>
</template>