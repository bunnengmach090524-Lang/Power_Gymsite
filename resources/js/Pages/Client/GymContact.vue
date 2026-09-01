<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ClientLayout from '../../Layouts/ClientLayout.vue'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  tenant: Object,
  settings: Object,
})

const { theme } = useTheme()
const { t } = useLang()

const bannerImage = computed(() => props.settings?.hero_banner_image?.image_url ?? null)
const socials = computed(() => props.settings?.social_links ?? {})
const hasSocials = computed(() => Object.values(socials.value).some(v => !!v))
const hasCoords = computed(() => !!props.tenant?.latitude && !!props.tenant?.longitude)
const mapSrc = computed(() =>
  hasCoords.value ? `https://maps.google.com/maps?q=${props.tenant.latitude},${props.tenant.longitude}&z=15&output=embed` : null
)

const phone = computed(() => props.settings?.contact_phone ?? props.tenant?.phone ?? null)
const email = computed(() => props.settings?.contact_email ?? props.tenant?.email ?? null)
const address = computed(() => props.tenant?.address ?? null)
const hasAnyInfo = computed(() => !!(address.value || phone.value || email.value))

const inquiryForm = useForm({ name: '', phone: '', email: '' })
function submitInquiry() {
  inquiryForm.post(`/gym/${props.tenant.slug}/inquiries`, { onSuccess: () => inquiryForm.reset() })
}

// ===== Copy address =====
const addressCopied = ref(false)

function copyAddress() {
  if (!address.value) return
  navigator.clipboard.writeText(address.value).then(() => {
    addressCopied.value = true
    setTimeout(() => { addressCopied.value = false }, 2000)
  })
}
</script>

<template>
  <!-- ===== PAGE BANNER ===== -->
  <section class="relative h-56 sm:h-72 flex items-center justify-center text-center px-6 overflow-hidden">
    <div
      v-if="bannerImage"
      class="absolute inset-0 bg-cover bg-center scale-105"
      :style="`background-image: url(${bannerImage})`"
    ></div>
    <div v-else class="absolute inset-0 bg-gradient-to-br from-emerald-900 via-slate-950 to-teal-900"></div>
    <div class="absolute inset-0 bg-slate-950/70"></div>
    <div class="relative animate-[fadeUp_0.7s_ease-out]">
      <p class="text-emerald-400 text-xs font-semibold tracking-[0.25em] uppercase mb-3">{{ tenant.name }}</p>
      <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">{{ t.contact_page_title ?? 'Contact Us' }}</h1>
    </div>
  </section>

  <!-- ===== MAP ===== -->
  <section v-if="mapSrc" class="max-w-6xl mx-auto px-6 -mt-10 relative z-10">
    <div class="rounded-2xl overflow-hidden shadow-2xl border" :class="theme.border">
      <iframe :src="mapSrc" class="w-full h-72 sm:h-96 border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </section>

  <!-- ===== INFO + FORM ===== -->
  <section class="max-w-6xl mx-auto px-6 py-16 sm:py-20 grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">

    <!-- Location & contact details -->
    <div v-reveal class="space-y-8">
      <div :class="[theme.card, theme.border]" class="rounded-2xl p-6 sm:p-7 border transition-all duration-300 hover:border-emerald-400/30">
        <h2 class="text-xl font-bold mb-5">{{ tenant.name }}</h2>

        <div v-if="hasAnyInfo" class="space-y-4">
          <div v-if="address" class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-full bg-emerald-500/10 flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-4.5 h-4.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </span>
            <div class="flex-1 min-w-0 pt-1.5">
             <p class="text-sm leading-relaxed" :class="theme.textMuted">{{ address }}</p>
             <button
               @click="copyAddress"
               class="inline-flex items-center gap-1 text-xs mt-1.5 transition-colors"
               :class="addressCopied ? 'text-emerald-400' : theme.textMuted + ' hover:text-emerald-400'"
             >
               <svg v-if="!addressCopied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
               </svg>
               <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
               </svg>
               {{ addressCopied ? (t.contact_address_copied ?? 'បានចម្លង!') : (t.contact_copy_address ?? 'ចម្លងអាសយដ្ឋាន') }}
             </button>
           </div>
          </div>

          <a v-if="phone" :href="`tel:${phone}`" class="flex items-center gap-3 group">
            <span class="w-9 h-9 rounded-full bg-emerald-500/10 flex items-center justify-center shrink-0 group-hover:bg-emerald-500/20 transition-colors">
              <svg class="w-4.5 h-4.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
              </svg>
            </span>
            <span class="text-sm group-hover:text-emerald-400 transition-colors" :class="theme.textMuted">{{ phone }}</span>
          </a>

          <a v-if="email" :href="`mailto:${email}`" class="flex items-center gap-3 group">
            <span class="w-9 h-9 rounded-full bg-emerald-500/10 flex items-center justify-center shrink-0 group-hover:bg-emerald-500/20 transition-colors">
              <svg class="w-4.5 h-4.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </span>
            <span class="text-sm group-hover:text-emerald-400 transition-colors" :class="theme.textMuted">{{ email }}</span>
          </a>
        </div>
        <p v-else class="text-sm" :class="theme.textMuted">
          {{ t.contact_no_info ?? 'ព័ត៌មានទំនាក់ទំនងមិនទាន់មានទេ សូមប្រើ form ខាងស្តាំដើម្បីទំនាក់ទំនងមកយើង' }}
        </p>
      </div>

      <div v-if="hasSocials" :class="[theme.card, theme.border]" class="rounded-2xl p-6 sm:p-7 border transition-all duration-300 hover:border-emerald-400/30">
        <p class="text-xs uppercase tracking-wide mb-4 font-semibold" :class="theme.textMuted">{{ t.contact_find_us ?? 'Find us on' }}</p>
        <div class="flex items-center gap-3 flex-wrap">
          <a v-if="socials.facebook" :href="socials.facebook" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0022 12z"/></svg>
          </a>
          <a v-if="socials.instagram" :href="socials.instagram" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2c2.72 0 3.06.01 4.12.06 1.06.05 1.79.22 2.43.47.66.26 1.21.6 1.76 1.15.55.55.9 1.1 1.15 1.76.25.64.42 1.37.47 2.43.05 1.06.06 1.4.06 4.12s-.01 3.06-.06 4.12c-.05 1.06-.22 1.79-.47 2.43a4.9 4.9 0 01-1.15 1.76 4.9 4.9 0 01-1.76 1.15c-.64.25-1.37.42-2.43.47-1.06.05-1.4.06-4.12.06s-3.06-.01-4.12-.06c-1.06-.05-1.79-.22-2.43-.47a4.9 4.9 0 01-1.76-1.15 4.9 4.9 0 01-1.15-1.76c-.25-.64-.42-1.37-.47-2.43C2.01 15.06 2 14.72 2 12s.01-3.06.06-4.12c.05-1.06.22-1.79.47-2.43.26-.66.6-1.21 1.15-1.76A4.9 4.9 0 015.44 2.53c.64-.25 1.37-.42 2.43-.47C8.94 2.01 9.28 2 12 2zm0 5a5 5 0 100 10 5 5 0 000-10zm0 8.2a3.2 3.2 0 110-6.4 3.2 3.2 0 010 6.4zm5.2-8.4a1.17 1.17 0 100-2.33 1.17 1.17 0 000 2.33z"/></svg>
          </a>
          <a v-if="socials.tiktok" :href="socials.tiktok" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M16.6 2h-3.2v13.4a2.9 2.9 0 11-2.9-3c.24 0 .48.03.7.08V9.3a6.1 6.1 0 105.3 6.05V8.6a7.7 7.7 0 004.5 1.5V6.9a4.3 4.3 0 01-4.4-4.9z"/></svg>
          </a>
          <a v-if="socials.telegram" :href="socials.telegram" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M21.94 3.6a1.5 1.5 0 0 0-1.53-.26L2.7 10.4a1.4 1.4 0 0 0 .06 2.63l4.55 1.5 1.76 5.6c.2.62.98.82 1.46.37l2.55-2.4 4.4 3.24c.7.51 1.7.13 1.9-.72l3.5-15.7a1.5 1.5 0 0 0-.94-1.72Z"/></svg>
          </a>
          <a v-if="socials.youtube" :href="socials.youtube" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 00.5 6.2 31.6 31.6 0 000 12a31.6 31.6 0 00.5 5.8 3 3 0 002.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 002.1-2.1A31.6 31.6 0 0024 12a31.6 31.6 0 00-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/></svg>
          </a>
          <a v-if="socials.whatsapp" :href="socials.whatsapp" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.7-.9-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.3-.4.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3 4.8 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 2-1.4.2-.7.2-1.2.1-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm0 18.2a8.1 8.1 0 01-4.2-1.1l-.3-.2-3.1.8.8-3-.2-.3A8.2 8.2 0 1112 20.2z"/></svg>
          </a>
          <a v-if="socials.twitter" :href="socials.twitter" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.9 2H22l-7.2 8.3L23.3 22h-6.9l-5.4-7-6.2 7H1.6l7.7-8.8L.8 2h7.1l4.9 6.4L18.9 2zm-1.2 18h1.9L7.4 4H5.3l12.4 16z"/></svg>
          </a>
          <a v-if="socials.linkedin" :href="socials.linkedin" target="_blank" rel="noopener" :class="theme.border" class="w-10 h-10 rounded-full border flex items-center justify-center hover:border-emerald-400 hover:text-emerald-400 hover:scale-110 transition-all">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.03-1.85-3.03-1.85 0-2.14 1.45-2.14 2.94v5.66H9.36V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 110-4.12 2.06 2.06 0 010 4.12zM7.11 20.45H3.56V9h3.55v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
          </a>
        </div>
      </div>

      <a v-if="settings?.virtual_tour_url" :href="settings.virtual_tour_url" target="_blank" rel="noopener" :class="[theme.card, theme.border]" class="inline-flex items-center gap-2 border rounded-lg px-4 py-2.5 text-sm hover:border-emerald-400 hover:text-emerald-400 transition-all">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.55-2.55A1 1 0 0121 8.3v7.4a1 1 0 01-1.45.9L15 14M5 6h9a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
        {{ t.contact_virtual_tour ?? 'Take a virtual tour' }}
      </a>
    </div>

    <!-- Inquiry form -->
    <div v-reveal="{ delay: 100 }">
      <form @submit.prevent="submitInquiry" :class="[theme.card, theme.border]" class="border rounded-2xl p-6 sm:p-7 space-y-4 shadow-lg transition-shadow hover:shadow-xl">
        <div class="flex items-center gap-2.5 mb-1">
          <span class="w-9 h-9 rounded-full bg-emerald-500/10 flex items-center justify-center shrink-0">
            <svg class="w-4.5 h-4.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-3 3v-3z"/>
            </svg>
          </span>
          <h3 class="font-semibold text-lg">{{ t.contact_form_title ?? 'Send us a message' }}</h3>
        </div>
        <input v-model="inquiryForm.name" type="text" :placeholder="t.contact_name ?? 'Your name'" :class="theme.input" class="w-full rounded-lg px-3.5 py-2.5 border focus:outline-none focus:ring-2 focus:ring-emerald-500 transition" />
        <input v-model="inquiryForm.phone" type="tel" :placeholder="t.contact_phone ?? 'Phone number'" :class="theme.input" class="w-full rounded-lg px-3.5 py-2.5 border focus:outline-none focus:ring-2 focus:ring-emerald-500 transition" />
        <input v-model="inquiryForm.email" type="email" :placeholder="t.contact_email ?? 'អ៊ីមែល (ស្រេចចិត្ត)'" :class="theme.input" class="w-full rounded-lg px-3.5 py-2.5 border focus:outline-none focus:ring-2 focus:ring-emerald-500 transition" />
        <button type="submit" :disabled="inquiryForm.processing" class="w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold rounded-lg py-2.5 transition-all hover:scale-[1.02]">
          {{ inquiryForm.processing ? (t.contact_sending ?? 'Sending...') : (t.contact_submit ?? 'Send message') }}
        </button>
        <p v-if="inquiryForm.recentlySuccessful" class="text-emerald-400 text-sm text-center">{{ t.contact_success ?? "Thanks! We'll be in touch soon." }}</p>
      </form>
    </div>
  </section>
</template>

<style>
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>