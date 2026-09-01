<script setup>
import { ref, computed } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  gymClass: Object,
  date: String,
  isMatchingDay: Boolean,
  rows: Array,
})

const { lang } = useLang()
const page = usePage()

const dayLabels = { mon: 'ចន្ទ', tue: 'អង្គារ', wed: 'ពុធ', thu: 'ព្រហស្បតិ៍', fri: 'សុក្រ', sat: 'សៅរ៍', sun: 'អាទិត្យ' }
const dayLabelsEn = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri', sat: 'Sat', sun: 'Sun' }

const statusMeta = {
  pending:    { label_km: 'មិនទាន់កត់ត្រា', label_en: 'Pending',    dot: 'bg-slate-400',   badge: 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' },
  present:    { label_km: 'មានវត្តមាន',     label_en: 'Present',    dot: 'bg-emerald-500', badge: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' },
  absent:     { label_km: 'អវត្តមាន',       label_en: 'Absent',     dot: 'bg-red-500',     badge: 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400' },
  permission: { label_km: 'សុំច្បាប់',       label_en: 'Excused',    dot: 'bg-amber-500',   badge: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400' },
}

// ===== Date navigation =====
const dateInput = ref(props.date)
function goToDate() {
  router.get(`/dashboard/classes/${props.gymClass.id}/roster`, { date: dateInput.value }, { preserveState: true })
}

// ===== Search =====
const search = ref('')
const filteredRows = computed(() => {
  if (!search.value.trim()) return props.rows
  const q = search.value.toLowerCase()
  return props.rows.filter(r => r.member?.name?.toLowerCase().includes(q))
})

// ===== Stats =====
const statusCounts = computed(() => {
  const counts = { pending: 0, present: 0, absent: 0, permission: 0 }
  for (const r of props.rows) counts[r.status] = (counts[r.status] ?? 0) + 1
  return counts
})

// ===== Per-row note draft (only shown when marking absent/permission) =====
const noteDrafts = ref({})
const openNoteFor = ref(null)

function toggleNote(bookingId) {
  openNoteFor.value = openNoteFor.value === bookingId ? null : bookingId
  if (!(bookingId in noteDrafts.value)) noteDrafts.value[bookingId] = ''
}

function mark(row, status) {
  router.post(
    `/dashboard/classes/${props.gymClass.id}/roster/mark`,
    {
      booking_id: row.booking_id,
      occurred_on: props.date,
      status,
      note: noteDrafts.value[row.booking_id] || null,
    },
    { preserveScroll: true, preserveState: true }
  )
  openNoteFor.value = null
}

function formatTime(t) {
  return t ? t.slice(0, 5) : ''
}
</script>

<template>
  <div class="w-full p-6 sm:p-8">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
      <div>
        <Link href="/dashboard/classes" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 mb-2">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
        </Link>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </span>
          {{ props.gymClass.name }}
          <span class="text-sm font-normal text-slate-400">
            · {{ lang === 'km' ? dayLabels[props.gymClass.schedule_day] : dayLabelsEn[props.gymClass.schedule_day] }}
            · {{ formatTime(props.gymClass.start_time) }}–{{ formatTime(props.gymClass.end_time) }}
          </span>
        </h1>
      </div>

      <input
        v-model="dateInput"
        @change="goToDate"
        type="date"
        class="text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
      />
    </div>

    <!-- Warning: picked date doesn't match this class's weekly day -->
    <div
      v-if="!isMatchingDay"
      class="flex items-center gap-2 bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30 text-amber-700 dark:text-amber-400 text-sm rounded-lg px-4 py-3 mb-5"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      {{ lang === 'km'
        ? `ថ្ងៃដែលបានជ្រើសរើសមិនត្រូវនឹងកាលវិភាគប្រចាំសប្តាហ៍របស់ class នេះទេ (${dayLabels[props.gymClass.schedule_day]})`
        : `The selected date doesn't match this class's weekly slot (${dayLabelsEn[props.gymClass.schedule_day]})` }}
    </div>

    <!-- Flash -->
    <div
      v-if="page.props.flash?.success"
      class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-5"
    >
      {{ page.props.flash.success }}
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="key in ['present', 'absent', 'permission', 'pending']" :key="key" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <div class="flex items-center gap-2 mb-2">
          <span class="w-2 h-2 rounded-full" :class="statusMeta[key].dot"></span>
          <p class="text-xs text-slate-400">{{ lang === 'km' ? statusMeta[key].label_km : statusMeta[key].label_en }}</p>
        </div>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ statusCounts[key] }}</p>
      </div>
    </div>

    <!-- Roster -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6">
      <div class="relative mb-4">
        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input
          v-model="search"
          type="text"
          :placeholder="lang === 'km' ? 'ស្វែងរកសមាជិក...' : 'Search member...'"
          class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
        />
      </div>

      <div class="space-y-3">
        <div
          v-for="row in filteredRows"
          :key="row.booking_id"
          class="rounded-2xl border border-slate-100 dark:border-slate-800 p-4"
        >
          <div class="flex items-center gap-3 flex-wrap">
            <img v-if="row.member?.photo_url" :src="row.member.photo_url" class="w-11 h-11 rounded-full object-cover shrink-0" />
            <div v-else class="w-11 h-11 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
              {{ row.member?.name?.[0]?.toUpperCase() ?? '?' }}
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="font-medium text-slate-900 dark:text-white truncate">{{ row.member?.name }}</p>
                <span
                  v-if="row.checked_in_hint"
                  class="inline-flex items-center gap-1 text-[11px] font-medium text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-500/10 px-2 py-0.5 rounded-full"
                  :title="lang === 'km' ? 'បាន check-in ចូល gym ក្នុងចន្លោះម៉ោង class នេះ' : 'Checked in to the gym during this class window'"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                  {{ lang === 'km' ? 'ប្រហែលបានមក' : 'Likely attended' }}
                </span>
              </div>
              <p class="text-xs text-slate-400">{{ row.member?.phone }}</p>
              <p v-if="row.marked_by_name" class="text-[11px] text-slate-400 mt-0.5">
                {{ lang === 'km' ? 'កត់ត្រាដោយ' : 'Marked by' }} {{ row.marked_by_name }}
              </p>
            </div>

            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full shrink-0" :class="statusMeta[row.status].badge">
              <span class="w-1.5 h-1.5 rounded-full" :class="statusMeta[row.status].dot"></span>
              {{ lang === 'km' ? statusMeta[row.status].label_km : statusMeta[row.status].label_en }}
            </span>
          </div>

          <!-- Action buttons -->
          <div class="flex items-center gap-2 mt-3 flex-wrap">
            <button
              @click="mark(row, 'present')"
              class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors duration-150"
              :class="row.status === 'present' ? 'bg-emerald-500 text-white' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-500/20'"
            >
              {{ lang === 'km' ? 'មានវត្តមាន' : 'Present' }}
            </button>
            <button
              @click="toggleNote(row.booking_id); mark(row, 'permission')"
              class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors duration-150"
              :class="row.status === 'permission' ? 'bg-amber-500 text-white' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-500/20'"
            >
              {{ lang === 'km' ? 'សុំច្បាប់' : 'Excused' }}
            </button>
            <button
              @click="mark(row, 'absent')"
              class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors duration-150"
              :class="row.status === 'absent' ? 'bg-red-500 text-white' : 'bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20'"
            >
              {{ lang === 'km' ? 'អវត្តមាន' : 'Absent' }}
            </button>
            <button
              v-if="row.status !== 'pending'"
              @click="mark(row, 'pending')"
              class="text-xs font-medium px-3 py-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors duration-150"
            >
              {{ lang === 'km' ? 'សម្អាត' : 'Clear' }}
            </button>

            <button
              @click="toggleNote(row.booking_id)"
              class="ml-auto text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors duration-150"
            >
              {{ row.note ? (lang === 'km' ? 'មើលកំណត់ចំណាំ' : 'View note') : (lang === 'km' ? '+ កំណត់ចំណាំ' : '+ Note') }}
            </button>
          </div>

          <!-- Note field (optional reason, e.g. for excused/absent) -->
          <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
            <div v-if="openNoteFor === row.booking_id" class="mt-3 flex items-center gap-2">
              <input
                v-model="noteDrafts[row.booking_id]"
                type="text"
                :placeholder="lang === 'km' ? 'មូលហេតុ (ស្រេចចិត្ត)...' : 'Reason (optional)...'"
                class="flex-1 text-sm px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
              />
              <button
                @click="mark(row, row.status === 'pending' ? 'permission' : row.status)"
                class="text-xs font-medium px-3 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
              >
                {{ lang === 'km' ? 'រក្សាទុក' : 'Save' }}
              </button>
            </div>
          </Transition>
        </div>

        <p v-if="!filteredRows.length" class="text-center text-slate-400 py-10">
          {{ search.trim() ? (lang === 'km' ? 'រកមិនឃើញលទ្ធផល' : 'No matching members') : (lang === 'km' ? 'គ្មានសមាជិកបានកក់ class នេះទេ' : 'No members booked into this class') }}
        </p>
      </div>
    </div>
  </div>
</template>