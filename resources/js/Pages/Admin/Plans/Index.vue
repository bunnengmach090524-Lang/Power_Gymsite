<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  plans: Array,
  stats: Object,
})

const { lang } = useLang()

function destroyPlan(plan) {
  if (!confirm(lang.value === 'km' ? `លុប "${plan.name}" ចោល?` : `Delete "${plan.name}"?`)) return
  router.delete(`/dashboard/plans/${plan.id}`, { preserveScroll: true })
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <div class="flex items-start justify-between mb-6 flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white flex items-center gap-2">
          💳 {{ lang === 'km' ? 'គម្រោងសមាជិកភាព' : 'Membership Plans' }}
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          {{ lang === 'km' ? 'គ្រប់គ្រងគម្រោងតម្លៃសមាជិកភាពទាំងអស់' : 'Manage all membership pricing plans' }}
        </p>
      </div>
      <Link
        href="/dashboard/plans/create"
        class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium text-sm rounded-lg px-4 py-2.5 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20"
      >
        + {{ lang === 'km' ? 'បន្ថែម Plan' : 'Add Plan' }}
      </Link>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ចំនួន Plans' : 'Total Plans' }}</p>
        <p class="text-2xl font-bold text-emerald-500">{{ stats.totalPlans }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'តម្លៃមធ្យម' : 'Average Price' }}</p>
        <p class="text-2xl font-bold text-amber-500">${{ stats.avgPrice }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'សមាជិកសកម្មសរុប' : 'Active Subscribers' }}</p>
        <p class="text-2xl font-bold text-red-400">{{ stats.totalSubscribers }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-slate-400 border-b border-slate-100 dark:border-slate-800">
            <th class="px-5 py-3 font-normal">{{ lang === 'km' ? 'ឈ្មោះ' : 'Name' }}</th>
            <th class="px-5 py-3 font-normal">{{ lang === 'km' ? 'តម្លៃ' : 'Price' }}</th>
            <th class="px-5 py-3 font-normal">{{ lang === 'km' ? 'រយៈពេល' : 'Duration' }}</th>
            <th class="px-5 py-3 font-normal">{{ lang === 'km' ? 'សមាជិកសកម្ម' : 'Subscribers' }}</th>
            <th class="px-5 py-3 font-normal text-right">{{ lang === 'km' ? 'សកម្មភាព' : 'Actions' }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="plan in plans" :key="plan.id"
            class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
          >
            <td class="px-5 py-3.5">
              <p class="font-medium text-slate-900 dark:text-white">{{ plan.name }}</p>
              <p v-if="plan.description" class="text-xs text-slate-400 mt-0.5 max-w-xs truncate">{{ plan.description }}</p>
            </td>
            <td class="px-5 py-3.5 font-semibold text-emerald-500">${{ plan.price }}</td>
            <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400">
              {{ plan.duration_days }} {{ lang === 'km' ? 'ថ្ងៃ' : 'days' }}
            </td>
            <td class="px-5 py-3.5">
              <span class="text-xs font-medium px-2 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500">
                {{ plan.active_subscribers_count ?? 0 }}
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center justify-end gap-2">
                <Link
                  :href="`/dashboard/plans/${plan.id}/edit`"
                  class="text-xs font-medium border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg px-3 py-1.5 transition-colors"
                >
                  {{ lang === 'km' ? 'កែ' : 'Edit' }}
                </Link>
                <button
                  @click="destroyPlan(plan)"
                  class="text-xs font-medium border border-red-200 dark:border-red-500/30 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg px-3 py-1.5 transition-colors"
                >
                  {{ lang === 'km' ? 'លុប' : 'Delete' }}
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!plans.length">
            <td colspan="5" class="px-5 py-10 text-center text-slate-400">
              {{ lang === 'km' ? 'មិនទាន់មាន Plan ណាមួយទេ' : 'No membership plans yet.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fade-in-up 0.35s ease-out;
}
</style>