<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ClientLayout from '@/Layouts/ClientLayout.vue'
import { useLang } from '@/composables/useLang'
import { useTheme } from '@/composables/useTheme'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  profile: Object,
  payments: Array,
  attendances: Array,
  tenant: Object,
  settings: Object,
})

const { lang } = useLang()
const { theme } = useTheme()

const tabs = ['salary', 'attendance', 'qr']
const activeTab = ref('salary')

const tabLabel = (tab) => ({
  salary: lang.value === 'km' ? 'ប្រាក់ខែ' : 'Salary',
  attendance: lang.value === 'km' ? 'វត្តមាន' : 'Attendance',
  qr: lang.value === 'km' ? 'QR ខ្លួនឯង' : 'My QR',
}[tab])

const tabIcon = (tab) => ({
  salary: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m0-9a9 9 0 100 8',
  attendance: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
  qr: 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 3h2m-2 3h6m-3-6v6',
}[tab])

function statusLabel(status) {
  return status === 'paid'
    ? (lang.value === 'km' ? 'បានបង់' : 'Paid')
    : (lang.value === 'km' ? 'មិនទាន់បង់' : 'Pending')
}

function formatDate(dateStr) {
  return dateStr?.slice(0, 10) ?? dateStr
}

function attendanceStatusLabel(status) {
  return status === 'present'
    ? (lang.value === 'km' ? 'មានវត្តមាន' : 'Present')
    : (lang.value === 'km' ? 'អវត្តមាន' : 'Absent')
}

const totalPending = computed(() =>
  props.payments.filter(p => p.status !== 'paid').reduce((sum, p) => sum + Number(p.total), 0)
)
const totalPaid = computed(() =>
  props.payments.filter(p => p.status === 'paid').reduce((sum, p) => sum + Number(p.total), 0)
)

// ===== Edit Profile (photo + name/phone) — same pattern as Member's
// account page. No email field here: staff/trainer accounts don't manage
// email self-service the way members do.
const profileForm = useForm({
  name: props.profile.name ?? '',
  phone: props.profile.phone ?? '',
  photo: null,
})

const photoPreview = ref(props.profile.photo_url ?? null)
const fileInputRef = ref(null)

function onPhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  profileForm.photo = file
  photoPreview.value = URL.createObjectURL(file)
}

function triggerFilePicker() {
  fileInputRef.value?.click()
}

function submitProfile() {
  profileForm.post('/my/staff', {
    forceFormData: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <div class="w-full max-w-4xl mx-auto px-4 sm:px-6 py-10">

    <!-- ===== Profile hero ===== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-700 p-6 sm:p-8 mb-8 shadow-xl shadow-emerald-500/20 animate-fade-in-up">
      <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
      <div class="absolute -bottom-14 -left-8 w-48 h-48 rounded-full bg-white/10 blur-2xl"></div>

      <div class="relative flex items-center gap-4 sm:gap-5">
        <img
          v-if="profile.photo_url"
          :src="profile.photo_url"
          class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover ring-4 ring-white/30 shadow-lg"
        />
        <div
          v-else
          class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center text-white text-2xl font-bold ring-4 ring-white/30 shadow-lg"
        >
          {{ profile.name?.[0]?.toUpperCase() ?? '?' }}
        </div>
        <div class="min-w-0">
          <h1 class="text-xl sm:text-2xl font-bold text-white truncate">{{ profile.name }}</h1>
          <p class="text-emerald-50/90 text-sm sm:text-base">{{ profile.position }}</p>
        </div>
      </div>

      <!-- Quick stats -->
      <div class="relative grid grid-cols-2 gap-3 mt-6">
        <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3">
          <p class="text-emerald-50/80 text-xs">{{ lang === 'km' ? 'បានបង់សរុប' : 'Total paid' }}</p>
          <p class="text-white text-lg font-bold">${{ totalPaid.toFixed(2) }}</p>
        </div>
        <div class="bg-white/15 backdrop-blur rounded-xl px-4 py-3">
          <p class="text-emerald-50/80 text-xs">{{ lang === 'km' ? 'មិនទាន់បង់' : 'Pending' }}</p>
          <p class="text-white text-lg font-bold">${{ totalPending.toFixed(2) }}</p>
        </div>
      </div>
    </div>

    <!-- ===== EDIT PROFILE CARD ===== -->
    <div
      :class="[theme.card, theme.border]"
      class="rounded-2xl p-6 border mb-8 transition-all duration-300 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10"
    >
      <h2 class="text-sm font-semibold mb-1" :class="theme.textMuted">
        {{ lang === 'km' ? 'កែប្រែព័ត៌មានផ្ទាល់ខ្លួន' : 'Edit profile' }}
      </h2>
      <p class="text-xs mb-5" :class="theme.textMuted">
        {{ lang === 'km' ? 'ព័ត៌មាននេះនឹងបង្ហាញនៅលើគណនីរបស់អ្នក' : 'This information appears on your account' }}
      </p>

      <form @submit.prevent="submitProfile" class="space-y-5">
        <!-- Avatar upload -->
        <div class="flex items-center gap-4">
          <img
            v-if="photoPreview"
            :src="photoPreview"
            class="w-16 h-16 rounded-full object-cover ring-2 ring-emerald-400/30"
          />
          <div
            v-else
            :class="theme.card"
            class="w-16 h-16 rounded-full flex items-center justify-center font-bold text-lg ring-2 ring-emerald-400/30"
          >
            {{ profile.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div>
            <button
              type="button"
              @click="triggerFilePicker"
              :class="theme.border"
              class="inline-flex items-center gap-2 text-xs font-medium px-4 py-2 rounded-full border transition-all duration-200 hover:border-emerald-400 hover:text-emerald-400"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
              </svg>
              {{ lang === 'km' ? 'ប្តូររូបភាព' : 'Change photo' }}
            </button>
            <input ref="fileInputRef" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPhotoChange" />
            <p class="text-[11px] mt-1.5" :class="theme.textMuted">
              {{ lang === 'km' ? 'JPG, PNG ឬ WEBP។ ទំហំអតិបរមា 3MB' : 'JPG, PNG or WEBP. Max 3MB' }}
            </p>
          </div>
        </div>

        <!-- Name + Phone -->
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium mb-1.5" :class="theme.textMuted">
              {{ lang === 'km' ? 'ឈ្មោះ' : 'Name' }}
            </label>
            <input
              v-model="profileForm.name"
              type="text"
              :class="theme.input"
              class="w-full rounded-lg px-3.5 py-2.5 border focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
            />
            <p v-if="profileForm.errors.name" class="text-xs text-red-400 mt-1">{{ profileForm.errors.name }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium mb-1.5" :class="theme.textMuted">
              {{ lang === 'km' ? 'លេខទូរស័ព្ទ' : 'Phone' }}
            </label>
            <input
              v-model="profileForm.phone"
              type="tel"
              :class="theme.input"
              class="w-full rounded-lg px-3.5 py-2.5 border focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
            />
            <p v-if="profileForm.errors.phone" class="text-xs text-red-400 mt-1">{{ profileForm.errors.phone }}</p>
          </div>
        </div>

        <button
          type="submit"
          :disabled="profileForm.processing"
          class="text-xs font-medium px-5 py-2.5 rounded-full bg-emerald-500 text-slate-950 transition-all duration-200 hover:bg-emerald-400 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:hover:scale-100"
        >
          {{ profileForm.processing
            ? (lang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...')
            : (lang === 'km' ? 'រក្សាទុកការផ្លាស់ប្តូរ' : 'Save changes') }}
        </button>
        <p v-if="profileForm.recentlySuccessful" class="text-emerald-400 text-xs">
          {{ lang === 'km' ? 'បានរក្សាទុកជោគជ័យ!' : 'Saved successfully!' }}
        </p>
      </form>
    </div>

    <!-- ===== Tabs ===== -->
    <div class="flex gap-1 sm:gap-2 mb-6 p-1.5 rounded-2xl w-full sm:w-fit" :class="[theme.card, theme.border, 'border']">
      <button
        v-for="tab in tabs"
        :key="tab"
        @click="activeTab = tab"
        class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
        :class="activeTab === tab
          ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30 scale-[1.02]'
          : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" :d="tabIcon(tab)" />
        </svg>
        <span class="hidden sm:inline">{{ tabLabel(tab) }}</span>
        <span class="sm:hidden text-xs">{{ tabLabel(tab) }}</span>
      </button>
    </div>

    <!-- ===== Panels ===== -->
    <Transition
      mode="out-in"
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <!-- Salary -->
      <div v-if="activeTab === 'salary'" key="salary" :class="[theme.card, theme.border]" class="border rounded-2xl overflow-hidden shadow-sm">
        <div v-if="!payments.length" class="px-6 py-16 text-center">
          <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m0-9a9 9 0 100 8" />
          </svg>
          <p class="text-slate-400 text-sm">{{ lang === 'km' ? 'មិនទាន់មានកំណត់ត្រាទេ' : 'No salary records yet' }}</p>
        </div>
        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="pay in payments"
            :key="pay.id"
            class="flex items-center justify-between px-5 sm:px-6 py-4 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/50"
          >
            <div class="min-w-0">
              <p class="text-xs text-slate-400 mb-0.5">{{ formatDate(pay.period_start) }} → {{ formatDate(pay.period_end) }}</p>
              <p class="font-semibold" :class="theme.text">${{ pay.total }}</p>
            </div>
            <span
              class="text-xs font-semibold px-3 py-1.5 rounded-full whitespace-nowrap"
              :class="pay.status === 'paid'
                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'"
            >
              {{ statusLabel(pay.status) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Attendance -->
      <div v-else-if="activeTab === 'attendance'" key="attendance" :class="[theme.card, theme.border]" class="border rounded-2xl overflow-hidden shadow-sm">
        <div v-if="!attendances.length" class="px-6 py-16 text-center">
          <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-slate-400 text-sm">{{ lang === 'km' ? 'មិនទាន់មានវត្តមានទេ' : 'No attendance records yet' }}</p>
        </div>
        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="att in attendances"
            :key="att.id"
            class="flex items-center justify-between px-5 sm:px-6 py-4 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/50"
          >
            <div>
              <p class="text-sm font-medium" :class="theme.text">{{ att.date ?? att.created_at?.slice(0, 10) }}</p>
              <p class="text-xs text-slate-400">{{ att.time ?? att.created_at?.slice(11, 16) }}</p>
            </div>
            <span
              class="text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5"
              :class="att.status === 'present'
                ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
                : 'bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400'"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="att.status === 'present' ? 'bg-emerald-500' : 'bg-red-500'"></span>
              {{ attendanceStatusLabel(att.status) }}
            </span>
          </div>
        </div>
      </div>

      <!-- QR -->
      <div v-else key="qr" :class="[theme.card, theme.border]" class="border rounded-2xl p-6 sm:p-10 flex flex-col items-center text-center shadow-sm">
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5 max-w-xs">
          {{ lang === 'km' ? 'បង្ហាញ QR នេះនៅច្រកចូល ដើម្បី scan check-in' : 'Show this QR at the entrance to check in' }}
        </p>
        <a
          href="/my/staff/qr"
          target="_blank"
          class="group inline-block rounded-2xl p-5 bg-white border-2 border-slate-100 shadow-md transition-all duration-200 hover:shadow-xl hover:scale-[1.03] hover:border-emerald-300"
        >
          <img src="/my/staff/qr" alt="QR Code" class="w-44 h-44 sm:w-52 sm:h-52" />
        </a>
        <p class="text-xs text-slate-400 mt-4">{{ lang === 'km' ? 'ចុចដើម្បីពង្រីក' : 'Tap to enlarge' }}</p>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.4s ease-out; }
</style>