<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import QRCode from 'qrcode'

const props = defineProps({
  tenant: Object,
  order: Object,
  qrString: String,
  autoVerifyEnabled: Boolean,
  canSimulate: Boolean,
  // Optional — same shape as GymHome.vue's settings/heroImages, reused here
  // so the payment banner matches the gym's own branding instead of a flat color.
  settings: Object,
  heroImages: Array,
})

// Same lookup order as GymHome's hero: an explicitly-chosen hero_banner_image
// first, then the first uploaded hero image, then no image (gradient fallback).
const bannerImage = computed(() =>
  props.settings?.hero_banner_image?.image_url ?? props.heroImages?.[0]?.image_url ?? null
)

const qrImageUrl = ref(null)
const status = ref('pending')
const pollTimer = ref(null)
const countdownTimer = ref(null)
const secondsElapsed = ref(0)
const simulating = ref(false)
const copied = ref(false)
const shareMenuOpen = ref(false)

// QR ផុតកំណត់ក្នុងរយៈពេល 15 នាទី
const EXPIRY_SECONDS = 15 * 60
const secondsRemaining = ref(EXPIRY_SECONDS)

const expiryLabel = computed(() => {
  const m = Math.floor(secondsRemaining.value / 60)
  const s = secondsRemaining.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})
const isExpiringSoon = computed(() => secondsRemaining.value <= 60 && secondsRemaining.value > 0)
const isExpired = computed(() => secondsRemaining.value <= 0)

onMounted(async () => {
  qrImageUrl.value = await QRCode.toDataURL(props.qrString, { width: 260, margin: 1 })

  if (props.autoVerifyEnabled) {
    pollTimer.value = setInterval(checkStatus, 4000)
  }

  countdownTimer.value = setInterval(() => {
    if (secondsRemaining.value > 0) secondsRemaining.value -= 1
  }, 1000)
})

onUnmounted(() => {
  if (pollTimer.value) clearInterval(pollTimer.value)
  if (countdownTimer.value) clearInterval(countdownTimer.value)
})

async function checkStatus() {
  secondsElapsed.value += 4

  try {
    const res = await fetch(`/gym/${props.tenant.slug}/class-orders/${props.order.id}/status`, {
      headers: { 'Accept': 'application/json' },
    })

    if (!res.ok) {
      console.error('Status check failed with', res.status)
      if (res.status === 401 || res.status === 419) {
        clearInterval(pollTimer.value)
        router.visit('/login')
      }
      return
    }

    const data = await res.json()
    status.value = data.status

    if (data.status === 'verified') {
      clearInterval(pollTimer.value)
      redirectToAccount()
    }
  } catch (e) {
    console.error('Status check failed', e)
  }
}

async function simulatePayment() {
  if (simulating.value) return
  simulating.value = true
  try {
    const res = await fetch(`/gym/${props.tenant.slug}/class-orders/${props.order.id}/simulate`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })
    if (res.ok) {
      status.value = 'verified'
      redirectToAccount()
    } else if (res.status === 401 || res.status === 419) {
      router.visit('/login')
    }
  } catch (e) {
    console.error('Simulate failed', e)
  } finally {
    simulating.value = false
  }
}

function redirectToAccount() {
  setTimeout(() => {
    router.visit(`/gym/${props.tenant.slug}/account`)
  }, 1500)
}

function goBack() {
  window.history.back()
}

function toggleShareMenu() {
  shareMenuOpen.value = !shareMenuOpen.value
}

function shareText() {
  return `ទូទាត់ចំនួន $${Number(props.order.total_amount).toFixed(2)} សម្រាប់ ${props.tenant?.name} — ${window.location.href}`
}

function shareToTelegram() {
  const url = encodeURIComponent(window.location.href)
  const text = encodeURIComponent(`ទូទាត់ចំនួន $${Number(props.order.total_amount).toFixed(2)} សម្រាប់ ${props.tenant?.name}`)
  window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank')
  shareMenuOpen.value = false
}

function shareToMessage() {
  window.location.href = `sms:?body=${encodeURIComponent(shareText())}`
  shareMenuOpen.value = false
}

async function copyQrLink() {
  try {
    await navigator.clipboard.writeText(shareText())
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch (e) {
    console.error('Copy failed', e)
  } finally {
    shareMenuOpen.value = false
  }
}
</script>

<template>
  <div class="relative min-h-screen w-full overflow-hidden bg-slate-900">
    <!-- Full-bleed blurred background — gym's own photo when available, gradient otherwise -->
    <div
      v-if="bannerImage"
      class="absolute inset-0 bg-cover bg-center scale-110 blur-md"
      :style="`background-image: url(${bannerImage})`"
    ></div>
    <div v-else class="absolute inset-0 bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500"></div>
    <div class="absolute inset-0 bg-slate-950/55"></div>

    <button
      @click="goBack"
      class="fixed top-4 left-4 z-20 inline-flex items-center gap-1.5 bg-white/15 hover:bg-white/25 backdrop-blur-sm text-white text-sm font-medium py-2 px-3 rounded-lg transition-colors"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      ត្រឡប់ក្រោយ
    </button>

    <!-- Modal card, floating over the blurred photo -->
    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-20">
      <div class="w-full max-w-md bg-white/95 backdrop-blur-xl rounded-3xl p-6 sm:p-8 text-center shadow-2xl border border-white/60">

        <p class="text-xs font-semibold tracking-widest uppercase text-emerald-500 mb-1">{{ tenant?.name }}</p>
        <h1 class="text-lg font-semibold text-slate-900 mb-5">ការទូទាត់ Class</h1>

        <!-- Progress steps -->
        <div class="flex items-center justify-center gap-2 mb-6">
          <div class="flex items-center gap-1.5">
            <div class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs font-semibold flex items-center justify-center">✓</div>
            <span class="text-xs text-slate-500 hidden sm:inline">ជ្រើសរើសថ្នាក់</span>
          </div>
          <div class="w-6 h-px bg-emerald-300"></div>
          <div class="flex items-center gap-1.5">
            <div
              class="w-6 h-6 rounded-full text-xs font-semibold flex items-center justify-center"
              :class="status === 'verified' ? 'bg-emerald-500 text-white' : 'bg-emerald-100 text-emerald-600 ring-2 ring-emerald-500'"
            >{{ status === 'verified' ? '✓' : '2' }}</div>
            <span class="text-xs font-medium text-slate-700 hidden sm:inline">ទូទាត់</span>
          </div>
          <div class="w-6 h-px" :class="status === 'verified' ? 'bg-emerald-300' : 'bg-slate-200'"></div>
          <div class="flex items-center gap-1.5">
            <div
              class="w-6 h-6 rounded-full text-xs font-semibold flex items-center justify-center"
              :class="status === 'verified' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400'"
            >3</div>
            <span class="text-xs text-slate-400 hidden sm:inline">បញ្ចប់</span>
          </div>
        </div>

        <!-- Order items -->
        <div class="space-y-1.5 mb-4 text-left">
          <div v-for="(item, i) in order.items" :key="i" class="flex items-center justify-between text-sm">
            <span class="text-slate-600 truncate">{{ item.name }}</span>
            <span class="text-slate-400 shrink-0 ml-2">${{ Number(item.price).toFixed(2) }}</span>
          </div>
        </div>
        <div class="border-t border-slate-200 pt-3 mb-6 flex items-center justify-between">
          <span class="text-sm text-slate-500">សរុប</span>
          <p class="text-3xl font-extrabold text-emerald-500">${{ Number(order.total_amount).toFixed(2) }}</p>
        </div>

        <div v-if="status === 'pending'">
          <!-- QR with countdown badge -->
          <div class="relative inline-block mb-3">
            <div class="bg-white rounded-xl p-4 border border-slate-200">
              <img v-if="qrImageUrl" :src="qrImageUrl" alt="KHQR Payment" class="w-56 h-56 sm:w-64 sm:h-64" :class="{ 'opacity-30': isExpired }" />
            </div>
            <div
              class="absolute -top-2 -right-2 text-[11px] font-semibold px-2 py-1 rounded-full shadow-sm"
              :class="isExpiringSoon || isExpired ? 'bg-rose-500 text-white' : 'bg-white text-slate-600 border border-slate-200'"
            >
              {{ isExpired ? 'ផុតកំណត់' : expiryLabel }}
            </div>
          </div>

          <p v-if="!isExpired" class="text-sm text-slate-500 mb-1">ស្កេន QR នេះដើម្បីបង់ប្រាក់ជាមួយ Bakong / ធនាគារណាមួយ</p>
          <p v-else class="text-sm text-rose-500 mb-1">QR នេះបានផុតកំណត់ សូម Refresh ទំព័រ ដើម្បីបង្កើតថ្មី</p>

          <!-- Trust badge -->
          <div class="inline-flex items-center gap-1.5 text-xs text-slate-400 mt-2 mb-4">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            ធានាសុវត្ថិភាពដោយ Bakong
          </div>

          <!-- Copy / Share -->
          <div class="relative mb-3">
            <button
              @click="toggleShareMenu"
              class="w-full border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-medium py-2.5 rounded-lg transition-colors inline-flex items-center justify-center gap-1.5"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.68 13.34a3 3 0 100-2.68m0 2.68a3 3 0 110-2.68m0 2.68l6.64 3.98m-6.64-6.66l6.64-3.98m0 0a3 3 0 105.36-2.68 3 3 0 00-5.36 2.68zm0 10.64a3 3 0 105.36 2.68 3 3 0 00-5.36-2.68z"/></svg>
              {{ copied ? 'បានចម្លងទៅ Clipboard!' : 'ចែករំលែក QR' }}
            </button>

            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 -translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div
                v-if="shareMenuOpen"
                class="absolute left-0 right-0 top-full mt-2 bg-white border border-slate-200 rounded-xl shadow-lg z-10 overflow-hidden"
              >
                <button
                  @click="shareToTelegram"
                  class="w-full flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                >
                  <span class="w-8 h-8 rounded-full bg-sky-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M21.05 3.16L2.53 10.53c-1.26.5-1.25 1.2-.23 1.51l4.75 1.48 1.84 5.6c.22.6.4.85.8.85.4 0 .58-.18.8-.4.13-.13.85-.83 1.7-1.65l3.53 2.6c.65.36 1.12.17 1.28-.6l2.32-10.9c.24-1-.34-1.44-1.27-1.06z"/></svg>
                  </span>
                  Telegram
                </button>
                <button
                  @click="shareToMessage"
                  class="w-full flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 border-t border-slate-100 transition-colors"
                >
                  <span class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8-1.06 0-2.077-.163-3.02-.463L3 21l1.395-4.185C3.512 15.55 3 13.836 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                  </span>
                  Message (SMS)
                </button>
                <button
                  @click="copyQrLink"
                  class="w-full flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 border-t border-slate-100 transition-colors"
                >
                  <span class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                  </span>
                  ចម្លងតំណ (Copy link)
                </button>
              </div>
            </Transition>
          </div>

          <div v-if="autoVerifyEnabled" class="text-xs text-emerald-600 flex items-center justify-center gap-1.5 mt-2">
            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
            កំពុងរង់ចាំការទូទាត់... ({{ secondsElapsed }}s)
          </div>

          <!-- Dev/testing only: hard-blocked server-side whenever the tenant
               has a real Bakong token, regardless of this button. -->
          <button
            v-if="canSimulate"
            @click="simulatePayment"
            :disabled="simulating"
            class="mt-4 w-full bg-amber-400 hover:bg-amber-300 disabled:opacity-50 text-slate-900 text-sm font-semibold py-2.5 rounded-lg transition-colors inline-flex items-center justify-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            {{ simulating ? 'កំពុងដំណើរការ...' : '🧪 Simulate Payment (dev only)' }}
          </button>
        </div>

        <div v-else-if="status === 'verified'" class="text-emerald-600">
          <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-500 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <p class="text-lg font-medium">ការទូទាត់ជោគជ័យ!</p>
          <p class="text-sm text-slate-500 mt-1">កំពុងបញ្ជូនអ្នកទៅផ្ទាំងគណនី...</p>
        </div>
      </div>
    </div>
  </div>
</template>