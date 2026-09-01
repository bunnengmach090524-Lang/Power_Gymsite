<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  startDate: String,
  endDate: String,
  stats: Object,
  revenueByPlan: Array,
  revenueByMethod: Array,
})

const startDate = ref(props.startDate)
const endDate = ref(props.endDate)

const statCards = [
  {
    key: 'totalRevenue',
    label: 'ចំណូលសរុប',
    format: (v) => `$${Number(v).toFixed(2)}`,
    icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 12h11M17 9l3 3-3 3',
    bg: 'bg-emerald-50 dark:bg-emerald-500/10', fg: 'text-emerald-500',
  },
  {
    key: 'totalPayments',
    label: 'ការទូទាត់សរុប',
    format: (v) => v,
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    bg: 'bg-blue-50 dark:bg-blue-500/10', fg: 'text-blue-500',
  },
  {
    key: 'newMembers',
    label: 'សមាជិកថ្មី',
    format: (v) => v,
    icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3.13a4 4 0 00-8 0',
    bg: 'bg-violet-50 dark:bg-violet-500/10', fg: 'text-violet-500',
  },
  {
    key: 'avgPayment',
    label: 'ការទូទាត់ជាមធ្យម',
    format: (v) => `$${Number(v).toFixed(2)}`,
    icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    bg: 'bg-amber-50 dark:bg-amber-500/10', fg: 'text-amber-500',
  },
]

function applyFilter() {
  router.get(
    '/dashboard/reports',
    { start_date: startDate.value, end_date: endDate.value },
    { preserveState: true, preserveScroll: true, replace: true }
  )
}

function exportCsv() {
  const params = new URLSearchParams({ start_date: startDate.value, end_date: endDate.value })
  window.location.href = `/dashboard/reports/export?${params.toString()}`
}

function printReport() {
  window.print()
}
</script>

<template>
  <div class="p-6 sm:p-8">
    <!-- PRINT-ONLY HEADER -->
    <div class="hidden print:block mb-4">
      <h1 class="text-lg font-bold">GymSite — របាយការណ៍ចំណូល</h1>
      <p class="text-sm text-slate-600">{{ startDate }} ដល់ {{ endDate }}</p>
    </div>

    <!-- ===== HEADER ===== -->
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
      <div>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2">
          📈 របាយការណ៍
        </h1>
        <p class="text-sm text-slate-400 mt-0.5">វិភាគទិន្នន័យលក់លទ្ធិក តាមរយៈពេលកំណត់</p>
      </div>

      <div class="flex items-center gap-2 no-print">
        <button
          @click="printReport"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z" />
          </svg>
          បោះពុម្ព / PDF
        </button>
        <button
          @click="exportCsv"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
          </svg>
          ទាញយក CSV
        </button>
      </div>
    </div>

    <!-- ===== DATE FILTER ===== -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 mb-6 flex flex-wrap items-end gap-3 no-print">
      <div>
        <label class="block text-xs text-slate-400 mb-1">ពីថ្ងៃ</label>
        <input
          type="date"
          v-model="startDate"
          class="border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">ដល់ថ្ងៃ</label>
        <input
          type="date"
          v-model="endDate"
          class="border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>
      <button
        @click="applyFilter"
        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm font-medium transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 8h12M9 12h6M11 16h2" />
        </svg>
        ត្រង
      </button>
    </div>

    <!-- ===== STAT CARDS ===== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div
        v-for="card in statCards"
        :key="card.key"
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
      >
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" :class="card.bg">
          <svg class="w-5 h-5" :class="card.fg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
          </svg>
        </div>
        <p class="text-xs text-slate-400 mb-1">{{ card.label }}</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ card.format(stats[card.key]) }}</p>
      </div>
    </div>

    <!-- ===== BREAKDOWN TABLES ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
          <p class="text-sm font-medium text-slate-900 dark:text-white">🎯 ចំណូលតាមកញ្ចប់សមាជិកភាព</p>
        </div>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-slate-400 border-b border-slate-100 dark:border-slate-800">
              <th class="px-5 py-3 font-normal">កញ្ចប់</th>
              <th class="px-5 py-3 font-normal text-right">ចំនួន</th>
              <th class="px-5 py-3 font-normal text-right">ចំណូល</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in revenueByPlan"
              :key="row.label"
              class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
            >
              <td class="px-5 py-3 text-slate-900 dark:text-white">{{ row.label }}</td>
              <td class="px-5 py-3 text-slate-400 text-right">{{ row.count }}</td>
              <td class="px-5 py-3 text-slate-900 dark:text-white text-right font-medium">${{ Number(row.total).toFixed(2) }}</td>
            </tr>
            <tr v-if="!revenueByPlan.length">
              <td colspan="3" class="px-5 py-10 text-center text-slate-400">មិនទាន់មានទិន្នន័យទេ</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
          <p class="text-sm font-medium text-slate-900 dark:text-white">💳 ចំណូលតាមមធ្យោបាយទូទាត់</p>
        </div>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-slate-400 border-b border-slate-100 dark:border-slate-800">
              <th class="px-5 py-3 font-normal">មធ្យោបាយ</th>
              <th class="px-5 py-3 font-normal text-right">ចំនួន</th>
              <th class="px-5 py-3 font-normal text-right">ចំណូល</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in revenueByMethod"
              :key="row.method"
              class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
            >
              <td class="px-5 py-3 text-slate-900 dark:text-white">{{ row.label }}</td>
              <td class="px-5 py-3 text-slate-400 text-right">{{ row.count }}</td>
              <td class="px-5 py-3 text-slate-900 dark:text-white text-right font-medium">${{ Number(row.total).toFixed(2) }}</td>
            </tr>
            <tr v-if="!revenueByMethod.length">
              <td colspan="3" class="px-5 py-10 text-center text-slate-400">មិនទាន់មានទិន្នន័យទេ</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>