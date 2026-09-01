<script setup>
import { ref, onMounted, nextTick, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  profiles: Array,
  activeProfileIds: Array,
})

const { t, lang } = useLang()
const page = usePage()

const scannerInputRef = ref(null)
const scannerForm = useForm({ qr_token: '' })

function submitScannerToken() {
  if (!scannerForm.qr_token.trim()) return
  scannerForm.post('/dashboard/staff-attendance/scan', {
    preserveScroll: true,
    onFinish: () => {
      scannerForm.qr_token = ''
      nextTick(() => scannerInputRef.value?.focus())
    },
  })
}

function refocusScanner() {
  scannerInputRef.value?.focus()
}

const manualSearch = ref('')
const filteredProfiles = computed(() => {
  if (!manualSearch.value.trim()) return props.profiles
  const q = manualSearch.value.toLowerCase()
  return props.profiles.filter(p => p.name.toLowerCase().includes(q))
})

const activeCount = computed(() => props.activeProfileIds.length)
const totalCount = computed(() => props.profiles.length)

function isActive(profileId) {
  return props.activeProfileIds.includes(profileId)
}

function toggleManual(profile) {
  useForm({ staff_profile_id: profile.id }).post('/dashboard/staff-attendance/toggle', { preserveScroll: true })
}

onMounted(() => scannerInputRef.value?.focus())
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up" @click="refocusScanner">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
      <div>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m0 0h.01M8 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4z" /></svg>
          </span>
          {{ lang === 'km' ? 'ស្កេន QR បុគ្គលិក' : 'Staff QR Scan' }}
        </h1>
        <p class="text-sm text-slate-400 mt-1 ml-[52px]">{{ lang === 'km' ? 'ស្កេន QR ដើម្បី check-in/out' : 'Scan a QR code to check in/out' }}</p>
      </div>
      <div class="flex items-center gap-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ lang === 'km' ? 'កំពុងធ្វើការ' : 'Active now' }}:</span>
        <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ activeCount }}</span>
        <span class="text-sm text-slate-300 dark:text-slate-600">/ {{ totalCount }}</span>
      </div>
    </div>

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
        v-if="page.props.errors?.qr_token || page.props.errors?.staff_profile_id"
        class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-sm rounded-xl px-5 py-3.5 mb-5 text-center font-medium"
      >
        ❌ {{ page.props.errors.qr_token || page.props.errors.staff_profile_id }}
      </div>
    </Transition>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
      <!-- LEFT: Scan area -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm h-fit sticky top-20">
          <div class="flex flex-col items-center">
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
              :placeholder="lang === 'km' ? 'ស្កេន QR កូដ...' : 'Scan QR code...'"
              autocomplete="off"
            />
          </div>
        </div>
      </div>

      <!-- RIGHT: Manual select -->
      <div class="lg:col-span-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-900 dark:text-white">
              {{ lang === 'km' ? 'ជ្រើសរើសដោយដៃ' : 'Manual select' }}
            </p>
          </div>
          <input
            v-model="manualSearch"
            type="text"
            :placeholder="lang === 'km' ? 'ស្វែងរកបុគ្គលិក...' : 'Search staff...'"
            class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm mb-4 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500"
          />
          <div class="grid sm:grid-cols-2 gap-2.5 max-h-[520px] overflow-y-auto pr-1">
            <button
              v-for="profile in filteredProfiles"
              :key="profile.id"
              @click="toggleManual(profile)"
              class="flex items-center gap-3 p-3 rounded-xl border transition-all duration-200 hover:-translate-y-0.5"
              :class="isActive(profile.id)
                ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-500/10'
                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-emerald-300'"
            >
              <img v-if="profile.photo_url" :src="profile.photo_url" class="w-10 h-10 rounded-full object-cover shrink-0" />
              <div v-else class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
                {{ profile.name?.[0]?.toUpperCase() }}
              </div>
              <span class="flex-1 text-left text-sm font-medium text-slate-900 dark:text-white truncate">{{ profile.name }}</span>
              <span
                class="text-xs font-medium px-2.5 py-1 rounded-full shrink-0"
                :class="isActive(profile.id) ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300'"
              >
                {{ isActive(profile.id) ? (lang === 'km' ? 'ចុច Check-out' : 'Tap check-out') : (lang === 'km' ? 'ចុច Check-in' : 'Tap check-in') }}
              </span>
            </button>
            <p v-if="!filteredProfiles.length" class="col-span-full text-center text-sm text-slate-400 py-12">{{ lang === 'km' ? 'មិនទាន់មានបុគ្គលិកទេ' : 'No staff yet' }}</p>
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