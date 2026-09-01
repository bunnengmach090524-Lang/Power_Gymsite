<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  trainers: Array,
  activeTrainerIds: Array,
})

const { t } = useLang()
const page = usePage()

const mode = ref('scanner') // 'scanner' | 'camera'

// ===== 1) HARDWARE SCANNER (keyboard-wedge input) =====
const scannerInputRef = ref(null)
const scannerForm = useForm({ qr_token: '' })

function submitScannerToken() {
  if (!scannerForm.qr_token.trim()) return
  scannerForm.post('/dashboard/trainer-attendance/scan', {
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
  html5QrCode = new Html5Qrcode('camera-scan-region')
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
  useForm({ qr_token: token }).post('/dashboard/trainer-attendance/scan', {
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

// ===== 3) MANUAL SELECT (always visible in right column now) =====
const manualSearch = ref('')
const filteredTrainers = computed(() => {
  if (!manualSearch.value.trim()) return props.trainers
  const q = manualSearch.value.toLowerCase()
  return props.trainers.filter(tr => tr.name.toLowerCase().includes(q))
})

const activeCount = computed(() => props.activeTrainerIds.length)
const totalCount = computed(() => props.trainers.length)

function isActive(trainerId) {
  return props.activeTrainerIds.includes(trainerId)
}

function toggleManual(trainer) {
  useForm({ trainer_id: trainer.id }).post('/dashboard/trainer-attendance/toggle', { preserveScroll: true })
}

onMounted(() => {
  if (mode.value === 'scanner') scannerInputRef.value?.focus()
})
onBeforeUnmount(() => stopCamera())
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up" @click="refocusScanner">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
      <div>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m0 0h.01M8 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4z" /></svg>
          </span>
          {{ t.trainer_attendance_scan_title }}
        </h1>
        <p class="text-sm text-slate-400 mt-1 ml-[52px]">{{ t.trainer_attendance_scan_hint }}</p>
      </div>
      <div class="flex items-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ t.trainer_attendance_active_now ?? 'Active now' }}:</span>
        <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ activeCount }}</span>
        <span class="text-sm text-slate-300 dark:text-slate-600">/ {{ totalCount }}</span>
      </div>
    </div>

    <!-- Flash messages -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="page.props.flash?.success"
        class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-xl px-5 py-3.5 mb-5 text-center font-medium"
      >
        ✅ {{ page.props.flash.success }}
      </div>
    </Transition>
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="page.props.errors?.qr_token || page.props.errors?.trainer_id"
        class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-sm rounded-xl px-5 py-3.5 mb-5 text-center font-medium"
      >
        ❌ {{ page.props.errors.qr_token || page.props.errors.trainer_id }}
      </div>
    </Transition>

    <!-- Two-column layout -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
      <!-- LEFT: Scan area (2/5 width) -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm h-fit sticky top-20">
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
            <div class="w-28 h-28 mx-auto mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center">
              <svg class="w-12 h-12 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
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
              id="camera-scan-region"
              class="w-full aspect-square rounded-2xl overflow-hidden bg-slate-900 mb-4"
            ></div>
            <p v-if="cameraError" class="text-sm text-red-500 mb-3">{{ cameraError }}</p>
            <button
              v-if="!cameraActive"
              @click="startCamera"
              class="w-full px-6 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
            >
              {{ t.trainer_attendance_start_camera }}
            </button>
            <button
              v-else
              @click="stopCamera"
              class="w-full px-6 py-3 rounded-xl bg-red-500 hover:bg-red-400 text-white font-medium transition-all duration-200"
            >
              {{ t.trainer_attendance_stop_camera }}
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT: Trainer list (3/5 width, always visible) -->
      <div class="lg:col-span-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-900 dark:text-white">
              {{ t.trainer_attendance_mode_manual ?? (lang === 'km' ? 'ជ្រើសរើសដោយដៃ' : 'Manual select') }}
            </p>
          </div>
          <input
            v-model="manualSearch"
            type="text"
            :placeholder="t.trainer_attendance_search_placeholder"
            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm mb-4 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500"
          />
          <div class="grid sm:grid-cols-2 gap-2.5 max-h-[520px] overflow-y-auto pr-1">
            <button
              v-for="trainer in filteredTrainers"
              :key="trainer.id"
              @click="toggleManual(trainer)"
              class="flex items-center gap-3 p-3 rounded-xl border transition-all duration-200 hover:-translate-y-0.5"
              :class="isActive(trainer.id)
                ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-500/10'
                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-emerald-300'"
            >
              <img v-if="trainer.photo_url" :src="`${trainer.photo_url}`" class="w-10 h-10 rounded-full object-cover shrink-0" />
              <div v-else class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
                {{ trainer.name?.[0]?.toUpperCase() }}
              </div>
              <span class="flex-1 text-left text-sm font-medium text-slate-900 dark:text-white truncate">{{ trainer.name }}</span>
              <span
                class="text-xs font-medium px-2.5 py-1 rounded-full shrink-0"
                :class="isActive(trainer.id) ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300'"
              >
                {{ isActive(trainer.id) ? t.trainer_attendance_tap_checkout : t.trainer_attendance_tap_checkin }}
              </span>
            </button>
            <p v-if="!filteredTrainers.length" class="col-span-full text-center text-sm text-slate-400 py-12">{{ t.trainer_empty }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
</style>