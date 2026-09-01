<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  month: String,
  range: String,
  compare: String,
  summary: Object,
  byStaff: Array,
  trend: Array,
  oldPendingAlert: Object,
})

const { lang } = useLang()

const selectedMonth = ref(props.month)
const selectedRange = ref(props.range)
const selectedCompare = ref(props.compare)
const expandedStaff = ref(null)

const ranges = [
  { value: 'month', km: '1 ខែ', en: '1 Month' },
  { value: '6months', km: '6 ខែ', en: '6 Months' },
  { value: 'year', km: '1 ឆ្នាំ', en: '1 Year' },
]

function reload() {
  router.get('/dashboard/salary-report', {
    month: selectedMonth.value,
    range: selectedRange.value,
    compare: selectedCompare.value,
  }, { preserveState: true })
}

function setRange(r) {
  selectedRange.value = r
  reload()
}

function setCompare(c) {
  selectedCompare.value = c
  reload()
}

function toggleStaff(id) {
  expandedStaff.value = expandedStaff.value === id ? null : id
}

function fmt(n) {
  return '$' + Number(n ?? 0).toFixed(2)
}

function monthLabel(ym) {
  const [y, m] = ym.split('-')
  const d = new Date(y, m - 1, 1)
  return d.toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', { month: 'short', year: '2-digit' })
}

const ratioColor = computed(() => {
  const r = props.summary.ratio
  if (r === null) return 'text-slate-400'
  if (r > 100) return 'text-red-500'
  if (r > 50) return 'text-amber-500'
  return 'text-emerald-500'
})

const netColor = computed(() => (props.summary.net >= 0 ? 'text-emerald-500' : 'text-red-500'))

function growthLabel(value) {
  if (value === null) return null
  const sign = value >= 0 ? '+' : ''
  return `${sign}${value}%`
}
function growthColor(value) {
  if (value === null) return 'text-slate-400'
  return value >= 0 ? 'text-emerald-500' : 'text-red-500'
}
function growthIcon(value) {
  if (value === null) return null
  return value >= 0 ? '↑' : '↓'
}

// Independent per-metric scales so a small revenue value next to a large
// salary value (or vice versa) is still readable.
const maxRevenue = computed(() => Math.max(1, ...props.trend.map(t => t.revenue)))
const maxSalary = computed(() => Math.max(1, ...props.trend.map(t => t.salary)))
function revenueBarHeight(value) {
  return Math.max(4, Math.round((value / maxRevenue.value) * 100))
}
function salaryBarHeight(value) {
  return Math.max(4, Math.round((value / maxSalary.value) * 100))
}

const showRatioAlert = computed(() => props.summary.ratio !== null && props.summary.ratio > 60)
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
        {{ lang === 'km' ? 'របាយការណ៍ប្រាក់ខែ' : 'Salary Report' }}
      </h1>

      <div class="flex flex-wrap items-center gap-3">
        <div class="flex p-1 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
          <button
            v-for="r in ranges"
            :key="r.value"
            @click="setRange(r.value)"
            class="px-3.5 py-1.5 rounded-md text-sm font-medium transition-all duration-200"
            :class="selectedRange === r.value
              ? 'bg-emerald-500 text-white shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
          >
            {{ lang === 'km' ? r.km : r.en }}
          </button>
        </div>

        <input
          v-model="selectedMonth"
          @change="reload"
          type="month"
          class="px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
        />

        <a
          :href="`/dashboard/salary-report/export?month=${selectedMonth}&range=${selectedRange}`"
          class="px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
          </svg>
          CSV
        </a>
      </div>
    </div>

    <!-- Old pending payment alert (always shown, independent of period filter) -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div v-if="oldPendingAlert" class="mb-4 flex items-start gap-3 bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 rounded-xl px-4 py-3">
        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.052 3.37c.866-1.5 3.03-1.5 3.896 0l7.355 12.75zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <p class="text-sm text-red-700 dark:text-red-400">
          {{ lang === 'km'
            ? `${oldPendingAlert.count} ការបង់ប្រាក់ខែនៅមិនទាន់បង់ជាង 30ថ្ងៃ — ចាស់បំផុត: ${oldPendingAlert.oldest_name} (${oldPendingAlert.oldest_days} ថ្ងៃ) សរុប ${fmt(oldPendingAlert.total)}`
            : `${oldPendingAlert.count} salary payment(s) pending over 30 days — oldest: ${oldPendingAlert.oldest_name} (${oldPendingAlert.oldest_days}d), totaling ${fmt(oldPendingAlert.total)}` }}
        </p>
      </div>
    </Transition>

    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div v-if="showRatioAlert" class="mb-6 flex items-start gap-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30 rounded-xl px-4 py-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-amber-700 dark:text-amber-400">
          {{ lang === 'km'
            ? `ប្រាក់ខែកំពុងស៊ីចំណូល ${summary.ratio}% ដែលខ្ពស់ជាងធម្មតា — គួរពិនិត្យមើលឡើងវិញ។`
            : `Salary is consuming ${summary.ratio}% of revenue — higher than typical, worth reviewing.` }}
        </p>
      </div>
    </Transition>

    <!-- Compare-against toggle -->
    <div class="flex items-center gap-2 mb-4 text-xs">
      <span class="text-slate-400">{{ lang === 'km' ? 'ធៀបនឹង:' : 'Compare vs:' }}</span>
      <button
        @click="setCompare('prior')"
        class="px-2.5 py-1 rounded-full font-medium transition-colors"
        :class="selectedCompare === 'prior' ? 'bg-slate-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
      >
        {{ lang === 'km' ? 'ចន្លោះពេលមុន' : 'Prior period' }}
      </button>
      <button
        @click="setCompare('yoy')"
        class="px-2.5 py-1 rounded-full font-medium transition-colors"
        :class="selectedCompare === 'yoy' ? 'bg-slate-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
      >
        {{ lang === 'km' ? 'ឆ្នាំមុន (ខែដូចគ្នា)' : 'Same period last year' }}
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ចំណូលសរុប' : 'Total Revenue' }}</p>
        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ fmt(summary.revenue) }}</p>
        <p v-if="summary.revenue_growth !== null" class="text-xs mt-1" :class="growthColor(summary.revenue_growth)">
          {{ growthIcon(summary.revenue_growth) }} {{ growthLabel(summary.revenue_growth) }}
        </p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ប្រាក់ខែសរុប' : 'Total Salary' }}</p>
        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ fmt(summary.total_salary) }}</p>
        <p class="text-[11px] text-slate-400 mt-1">
          {{ summary.paid_count }} {{ lang === 'km' ? 'បានបង់' : 'paid' }} / {{ summary.pending_count }} {{ lang === 'km' ? 'មិនទាន់' : 'pending' }}
        </p>
        <p v-if="summary.salary_growth !== null" class="text-xs mt-1" :class="growthColor(-summary.salary_growth)">
          {{ growthIcon(summary.salary_growth) }} {{ growthLabel(summary.salary_growth) }}
        </p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ចំណេញសុទ្ធ' : 'Net (Rev − Salary)' }}</p>
        <p class="text-xl font-bold" :class="netColor">{{ fmt(summary.net) }}</p>
        <p v-if="summary.net_growth !== null" class="text-xs mt-1" :class="growthColor(summary.net_growth)">
          {{ growthIcon(summary.net_growth) }} {{ growthLabel(summary.net_growth) }}
        </p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'សមាមាត្រ ប្រាក់ខែ/ចំណូល' : 'Salary / Revenue' }}</p>
        <p class="text-xl font-bold" :class="ratioColor">
          {{ summary.ratio !== null ? summary.ratio + '%' : '—' }}
        </p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ជាមធ្យមក្នុងមួយនាក់' : 'Avg per Staff' }}</p>
        <p class="text-xl font-bold text-slate-900 dark:text-white">{{ fmt(summary.avg_per_staff) }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ summary.staff_count }} {{ lang === 'km' ? 'នាក់' : 'staff' }}</p>
      </div>
    </div>

    <!-- Top earner -->
    <div v-if="summary.top_earner" class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-5 mb-8 flex items-center gap-4">
      <img v-if="summary.top_earner.photo_url" :src="summary.top_earner.photo_url" class="w-12 h-12 rounded-full object-cover ring-2 ring-white/40" />
      <div v-else class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white font-bold">
        {{ summary.top_earner.name?.[0]?.toUpperCase() ?? '?' }}
      </div>
      <div>
        <p class="text-emerald-50 text-xs">{{ lang === 'km' ? 'ទទួលប្រាក់ខែខ្ពស់បំផុត' : "Top earner" }}</p>
        <p class="text-white font-semibold">{{ summary.top_earner.name }} — {{ fmt(summary.top_earner.total) }}</p>
      </div>
    </div>

    <!-- Trend chart: only meaningful with more than one data point -->
    <div v-if="trend.length > 1" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 mb-8">
      <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">
        {{ lang === 'km' ? 'និន្នាការ' : 'Trend' }}
      </h2>
      <div class="flex items-end justify-between gap-2 h-48 overflow-x-auto">
        <div v-for="t in trend" :key="t.month" class="flex-1 min-w-[56px] flex flex-col items-center gap-1.5">
          <div class="w-full flex items-end justify-center gap-1.5 h-36">
            <div class="w-1/2 flex flex-col items-center justify-end h-full">
              <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-medium mb-1 whitespace-nowrap">{{ fmt(t.revenue) }}</span>
              <div
                class="w-full bg-cyan-400 rounded-t transition-all duration-300"
                :style="{ height: revenueBarHeight(t.revenue) + '%' }"
              ></div>
            </div>
            <div class="w-1/2 flex flex-col items-center justify-end h-full">
              <span class="text-[10px] text-rose-500 dark:text-rose-400 font-medium mb-1 whitespace-nowrap">{{ fmt(t.salary) }}</span>
              <div
                class="w-full bg-rose-400 rounded-t transition-all duration-300"
                :style="{ height: salaryBarHeight(t.salary) + '%' }"
              ></div>
            </div>
          </div>
          <p class="text-[11px] text-slate-400">{{ monthLabel(t.month) }}</p>
        </div>
      </div>
      <div class="flex items-center gap-4 mt-4 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span>{{ lang === 'km' ? 'ចំណូល' : 'Revenue' }}</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>{{ lang === 'km' ? 'ប្រាក់ខែ' : 'Salary' }}</span>
        <span class="text-slate-400 italic">{{ lang === 'km' ? '(scale ដាច់ដោយឡែក)' : '(independent scales)' }}</span>
      </div>
    </div>
    <div v-else class="bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-6 mb-8 text-center text-sm text-slate-400">
      {{ lang === 'km' ? 'ជ្រើសរើស "6 ខែ" ឬ "1 ឆ្នាំ" ដើម្បីមើលនិន្នាការ' : 'Select "6 Months" or "1 Year" to see the trend' }}
    </div>

    <!-- Per-staff breakdown with drill-down -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
          {{ lang === 'km' ? 'តាមបុគ្គលិកម្នាក់ៗ' : 'Breakdown by Staff' }}
        </h2>
      </div>
      <div v-if="!byStaff.length" class="px-6 py-10 text-center text-slate-400 text-sm">
        {{ lang === 'km' ? 'មិនទាន់មានទិន្នន័យប្រាក់ខែសម្រាប់ចន្លោះពេលនេះទេ' : 'No salary data for this period yet' }}
      </div>
      <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
        <div v-for="s in byStaff" :key="s.staff_profile_id">
          <button
            @click="toggleStaff(s.staff_profile_id)"
            class="w-full flex items-center justify-between px-6 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors text-left"
          >
            <div class="flex items-center gap-3">
              <img v-if="s.photo_url" :src="s.photo_url" class="w-9 h-9 rounded-full object-cover" />
              <div v-else class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold">
                {{ s.name?.[0]?.toUpperCase() ?? '?' }}
              </div>
              <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ s.name }}</p>
                <p class="text-xs text-slate-400">{{ s.position }} · {{ s.payments.length }} {{ lang === 'km' ? 'ការបង់ប្រាក់' : 'payment(s)' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-right">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ fmt(s.total) }}</p>
                <span class="text-[11px] font-medium" :class="s.status === 'paid' ? 'text-emerald-500' : 'text-amber-500'">
                  {{ s.status === 'paid' ? (lang === 'km' ? 'បានបង់' : 'Paid') : (lang === 'km' ? 'មិនទាន់បង់' : 'Pending') }}
                </span>
              </div>
              <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="expandedStaff === s.staff_profile_id ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </button>

          <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-96" leave-active-class="transition-all duration-150 ease-in" leave-to-class="opacity-0 max-h-0">
            <div v-if="expandedStaff === s.staff_profile_id" class="bg-slate-50 dark:bg-slate-800/30 px-6 py-3 overflow-hidden">
              <div v-for="p in s.payments" :key="p.id" class="flex items-center justify-between py-2 text-sm border-b border-slate-100 dark:border-slate-800 last:border-0">
                <span class="text-slate-500 dark:text-slate-400 text-xs">{{ p.period_start }} → {{ p.period_end }}</span>
                <div class="flex items-center gap-3">
                  <span class="font-medium text-slate-800 dark:text-slate-100">{{ fmt(p.total) }}</span>
                  <span class="text-[11px]" :class="p.status === 'paid' ? 'text-emerald-500' : 'text-amber-500'">
                    {{ p.status === 'paid' ? (lang === 'km' ? 'បានបង់' : 'Paid') : (lang === 'km' ? 'មិនទាន់' : 'Pending') }}
                  </span>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
</style>