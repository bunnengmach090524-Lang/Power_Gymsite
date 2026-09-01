<script setup>
import { useForm, router, usePage, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  trainers: Array,
  todayAttendance: Array,
  activeTrainerIds: Array,
  presentCount: Number,
  totalTrainers: Number,
  selectedDate: String,
  isToday: Boolean,
})

const { t, lang } = useLang()
const page = usePage()

function isActive(trainerId) {
  return props.activeTrainerIds.includes(trainerId)
}

function isPresent(trainerId) {
  return props.todayAttendance.some(r => r.trainer_id === trainerId)
}

function latestRecord(trainerId) {
  return props.todayAttendance.find(a => a.trainer_id === trainerId && !a.checked_out_at)
}

function checkIn(trainer) {
  useForm({ trainer_id: trainer.id }).post('/dashboard/trainer-attendance/check-in', { preserveScroll: true })
}

function checkOut(trainer) {
  const record = latestRecord(trainer.id)
  if (!record) return
  router.patch(`/dashboard/trainer-attendance/${record.id}/check-out`, {}, { preserveScroll: true })
}

function deleteRecord(record) {
  if (confirm(t.value.trainer_attendance_confirm_delete)) {
    router.delete(`/dashboard/trainer-attendance/${record.id}`, { preserveScroll: true })
  }
}

function formatTime(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleTimeString(lang.value === 'km' ? 'km-KH' : 'en-US', { hour: '2-digit', minute: '2-digit' })
}

function duration(inTime, outTime) {
  if (!outTime) return null
  const mins = Math.round((new Date(outTime) - new Date(inTime)) / 60000)
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return h > 0 ? `${h}h ${m}m` : `${m}m`
}

const absentCount = computed(() => props.totalTrainers - props.presentCount)
const attendanceRate = computed(() => props.totalTrainers ? Math.round((props.presentCount / props.totalTrainers) * 100) : 0)
const finishedRecords = computed(() => props.todayAttendance.filter(r => r.checked_out_at))
const avgDuration = computed(() => {
  if (!finishedRecords.value.length) return null
  const totalMins = finishedRecords.value.reduce((sum, r) => sum + Math.round((new Date(r.checked_out_at) - new Date(r.checked_in_at)) / 60000), 0)
  const avg = Math.round(totalMins / finishedRecords.value.length)
  const h = Math.floor(avg / 60)
  const m = avg % 60
  return h > 0 ? `${h}h ${m}m` : `${m}m`
})

// ===== Date navigation =====
const dateInput = ref(props.selectedDate)

function goToDate() {
  router.get('/dashboard/trainer-attendance', { date: dateInput.value }, { preserveState: true })
}

function exportCsv() {
  window.location.href = `/dashboard/trainer-attendance/export?date=${props.selectedDate}`
}

// ===== Search + filter =====
const rosterSearch = ref('')
const rosterFilter = ref('all') // 'all' | 'present' | 'absent'

const filteredTrainers = computed(() => {
  let list = props.trainers

  if (rosterFilter.value === 'present') {
    list = list.filter(tr => isPresent(tr.id))
  } else if (rosterFilter.value === 'absent') {
    list = list.filter(tr => !isPresent(tr.id))
  }

  if (rosterSearch.value.trim()) {
    const q = rosterSearch.value.toLowerCase()
    list = list.filter(tr => tr.name.toLowerCase().includes(q))
  }

  return list
})

const filterTabs = computed(() => [
  { v: 'all', l: lang.value === 'km' ? 'ទាំងអស់' : 'All', count: props.totalTrainers },
  { v: 'present', l: lang.value === 'km' ? 'វត្តមាន' : 'Present', count: props.presentCount },
  { v: 'absent', l: lang.value === 'km' ? 'អវត្តមាន' : 'Absent', count: absentCount.value },
])
</script>

<template>
  <div class="w-full p-6 sm:p-8">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
      <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2.5">
        <span class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        {{ t.trainer_attendance_title }}
      </h1>

      <div class="flex items-center gap-2 flex-wrap">
        <input
          v-model="dateInput"
          @change="goToDate"
          type="date"
          :max="new Date().toISOString().split('T')[0]"
          class="text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
        />
        <button
          @click="exportCsv"
          class="flex items-center gap-1.5 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium rounded-lg px-4 py-2.5 transition-all duration-200 hover:border-emerald-400 hover:text-emerald-500"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          CSV
        </button>
        <Link
          href="/dashboard/trainer-attendance/scan"
          class="flex items-center gap-1.5 text-sm bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-4 py-2.5 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:-translate-y-0.5"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m0 0h.01M8 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4z"/></svg>
          {{ t.trainer_attendance_mode_scanner ?? (lang === 'km' ? 'ស្កេន' : 'Scan') }}
        </Link>
      </div>
    </div>

    <!-- Viewing-past-date banner -->
    <div
      v-if="!isToday"
      class="flex items-center gap-2 bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30 text-amber-700 dark:text-amber-400 text-sm rounded-lg px-4 py-3 mb-5"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      {{ lang === 'km' ? 'អ្នកកំពុងមើលកំណត់ត្រាថ្ងៃមុន — មិនអាច check-in/check-out បានទេ' : "You're viewing a past date — check-in/out is disabled" }}
    </div>

    <!-- Flash messages -->
    <div
      v-if="page.props.flash?.success"
      class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-5"
    >
      {{ page.props.flash.success }}
    </div>
    <div
      v-if="page.props.errors && Object.keys(page.props.errors).length"
      class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-sm rounded-lg px-4 py-3 mb-5"
    >
      {{ Object.values(page.props.errors)[0] }}
    </div>

    <!-- STATS ROW -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500 shrink-0">
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <p class="text-xs text-slate-400">{{ t.trainer_attendance_present_today }}</p>
        </div>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ presentCount }}<span class="text-sm font-normal text-slate-400">/{{ totalTrainers }}</span></p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 shrink-0">
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </div>
          <p class="text-xs text-slate-400">{{ lang === 'km' ? 'អវត្តមាន' : 'Absent' }}</p>
        </div>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ absentCount }}</p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-500 shrink-0">
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
          </div>
          <p class="text-xs text-slate-400">{{ lang === 'km' ? 'អត្រាមកធ្វើការ' : 'Attendance rate' }}</p>
        </div>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ attendanceRate }}%</p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-500 shrink-0">
            <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <p class="text-xs text-slate-400">{{ lang === 'km' ? 'រយៈពេលជាមធ្យម' : 'Avg. duration' }}</p>
        </div>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ avgDuration ?? '—' }}</p>
      </div>
    </div>

    <!-- TRAINER ROSTER (search + filter + toggle check-in/out) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 mb-6">
      <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ lang === 'km' ? 'បញ្ជីគ្រូបង្វឹក' : 'Trainer roster' }}</p>

        <!-- Filter tabs -->
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
          <button
            v-for="tab in filterTabs"
            :key="tab.v"
            @click="rosterFilter = tab.v"
            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-150 flex items-center gap-1.5"
            :class="rosterFilter === tab.v
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
          >
            {{ tab.l }}
            <span
              class="text-[10px] px-1.5 py-0.5 rounded-full"
              :class="rosterFilter === tab.v ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400'"
            >{{ tab.count }}</span>
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="relative mb-4">
        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input
          v-model="rosterSearch"
          type="text"
          :placeholder="t.trainer_attendance_search_placeholder ?? (lang === 'km' ? 'ស្វែងរកគ្រូបង្វឹក...' : 'Search trainer...')"
          class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="trainer in filteredTrainers"
          :key="trainer.id"
          class="relative rounded-2xl p-4 flex flex-col items-center text-center gap-2.5 border-2 transition-all duration-200"
          :class="isActive(trainer.id)
            ? 'border-emerald-400 bg-emerald-50/50 dark:bg-emerald-500/5'
            : 'border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20'"
        >
          <span
            class="absolute top-3 right-3 w-2.5 h-2.5 rounded-full"
            :class="isActive(trainer.id) ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300 dark:bg-slate-600'"
          ></span>

          <img v-if="trainer.photo_url" :src="`${trainer.photo_url}`" class="w-16 h-16 rounded-full object-cover ring-2" :class="isActive(trainer.id) ? 'ring-emerald-400' : 'ring-slate-200 dark:ring-slate-700'" />
          <div v-else class="w-16 h-16 rounded-full bg-emerald-500 flex items-center justify-center text-white text-lg font-semibold ring-2" :class="isActive(trainer.id) ? 'ring-emerald-400' : 'ring-slate-200 dark:ring-slate-700'">
            {{ trainer.name?.[0]?.toUpperCase() }}
          </div>

          <div class="min-w-0 w-full">
            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ trainer.name }}</p>
            <p v-if="isToday && isActive(trainer.id)" class="text-xs text-emerald-500 mt-0.5">
              {{ t.trainer_attendance_working }} · {{ formatTime(latestRecord(trainer.id)?.checked_in_at) }}
            </p>
            <p v-else-if="!isToday && isPresent(trainer.id)" class="text-xs text-emerald-500 mt-0.5">
              {{ lang === 'km' ? 'បានចូលរួម' : 'Attended' }}
            </p>
            <p v-else class="text-xs text-slate-400 mt-0.5">{{ t.trainer_attendance_not_present }}</p>
            <p v-if="trainer.shift_start_time" class="text-[11px] text-slate-400 mt-0.5">
              {{ lang === 'km' ? 'ម៉ោងកំណត់' : 'Shift' }}: {{ trainer.shift_start_time.slice(0, 5) }}
            </p>
          </div>

          <button
            v-if="isToday && isActive(trainer.id)"
            @click="checkOut(trainer)"
            class="w-full px-3 py-2 rounded-lg bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-400 text-xs font-medium hover:bg-red-100 dark:hover:bg-red-500/20 transition-colors duration-150"
          >
            {{ t.trainer_attendance_checkout }}
          </button>
          <button
            v-else-if="isToday"
            @click="checkIn(trainer)"
            class="w-full px-3 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-medium transition-colors duration-150"
          >
            {{ t.trainer_attendance_checkin }}
          </button>
          <span v-else class="w-full px-3 py-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-slate-400 text-xs text-center">
            {{ isPresent(trainer.id) ? (lang === 'km' ? 'បានចូលរួម' : 'Attended') : (lang === 'km' ? 'អវត្តមាន' : 'Absent') }}
          </span>
        </div>
        <p v-if="!filteredTrainers.length" class="col-span-full text-center text-slate-400 py-10">
          {{ rosterSearch.trim() || rosterFilter !== 'all' ? (lang === 'km' ? 'រកមិនឃើញលទ្ធផល' : 'No matching trainers') : t.trainer_empty }}
        </p>
      </div>
    </div>

    <!-- LOG -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ t.trainer_attendance_today_log }}</p>
        <span class="text-xs text-slate-400">{{ todayAttendance.length }} {{ lang === 'km' ? 'កំណត់ត្រា' : 'records' }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-slate-400 border-b border-slate-100 dark:border-slate-800">
              <th class="px-5 py-3 font-normal">{{ t.trainer_name }}</th>
              <th class="px-5 py-3 font-normal">{{ t.trainer_attendance_checkin_time }}</th>
              <th class="px-5 py-3 font-normal">{{ t.trainer_attendance_checkout_time }}</th>
              <th class="px-5 py-3 font-normal">{{ t.trainer_attendance_duration }}</th>
              <th class="px-5 py-3 font-normal">{{ lang === 'km' ? 'ស្ថានភាព' : 'Status' }}</th>
              <th class="px-5 py-3 font-normal text-right">{{ t.team_table_actions }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in todayAttendance" :key="record.id" class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors duration-150">
              <td class="px-5 py-3 text-slate-900 dark:text-white font-medium">{{ record.trainer.name }}</td>
              <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ formatTime(record.checked_in_at) }}</td>
              <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                <span v-if="record.checked_out_at">{{ formatTime(record.checked_out_at) }}</span>
                <span v-else>—</span>
              </td>
              <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ duration(record.checked_in_at, record.checked_out_at) ?? '—' }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span
                    class="text-xs font-medium px-2.5 py-1 rounded-full"
                    :class="record.checked_out_at
                      ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'
                      : 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'"
                  >
                    {{ record.checked_out_at ? (lang === 'km' ? 'បានចេញ' : 'Finished') : t.trainer_attendance_working }}
                  </span>
                  <span v-if="record.is_late" class="text-xs font-medium px-2 py-1 rounded-full bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400">
                    {{ lang === 'km' ? 'យឺត' : 'Late' }}
                  </span>
                </div>
              </td>
              <td class="px-5 py-3 text-right">
                <button @click="deleteRecord(record)" class="text-red-500 dark:text-red-400 hover:text-red-600 text-sm font-medium transition-colors duration-150">
                  {{ t.team_remove }}
                </button>
              </td>
            </tr>
            <tr v-if="!todayAttendance.length">
              <td colspan="6" class="px-5 py-10 text-center text-slate-400">{{ t.trainer_attendance_empty }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>