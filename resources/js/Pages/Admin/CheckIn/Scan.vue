<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const { t, lang } = useLang()
const page = usePage()

const mode = ref('scanner') // 'scanner' | 'camera'

const checkedInMember = computed(() => page.props.flash?.checkedInMember ?? null)

function statusBadgeClass(status) {
  return {
    active: 'bg-emerald-500 text-white',
    expiring: 'bg-amber-500 text-white',
    expired: 'bg-red-500 text-white',
    none: 'bg-slate-400 text-white',
  }[status] ?? 'bg-slate-400 text-white'
}

function statusLabel(status) {
  const labels = {
    active: lang.value === 'km' ? 'សកម្ម' : 'Active',
    expiring: lang.value === 'km' ? 'ជិតផុតកំណត់' : 'Expiring',
    expired: lang.value === 'km' ? 'ផុតកំណត់' : 'Expired',
    none: lang.value === 'km' ? 'គ្មានកញ្ចប់' : 'No plan',
  }
  return labels[status] ?? '—'
}

// ===== 1) HARDWARE SCANNER (keyboard-wedge input) =====
const scannerInputRef = ref(null)
const scannerForm = useForm({ qr_token: '' })

function submitScannerToken() {
  if (!scannerForm.qr_token.trim()) return
  scannerForm.post('/dashboard/check-in/scan', {
    preserveScroll: true,
    onFinish: () => {
      scannerForm.qr_token = ''
      nextTick(() => scannerInputRef.value?.focus())
    },
  })
}

function refocusScanner() {
  if (mode.value === 'scanner') scannerInputRef.value?.focus()
}

// ===== 2) CAMERA SCAN =====
const cameraActive = ref(false)
const cameraError = ref('')
let html5QrCode = null

async function startCamera() {
  cameraError.value = ''
  const { Html5Qrcode } = await import('html5-qrcode')
  html5QrCode = new Html5Qrcode('member-camera-scan-region')
  try {
    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: { width: 220, height: 220 } },
      (decodedText) => {
        onCameraDecoded(decodedText)
      },
      () => {} // ignore per-frame scan failures
    )
    cameraActive.value = true
  } catch (err) {
    cameraError.value = t.value.trainer_attendance_camera_error
  }
}

async function stopCamera() {
  if (html5QrCode && cameraActive.value) {
    try { await html5QrCode.stop() } catch (e) {}
    html5QrCode = null
  }
  cameraActive.value = false
}

let processingCameraScan = false
function onCameraDecoded(token) {
  if (processingCameraScan) return
  processingCameraScan = true
  useForm({ qr_token: token }).post('/dashboard/check-in/scan', {
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => { processingCameraScan = false }, 1500) // debounce re-scans
    },
  })
}

function switchMode(newMode) {
  if (mode.value === 'camera' && newMode !== 'camera') stopCamera()
  mode.value = newMode
  if (newMode === 'scanner') nextTick(() => scannerInputRef.value?.focus())
}

onMounted(() => {
  if (mode.value === 'scanner') scannerInputRef.value?.focus()
})
onBeforeUnmount(() => stopCamera())
</script>

<template>
  <div class="p-6 sm:p-8 max-w-2xl mx-auto animate-fade-in-up" @click="refocusScanner">
    <Link href="/dashboard/check-in" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 mb-4">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
      {{ t.checkin_back ?? (lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back') }}
    </Link>

    <h1 class="text-xl font-semibold text-slate-900 dark:text-white mb-2 text-center">
      📷 {{ t.checkin_scan_title ?? 'ស្កេន QR Check-in' }}
    </h1>
    <p class="text-sm text-slate-400 mb-6 text-center">{{ t.checkin_scan_hint ?? 'ដាក់ scanner ទៅលើ QR code របស់សមាជិក' }}</p>

    <!-- Success with photo + status badge -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="checkedInMember"
        class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 rounded-xl px-5 py-4 mb-3 flex items-center gap-3"
      >
        <img
          v-if="checkedInMember.photo_url"
          :src="`${checkedInMember.photo_url}`"
          class="w-12 h-12 rounded-full object-cover shrink-0"
        />
        <div v-else class="w-12 h-12 rounded-full bg-emerald-500 flex items-center justify-center text-white font-semibold shrink-0">
          {{ checkedInMember.name?.[0]?.toUpperCase() }}
        </div>
        <div class="flex-1 text-left min-w-0">
          <p class="text-emerald-700 dark:text-emerald-400 font-semibold truncate">✅ {{ checkedInMember.name }}</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-500">
            {{ lang === 'km' ? 'បាន check-in ជោគជ័យ' : 'Checked in successfully' }}
          </p>
        </div>
        <span
          class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
          :class="statusBadgeClass(checkedInMember.status)"
        >
          {{ statusLabel(checkedInMember.status) }}
        </span>
      </div>
    </Transition>

    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="page.props.flash?.warning"
        class="bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30 text-amber-700 dark:text-amber-400 text-base rounded-xl px-5 py-3.5 mb-5 text-center font-medium"
      >
        {{ page.props.flash.warning }}
      </div>
    </Transition>
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="page.props.errors?.qr_token"
        class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-base rounded-xl px-5 py-3.5 mb-5 text-center font-medium"
      >
        ❌ {{ page.props.errors.qr_token }}
      </div>
    </Transition>

    <!-- Mode tabs -->
    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-xl p-1 mb-6">
      <button
        v-for="opt in ['scanner', 'camera']"
        :key="opt"
        @click="switchMode(opt)"
        class="flex-1 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
        :class="mode === opt ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
      >
        {{ t[`trainer_attendance_mode_${opt}`] }}
      </button>
    </div>

    <!-- MODE 1: Hardware Scanner -->
    <div v-if="mode === 'scanner'" class="flex flex-col items-center">
      <div class="w-32 h-32 mx-auto mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center">
        <svg class="w-14 h-14 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m0 0h.01M8 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4z" />
        </svg>
      </div>
      <input
        ref="scannerInputRef"
        v-model="scannerForm.qr_token"
        @keyup.enter="submitScannerToken"
        @blur="refocusScanner"
        type="text"
        class="w-full px-4 py-4 text-center text-lg rounded-xl border-2 border-emerald-300 dark:border-emerald-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-emerald-500/30"
        :placeholder="t.trainer_attendance_scan_placeholder"
        autocomplete="off"
      />
    </div>

    <!-- MODE 2: Camera Scan -->
    <div v-else class="flex flex-col items-center">
      <div
        id="member-camera-scan-region"
        class="w-full max-w-sm aspect-square rounded-2xl overflow-hidden bg-slate-900 mb-4"
      ></div>
      <p v-if="cameraError" class="text-sm text-red-500 mb-3">{{ cameraError }}</p>
      <button
        v-if="!cameraActive"
        @click="startCamera"
        class="px-6 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-medium transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        {{ t.trainer_attendance_start_camera }}
      </button>
      <button
        v-else
        @click="stopCamera"
        class="px-6 py-3 rounded-xl bg-red-500 hover:bg-red-600 text-white font-medium transition-all duration-200"
      >
        {{ t.trainer_attendance_stop_camera }}
      </button>
    </div>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
</style>