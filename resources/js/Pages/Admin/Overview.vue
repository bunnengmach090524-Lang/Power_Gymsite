<script setup>
import { computed, ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement,
  PointElement, ArcElement, Title, Tooltip, Legend, Filler,
} from 'chart.js'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import { useLang } from '../../composables/useLang'
import { useTheme } from '../../composables/useTheme'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler)

defineOptions({ layout: AdminLayout })

const props = defineProps({
  stats: Object,
  recentMembers: Array,
  chartData: Object,
  range: String,
  nextExpiring: Object,
  renewalRate: Number,
  membersByPlan: Array,
  todayClassSpotlight: Object,
  todayClasses: Array,
  selectedDay : String, 
})

const { t, lang } = useLang()
const { isDark } = useTheme()

const cards = [
  { key: 'overview_total_members', value: () => props.stats.totalMembers, icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3.13a4 4 0 00-8 0', bg: 'bg-blue-50 dark:bg-blue-500/10', fg: 'text-blue-500' },
  { key: 'overview_expiring_soon', value: () => props.stats.expiringSoon, icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', bg: 'bg-amber-50 dark:bg-amber-500/10', fg: 'text-amber-500' },
  { key: 'overview_monthly_revenue', value: () => `$${props.stats.monthlyRevenue}`, icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 12h11M17 9l3 3-3 3', bg: 'bg-emerald-50 dark:bg-emerald-500/10', fg: 'text-emerald-500' },
  { key: 'overview_active_subscriptions', value: () => props.stats.activeSubscriptions, icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', bg: 'bg-violet-50 dark:bg-violet-500/10', fg: 'text-violet-500' },
]

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const loadingChart = ref(false)
function switchRange(newRange) {
  loadingChart.value = true
  router.get('/dashboard', { range: newRange }, {
    only: ['chartData', 'range'],
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { loadingChart.value = false },
  })
}

const dayTabs = [
  { key: 'mon', en: 'Mon', km: 'ច័ន្ទ' },
  { key: 'tue', en: 'Tue', km: 'អង្គារ' },
  { key: 'wed', en: 'Wed', km: 'ពុធ' },
  { key: 'thu', en: 'Thu', km: 'ព្រហ.' },
  { key: 'fri', en: 'Fri', km: 'សុក្រ' },
  { key: 'sat', en: 'Sat', km: 'សៅរ៍' },
  { key: 'sun', en: 'Sun', km: 'អាទិត្យ' },
]

const loadingClasses = ref(false)
function switchDay(newDay) {
  loadingClasses.value = true
  router.get('/dashboard', { day: newDay }, {
    only: ['todayClasses', 'selectedDay'],
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { loadingClasses.value = false },
  })
}

// "Starting soon" badge only applies to the day tab that matches today's
// real-world date — otherwise a class shown on Tuesday could wrongly flash
// "starting soon" while browsing on a Sunday.
const todayAbbr = computed(() => ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][new Date().getDay()])

function isStartingSoon(cls) {
  if (props.selectedDay !== todayAbbr.value || !cls.startTime) return false
  const [h, m] = cls.startTime.split(':').map(Number)
  const start = new Date()
  start.setHours(h, m, 0, 0)
  const diffMin = (start - new Date()) / 60000
  return diffMin >= 0 && diffMin <= 20
}

const gridColor = computed(() => (isDark.value ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.15)'))
const tickColor = computed(() => (isDark.value ? '#94a3b8' : '#64748b'))

const revenueChartData = computed(() => ({
  labels: props.chartData.labels,
  datasets: [{
    label: t.value.overview_chart_revenue,
    data: props.chartData.revenue,
    borderColor: '#10b981',
    backgroundColor: 'rgba(16,185,129,0.15)',
    fill: true, tension: 0.35,
    pointBackgroundColor: '#10b981', pointRadius: 4, pointHoverRadius: 6,
  }],
}))

const revenueChartOptions = computed(() => ({
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: tickColor.value } },
    y: { grid: { color: gridColor.value }, ticks: { color: tickColor.value, callback: (v) => `$${v}` } },
  },
}))

const membersChartData = computed(() => ({
  labels: props.chartData.labels,
  datasets: [{
    label: t.value.overview_chart_new_members,
    data: props.chartData.newMembers,
    backgroundColor: '#3b82f6', borderRadius: 6, barThickness: 22,
  }],
}))

const membersChartOptions = computed(() => ({
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: tickColor.value } },
    y: { grid: { color: gridColor.value }, ticks: { color: tickColor.value, stepSize: 1 } },
  },
}))

const ringRadius = 42
const ringCircumference = 2 * Math.PI * ringRadius
const ringOffset = computed(() => ringCircumference - (props.renewalRate / 100) * ringCircumference)

const planColors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6']
const planDonutData = computed(() => ({
  labels: props.membersByPlan.map(p => p.label),
  datasets: [{
    data: props.membersByPlan.map(p => p.count),
    backgroundColor: planColors,
    borderWidth: 0,
  }],
}))
const planDonutOptions = computed(() => ({
  responsive: true, maintainAspectRatio: false,
  cutout: '65%',
  plugins: { legend: { position: 'bottom', labels: { color: tickColor.value, boxWidth: 10, padding: 12, font: { size: 11 } } } },
}))
</script>

<template>
  <div class="p-6 sm:p-8">
    <div class="mb-6">
      <h1 class="text-xl font-semibold text-slate-900 dark:text-white">📊 {{ t.overview_title }}</h1>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div
        v-for="(card, i) in cards" :key="i"
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
      >
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" :class="card.bg">
          <svg class="w-5 h-5" :class="card.fg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
          </svg>
        </div>
        <p class="text-xs text-slate-400 mb-1">{{ t[card.key] }}</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ card.value() }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div
        class="lg:col-span-2 relative overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-7 flex flex-col justify-center min-h-[160px]"
      >
        <div
          v-if="todayClassSpotlight?.imageUrl"
          class="absolute inset-0 bg-cover bg-center"
          :style="{ backgroundImage: `url(${todayClassSpotlight.imageUrl})` }"
        ></div>
        <div
          v-if="todayClassSpotlight?.imageUrl"
          class="absolute inset-0 bg-gradient-to-r from-white/95 via-white/85 to-white/50 dark:from-slate-900/95 dark:via-slate-900/85 dark:to-slate-900/50"
        ></div>

        <span
          v-if="todayClassSpotlight"
          class="relative z-10 inline-flex items-center gap-1.5 text-xs font-medium bg-blue-50 dark:bg-blue-500/10 text-blue-500 px-2.5 py-1 rounded-full w-fit mb-3"
        >
          🏋️ {{ lang === 'km' ? 'Class ថ្ងៃនេះ' : "Today's class" }} · {{ todayClassSpotlight.name }} · {{ todayClassSpotlight.startTime?.slice(0,5) }}
        </span>

        <template v-if="nextExpiring">
          <p class="relative z-10 text-xs uppercase tracking-wide text-slate-400 mb-1.5 flex items-center gap-1.5">
            ⏳ {{ t.overview_next_expiring_label }}
          </p>
          <h2 class="relative z-10 text-xl sm:text-2xl font-bold mb-2 text-slate-900 dark:text-white">{{ nextExpiring.memberName }}</h2>
          <div class="relative z-10 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">📦 {{ nextExpiring.planName }}</span>
            <span class="flex items-center gap-1.5">📅 {{ formatDate(nextExpiring.endDate) }}</span>
            <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 font-medium text-slate-600 dark:text-slate-300">
              {{ nextExpiring.daysLeft <= 0 ? t.overview_expires_today : `${nextExpiring.daysLeft} ${t.overview_days_left}` }}
            </span>
          </div>
        </template>
        <p v-else class="relative z-10 text-slate-400">{{ t.overview_no_expiring_soon }}</p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col items-center justify-center">
        <div class="relative w-28 h-28">
          <svg class="w-28 h-28 -rotate-90" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="42" fill="none" :stroke="isDark ? '#1e293b' : '#e2e8f0'" stroke-width="10" />
            <circle
              cx="50" cy="50" r="42" fill="none" stroke="#8b5cf6" stroke-width="10" stroke-linecap="round"
              :stroke-dasharray="ringCircumference" :stroke-dashoffset="ringOffset"
              style="transition: stroke-dashoffset 0.6s ease"
            />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-xl font-bold text-slate-900 dark:text-white">{{ renewalRate }}%</span>
          </div>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 text-center">{{ t.overview_renewal_rate }}</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ stats.activeSubscriptions }}/{{ stats.totalMembers }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 mb-6">
      <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <p class="text-sm font-medium text-slate-900 dark:text-white flex items-center gap-2">
          <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </span>
          {{ lang === 'km' ? 'Classes' : 'Classes' }}
          <span class="text-xs font-normal text-slate-400">({{ todayClasses.length }})</span>
        </p>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1 overflow-x-auto max-w-full">
            <button
              v-for="tab in dayTabs" :key="tab.key"
              @click="switchDay(tab.key)"
              class="px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors whitespace-nowrap"
              :class="selectedDay === tab.key ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
            >
              {{ lang === 'km' ? tab.km : tab.en }}
            </button>
          </div>
          <Link
            href="/dashboard/classes/create"
            class="inline-flex items-center gap-1 text-xs font-medium bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap"
          >
            + {{ lang === 'km' ? 'បន្ថែម Class' : 'Add Class' }}
          </Link>
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loadingClasses" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="i in 3" :key="i" class="bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-xl p-3.5 animate-pulse">
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3 mb-2"></div>
          <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/2 mb-3"></div>
          <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/3 mb-3"></div>
          <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded"></div>
        </div>
      </div>

      <div v-else-if="todayClasses.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-72 overflow-y-auto pr-1">
        <div
          v-for="cls in todayClasses" :key="cls.id"
          class="relative bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-xl p-3.5 flex flex-col gap-2 hover:border-blue-200 dark:hover:border-blue-500/30 transition-colors"
        >
          <span
            v-if="isStartingSoon(cls)"
            class="absolute -top-2 -right-2 inline-flex items-center gap-1 text-[10px] font-semibold bg-red-500 text-white px-2 py-0.5 rounded-full shadow-sm animate-pulse"
          >
            🔴 {{ lang === 'km' ? 'ជិតចាប់ផ្តើម' : 'Starting soon' }}
          </span>
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-semibold text-slate-900 dark:text-white truncate">{{ cls.name }}</p>
              <p class="text-xs text-slate-400 mt-0.5">{{ cls.startTime?.slice(0,5) }} - {{ cls.endTime?.slice(0,5) }}</p>
              <p v-if="cls.trainerName" class="text-xs text-slate-400 mt-1 flex items-center gap-1">🧑‍🏫 {{ cls.trainerName }}</p>
            </div>
            <span class="shrink-0 text-xs bg-blue-50 dark:bg-blue-500/10 text-blue-500 px-2 py-1 rounded-full font-medium">👥 {{ cls.capacity }}</span>
          </div>
          <Link
            :href="`/dashboard/classes/${cls.id}/edit`"
            class="mt-1 inline-flex items-center justify-center gap-1 text-xs font-medium border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg py-1.5 transition-colors"
          >
            {{ lang === 'km' ? 'មើល / កែ' : 'View / Edit' }}
          </Link>
        </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center py-8 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
          <svg class="w-7 h-7 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <p class="text-slate-400 text-sm">{{ lang === 'km' ? 'មិនមាន class កំណត់សម្រាប់ថ្ងៃនេះទេ' : 'No classes scheduled for this day.' }}</p>
        <Link
          href="/dashboard/classes/create"
          class="mt-3 text-xs font-medium text-blue-500 hover:text-blue-600"
        >
          + {{ lang === 'km' ? 'បង្កើត Class ថ្មី' : 'Create a new class' }}
        </Link>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
          <p class="text-sm font-medium text-slate-900 dark:text-white flex items-center gap-1.5">📈 {{ t.overview_chart_revenue_title }}</p>
          <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
            <button
              v-for="opt in ['daily', 'monthly', 'yearly']" :key="opt"
              @click="switchRange(opt)"
              class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors"
              :class="range === opt ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
            >
              {{ t[`overview_range_${opt}`] }}
            </button>
          </div>
        </div>
        <div class="h-64">
          <div v-if="loadingChart" class="h-full flex items-end gap-2 px-2 animate-pulse">
            <div v-for="i in 8" :key="i" class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-t" :style="{ height: (30 + (i % 4) * 15) + '%' }"></div>
          </div>
          <Line v-else :data="revenueChartData" :options="revenueChartOptions" />
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <p class="text-sm font-medium text-slate-900 dark:text-white mb-4">🥧 {{ t.overview_members_by_plan }}</p>
        <div v-if="membersByPlan.length" class="h-56">
          <Doughnut :data="planDonutData" :options="planDonutOptions" />
        </div>
        <div v-else class="h-56 flex items-center justify-center">
          <p class="text-sm text-slate-400">{{ t.overview_no_plan_data }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 mb-6">
      <p class="text-sm font-medium text-slate-900 dark:text-white mb-4">{{ t.overview_chart_members_title }}</p>
      <div class="h-56"><Bar :data="membersChartData" :options="membersChartOptions" /></div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ t.overview_recent_members }}</p>
      </div>
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-slate-400 border-b border-slate-100 dark:border-slate-800">
            <th class="px-5 py-3 font-normal">{{ t.table_name }}</th>
            <th class="px-5 py-3 font-normal">{{ t.table_phone }}</th>
            <th class="px-5 py-3 font-normal">{{ t.table_joined }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in recentMembers" :key="member.id" class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
            <td class="px-5 py-3 text-slate-900 dark:text-white">{{ member.name }}</td>
            <td class="px-5 py-3 text-slate-400">{{ member.phone ?? '—' }}</td>
            <td class="px-5 py-3 text-slate-400">{{ formatDate(member.joined_date) }}</td>
          </tr>
          <tr v-if="!recentMembers.length">
            <td colspan="3" class="px-5 py-10 text-center text-slate-400">{{ t.overview_no_members }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>