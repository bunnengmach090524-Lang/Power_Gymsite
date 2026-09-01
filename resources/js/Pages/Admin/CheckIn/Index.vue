<script setup>
import { ref, computed } from 'vue'
import { router, usePage, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

defineOptions({ layout: AdminLayout })

const props = defineProps({
  todayCheckIns: Array,
  todayCount: Number,
  checkInRate: Number,
  totalActiveMembers: Number,
  weeklyTrend: Array,
  monthlyTrend: Array,
})

const { t, lang } = useLang()
const page = usePage()

const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const searchOpen = ref(false)
let searchTimer = null

function onSearchInput() {
  clearTimeout(searchTimer)
  if (searchQuery.value.trim().length < 2) {
    searchResults.value = []
    searchOpen.value = false
    return
  }
  searchTimer = setTimeout(async () => {
    searchLoading.value = true
    try {
      const res = await fetch(`/dashboard/check-in/search?q=${encodeURIComponent(searchQuery.value)}`)
      searchResults.value = await res.json()
      searchOpen.value = true
    } finally {
      searchLoading.value = false
    }
  }, 300)
}

function checkIn(member) {
  useForm({ member_id: member.id }).post('/dashboard/check-in', {
    preserveScroll: true,
    onSuccess: () => {
      searchQuery.value = ''
      searchResults.value = []
      searchOpen.value = false
    },
  })
}

function undoCheckIn(checkIn) {
  if (confirm(t.value.checkin_confirm_undo)) {
    router.delete(`/dashboard/check-in/${checkIn.id}`, { preserveScroll: true })
  }
}

function formatTime(dateStr) {
  return new Date(dateStr).toLocaleTimeString(lang.value === 'km' ? 'km-KH' : 'en-US', {
    hour: '2-digit', minute: '2-digit',
  })
}

function subscriptionBadge(member) {
  if (member.subscription_status === 'none') {
    return { label: lang.value === 'km' ? 'គ្មាន subscription សកម្ម' : 'No active plan', class: 'bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-400' }
  }
  if (member.subscription_status === 'expired') {
    return { label: lang.value === 'km' ? 'ផុតកំណត់' : 'Expired', class: 'bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-400' }
  }
  if (member.subscription_status === 'expiring') {
    const daysLeft = member.subscription_days_left
    const text = daysLeft <= 0
      ? (lang.value === 'km' ? 'ផុតកំណត់ថ្ងៃនេះ' : 'Expires today')
      : (lang.value === 'km' ? `ជិតផុតកំណត់ (${daysLeft}ថ្ងៃ)` : `Expiring in ${daysLeft}d`)
    return { label: text, class: 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400' }
  }
  return { label: lang.value === 'km' ? 'សកម្ម' : 'Active', class: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' }
}

const ringRadius = 42
const ringCircumference = 2 * Math.PI * ringRadius
const ringOffset = ringCircumference - (props.checkInRate / 100) * ringCircumference

// ===== CHECK-IN TRENDS CHART =====
const trendView = ref('weekly') // 'weekly' | 'monthly'

function weekdayLabel(dateStr) {
  const d = new Date(dateStr + 'T00:00:00')
  return new Intl.DateTimeFormat(lang.value === 'km' ? 'km-KH' : 'en-US', { weekday: 'short' }).format(d)
}

function dayOfMonthLabel(dateStr) {
  const d = new Date(dateStr + 'T00:00:00')
  return String(d.getDate())
}

const activeTrend = computed(() => (trendView.value === 'weekly' ? props.weeklyTrend : props.monthlyTrend) ?? [])

const trendTotal = computed(() => activeTrend.value.reduce((sum, d) => sum + d.count, 0))

const trendChartData = computed(() => ({
  labels: activeTrend.value.map((d) => (trendView.value === 'weekly' ? weekdayLabel(d.date) : dayOfMonthLabel(d.date))),
  datasets: [
    {
      label: lang.value === 'km' ? 'ចំនួន Check-in' : 'Check-ins',
      backgroundColor: '#10b981',
      hoverBackgroundColor: '#059669',
      borderRadius: 4,
      maxBarThickness: trendView.value === 'weekly' ? 40 : 18,
      data: activeTrend.value.map((d) => d.count),
    },
  ],
}))

const trendChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        title: (items) => {
          const d = activeTrend.value[items[0].dataIndex]
          return d ? d.date : ''
        },
      },
    },
  },
  scales: {
    x: { grid: { display: false } },
    y: { beginAtZero: true, ticks: { precision: 0 } },
  },
}))
</script>

<template>
  <div class="p-6 sm:p-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
        ✅ {{ t.checkin_title }}
      </h1>
      <Link
        href="/dashboard/check-in/scan"
        class="flex items-center gap-1.5 text-sm bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-4 py-2.5 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m0 0h.01M8 4h.01M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4z"/></svg>
        {{ t.trainer_attendance_mode_scanner ?? (lang === 'km' ? 'ស្កេន' : 'Scan') }}
      </Link>
    </div>

    <div
      v-if="page.props.flash?.success"
      class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-4"
    >
      {{ page.props.flash.success }}
    </div>
    <div
      v-if="page.props.flash?.warning"
      class="bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30 text-amber-700 dark:text-amber-400 text-sm rounded-lg px-4 py-3 mb-4"
    >
      {{ page.props.flash.warning }}
    </div>
    <div
      v-if="page.props.errors && Object.keys(page.props.errors).length"
      class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-sm rounded-lg px-4 py-3 mb-4"
    >
      {{ Object.values(page.props.errors)[0] }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- SEARCH + CHECK-IN -->
      <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <p class="text-sm font-medium text-slate-900 dark:text-white mb-3">{{ t.checkin_search_label }}</p>
        <div class="relative">
          <input
            v-model="searchQuery"
            @input="onSearchInput"
            type="text"
            :placeholder="t.checkin_search_placeholder"
            class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-base focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
          />
          <div v-if="searchLoading" class="absolute right-4 top-1/2 -translate-y-1/2">
            <svg class="animate-spin w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
          </div>

          <div
            v-if="searchOpen && searchResults.length"
            class="absolute z-20 mt-2 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl max-h-80 overflow-y-auto"
          >
            <div
              v-for="member in searchResults"
              :key="member.id"
              class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-700 last:border-0"
            >
              <div class="flex items-center gap-3 min-w-0">
                <img
                  v-if="member.photo_url"
                  :src="`${member.photo_url}`"
                  class="w-10 h-10 rounded-full object-cover shrink-0"
                />
                <div v-else class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
                  {{ member.name?.[0]?.toUpperCase() }}
                </div>
                <div class="min-w-0">
                  <p class="text-slate-900 dark:text-white font-medium truncate">{{ member.name }}</p>
                  <p class="text-xs text-slate-400">{{ member.phone ?? '—' }}</p>
                  <span
                    class="inline-block mt-1 text-[11px] font-medium px-2 py-0.5 rounded-full"
                    :class="subscriptionBadge(member).class"
                  >{{ subscriptionBadge(member).label }}</span>
                </div>
              </div>
              <button
                v-if="!member.already_checked_in && member.subscription_status !== 'expired'"
                @click="checkIn(member)"
                class="shrink-0 px-3.5 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium transition-colors"
              >
                {{ t.checkin_button }}
              </button>
              <span v-else-if="member.already_checked_in" class="shrink-0 text-xs text-emerald-500 font-medium">
                ✓ {{ t.checkin_already_done }}
              </span>
              <span v-else class="shrink-0 text-xs text-red-500 font-medium">
                {{ t.checkin_no_active_subscription }}
              </span>
            </div>
          </div>
          <div
            v-else-if="searchOpen && !searchLoading"
            class="absolute z-20 mt-2 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl p-4 text-center text-sm text-slate-400"
          >
            {{ t.checkin_no_results }}
          </div>
        </div>
      </div>

      <!-- CHECK-IN RATE RING -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col items-center justify-center">
        <div class="relative w-28 h-28">
          <svg class="w-28 h-28 -rotate-90" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" class="text-slate-100 dark:text-slate-800" stroke-width="10" />
            <circle
              cx="50" cy="50" r="42" fill="none" stroke="#10b981" stroke-width="10" stroke-linecap="round"
              :stroke-dasharray="ringCircumference" :stroke-dashoffset="ringOffset"
              style="transition: stroke-dashoffset 0.6s ease"
            />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-xl font-bold text-slate-900 dark:text-white">{{ checkInRate }}%</span>
          </div>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 text-center">{{ t.checkin_rate_label }}</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ todayCount }}/{{ totalActiveMembers }}</p>
      </div>
    </div>

    <!-- CHECK-IN TRENDS CHART -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 mb-6">
      <div class="flex items-center justify-between mb-1 flex-wrap gap-3">
        <div>
          <p class="text-sm font-medium text-slate-900 dark:text-white">
            📈 {{ lang === 'km' ? 'និន្នាការ Check-in' : 'Check-in Trends' }}
          </p>
          <p class="text-xs text-slate-400 mt-0.5">
            {{ lang === 'km' ? 'សរុប' : 'Total' }}: {{ trendTotal }} {{ lang === 'km' ? 'Check-in' : 'check-ins' }}
            {{ trendView === 'weekly' ? (lang === 'km' ? 'ក្នុង 7 ថ្ងៃចុងក្រោយ' : 'in the last 7 days') : (lang === 'km' ? 'ក្នុងខែនេះ' : 'this month') }}
          </p>
        </div>
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
          <button
            @click="trendView = 'weekly'"
            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200"
            :class="trendView === 'weekly' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
          >
            {{ lang === 'km' ? 'ប្រចាំសប្តាហ៍' : 'Weekly' }}
          </button>
          <button
            @click="trendView = 'monthly'"
            class="px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200"
            :class="trendView === 'monthly' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
          >
            {{ lang === 'km' ? 'ប្រចាំខែ' : 'Monthly' }}
          </button>
        </div>
      </div>
      <div class="h-64 mt-4">
        <Bar :data="trendChartData" :options="trendChartOptions" />
      </div>
    </div>

    <!-- TODAY'S LIST -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ t.checkin_today_list }} ({{ todayCount }})</p>
      </div>
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-slate-400 border-b border-slate-100 dark:border-slate-800">
            <th class="px-5 py-3 font-normal">{{ t.table_name }}</th>
            <th class="px-5 py-3 font-normal">{{ t.checkin_time }}</th>
            <th class="px-5 py-3 font-normal">{{ t.checkin_by_staff }}</th>
            <th class="px-5 py-3 font-normal text-right">{{ t.team_table_actions }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ci in todayCheckIns" :key="ci.id" class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-5 py-3 text-slate-900 dark:text-white">{{ ci.member.name }}</td>
            <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ formatTime(ci.checked_in_at) }}</td>
            <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ ci.staff?.name ?? '—' }}</td>
            <td class="px-5 py-3 text-right">
              <button @click="undoCheckIn(ci)" class="text-red-500 dark:text-red-400 hover:text-red-600 text-sm font-medium">
                {{ t.checkin_undo }}
              </button>
            </td>
          </tr>
          <tr v-if="!todayCheckIns.length">
            <td colspan="4" class="px-5 py-10 text-center text-slate-400">{{ t.checkin_empty }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>