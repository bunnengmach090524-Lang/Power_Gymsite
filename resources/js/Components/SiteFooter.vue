<script setup>
import { computed } from 'vue'
import { useLang } from '../composables/useLang'

const props = defineProps({
  tenant: Object,
  settings: Object,
  navLinks: { type: Array, default: () => [] },
  homeHref: { type: String, default: '/' },
  loginHref: { type: String, default: '/login' },
  registerHref: { type: String, default: '/register' },
})

const { t } = useLang()

const gymName = computed(() => props.tenant?.name ?? 'GymSite')
const logoUrl = computed(() => props.settings?.logo_image?.image_url ?? null)
const initials = computed(() => gymName.value.trim().charAt(0).toUpperCase() || 'G')

const socials = computed(() => props.settings?.social_links ?? {})
const hasSocials = computed(() => Object.values(socials.value).some(v => !!v))
const contactPhone = computed(() => props.settings?.contact_phone ?? props.tenant?.phone)
const contactEmail = computed(() => props.settings?.contact_email ?? props.tenant?.email)
const contactAddress = computed(() => props.tenant?.address)
const hasCoords = computed(() => !!props.tenant?.latitude && !!props.tenant?.longitude)
const mapLink = computed(() =>
  hasCoords.value ? `https://maps.google.com/?q=${props.tenant.latitude},${props.tenant.longitude}` : null
)

const navIcons = {
  nav_about: '👋',
  nav_pricing: '💳',
  nav_trainers: '🧑‍🏫',
  nav_classes: '🏋️',
  nav_gallery: '📸',
  nav_contact: '✉️',
}
function iconFor(key) {
  return navIcons[key] ?? '•'
}
</script>

<template>
  <footer class="bg-slate-950 text-slate-300 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-6 py-16">
      <slot name="footer-content">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">

          <!-- Brand + description + socials -->
          <div class="lg:col-span-1">
            <div class="flex items-center gap-2.5 mb-4">
              <img v-if="logoUrl" :src="logoUrl" :alt="gymName" class="w-9 h-9 rounded-lg object-cover ring-1 ring-white/10" />
              <div v-else class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-slate-950 font-bold text-sm">{{ initials }}</div>
              <span class="font-bold text-lg text-white">{{ gymName }}</span>
            </div>
            <p class="text-sm leading-relaxed text-slate-400 mb-5">
              {{ settings?.tagline ?? t.footer_default_tagline ?? 'A modern gym management & member experience platform.' }}
            </p>
            <div v-if="hasSocials" class="flex items-center gap-4 flex-wrap">
              <a v-if="socials.facebook" :href="socials.facebook" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0022 12z"/></svg>
              </a>
              <a v-if="socials.instagram" :href="socials.instagram" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2c2.72 0 3.06.01 4.12.06 1.06.05 1.79.22 2.43.47.66.26 1.21.6 1.76 1.15.55.55.9 1.1 1.15 1.76.25.64.42 1.37.47 2.43.05 1.06.06 1.4.06 4.12s-.01 3.06-.06 4.12c-.05 1.06-.22 1.79-.47 2.43a4.9 4.9 0 01-1.15 1.76 4.9 4.9 0 01-1.76 1.15c-.64.25-1.37.42-2.43.47-1.06.05-1.4.06-4.12.06s-3.06-.01-4.12-.06c-1.06-.05-1.79-.22-2.43-.47a4.9 4.9 0 01-1.76-1.15 4.9 4.9 0 01-1.15-1.76c-.25-.64-.42-1.37-.47-2.43C2.01 15.06 2 14.72 2 12s.01-3.06.06-4.12c.05-1.06.22-1.79.47-2.43.26-.66.6-1.21 1.15-1.76A4.9 4.9 0 015.44 2.53c.64-.25 1.37-.42 2.43-.47C8.94 2.01 9.28 2 12 2zm0 5a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.2 3.2 0 110-6.4 3.2 3.2 0 010 6.4zm5.2-8.4a1.17 1.17 0 100-2.33 1.17 1.17 0 000 2.33z"/></svg>
              </a>
              <a v-if="socials.tiktok" :href="socials.tiktok" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.6 2h-3.2v13.4a2.9 2.9 0 11-2.9-3c.24 0 .48.03.7.08V9.3a6.1 6.1 0 105.3 6.05V8.6a7.7 7.7 0 004.5 1.5V6.9a4.3 4.3 0 01-4.4-4.9z"/></svg>
              </a>
              <a v-if="socials.telegram" :href="socials.telegram" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M21.94 3.6a1.5 1.5 0 0 0-1.53-.26L2.7 10.4a1.4 1.4 0 0 0 .06 2.63l4.55 1.5 1.76 5.6c.2.62.98.82 1.46.37l2.55-2.4 4.4 3.24c.7.51 1.7.13 1.9-.72l3.5-15.7a1.5 1.5 0 0 0-.94-1.72Z"/></svg>
              </a>
              <a v-if="socials.youtube" :href="socials.youtube" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 00.5 6.2 31.6 31.6 0 000 12a31.6 31.6 0 00.5 5.8 3 3 0 002.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 002.1-2.1A31.6 31.6 0 0024 12a31.6 31.6 0 00-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/></svg>
              </a>
              <a v-if="socials.whatsapp" :href="socials.whatsapp" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.7-.9-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.3-.4.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3 4.8 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 2-1.4.2-.7.2-1.2.1-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2a8.1 8.1 0 01-4.2-1.1l-.3-.2-3.1.8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
              </a>
              <a v-if="socials.twitter" :href="socials.twitter" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.9 2H22l-7.2 8.3L23.3 22h-6.9l-5.4-7-6.2 7H1.6l7.7-8.8L.8 2h7.1l4.9 6.4L18.9 2zm-1.2 18h1.9L7.4 4H5.3l12.4 16z"/></svg>
              </a>
              <a v-if="socials.linkedin" :href="socials.linkedin" target="_blank" rel="noopener" class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-white hover:bg-emerald-500 hover:text-slate-950 hover:scale-110 transition-all">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.03-1.85-3.03-1.85 0-2.14 1.45-2.14 2.94v5.66H9.36V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 110-4.12 2.06 2.06 0 010 4.12zM7.11 20.45H3.56V9h3.55v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
              </a>
            </div>
          </div>

          <!-- Quick Links -->
          <div>
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-4">{{ t.footer_quick_links ?? 'Quick Links' }}</h3>
            <ul class="space-y-3 text-sm">
              <li v-for="link in navLinks" :key="link.href">
                <a :href="link.href" class="text-slate-400 hover:text-emerald-400 transition-colors inline-flex items-center gap-2.5">
                  <span class="text-base leading-none">{{ iconFor(link.key) }}</span>
                  {{ t[link.key] ?? link.fallback }}
                </a>
              </li>
            </ul>
          </div>

          <!-- Membership shortcut -->
          <div>
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-4">{{ t.footer_membership ?? 'Membership' }}</h3>
            <ul class="space-y-3 text-sm">
              <li>
                <a :href="`${homeHref}/pricing`" class="text-slate-400 hover:text-emerald-400 transition-colors inline-flex items-center gap-2.5">
                  <span class="text-base leading-none">💳</span>
                  {{ t.footer_view_plans ?? 'View Plans' }}
                </a>
              </li>
              <li>
                <a :href="registerHref" class="text-slate-400 hover:text-emerald-400 transition-colors inline-flex items-center gap-2.5">
                  <span class="text-base leading-none">🚀</span>
                  {{ t.footer_join_now ?? 'Join Now' }}
                </a>
              </li>
              <li>
                <a :href="loginHref" class="text-slate-400 hover:text-emerald-400 transition-colors inline-flex items-center gap-2.5">
                  <span class="text-base leading-none">🔑</span>
                  {{ t.footer_staff_login ?? 'Staff / Admin Login' }}
                </a>
              </li>
            </ul>
          </div>

          <!-- Contact + location -->
          <div>
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-4">{{ t.footer_contact ?? 'Contact' }}</h3>
            <ul class="space-y-3 text-sm text-slate-400">
              <li v-if="contactAddress" class="flex items-start gap-2.5">
                <span class="text-base leading-none shrink-0">📍</span>
                <a v-if="mapLink" :href="mapLink" target="_blank" rel="noopener" class="hover:text-emerald-400 transition-colors leading-relaxed">{{ contactAddress }}</a>
                <span v-else class="leading-relaxed">{{ contactAddress }}</span>
              </li>
              <li v-if="contactPhone" class="flex items-center gap-2.5">
                <span class="text-base leading-none shrink-0">📞</span>
                <a :href="`tel:${contactPhone}`" class="hover:text-emerald-400 transition-colors">{{ contactPhone }}</a>
              </li>
              <li v-if="contactEmail" class="flex items-center gap-2.5">
                <span class="text-base leading-none shrink-0">✉️</span>
                <a :href="`mailto:${contactEmail}`" class="hover:text-emerald-400 transition-colors">{{ contactEmail }}</a>
              </li>
              <li v-if="!contactAddress && !contactPhone && !contactEmail" class="text-slate-500 text-xs italic">
                {{ t.footer_no_contact ?? 'Contact details coming soon.' }}
              </li>
            </ul>
          </div>
        </div>
      </slot>

      <!-- Bottom bar -->
      <div class="mt-12 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
        <p>© {{ new Date().getFullYear() }} {{ gymName }} · Powered by MACH BUNNENG</p>
        <a :href="loginHref" class="hover:text-emerald-400 transition-colors opacity-70 hover:opacity-100">
          {{ t.footer_staff_login ?? 'Staff / Admin Login' }}
        </a>
      </div>
    </div>
  </footer>
</template>