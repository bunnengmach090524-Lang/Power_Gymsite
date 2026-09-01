<script setup>
import { computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  promotions: Array,
  availablePlans: { type: Array, default: () => [] },
})

const { t, lang } = useLang()
const page = usePage()

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function formatDiscount(promo) {
  return promo.discount_type === 'percentage' ? `${promo.discount_value}%` : `$${promo.discount_value}`
}

// NOTE: assumes MembershipPlan has a `price` column. Adjust the field
// name below if your plans use something else (e.g. `monthly_price`).
function originalPrice(promo) {
  return promo.applicable_plan?.price ?? null
}

function discountedPrice(promo) {
  const original = originalPrice(promo)
  if (original === null) return null
  return promo.discount_type === 'percentage'
    ? (original * (1 - promo.discount_value / 100)).toFixed(2)
    : Math.max(0, original - promo.discount_value).toFixed(2)
}

function status(promo) {
  if (!promo.active) return 'inactive'
  const today = new Date().toISOString().slice(0, 10)
  const start = (promo.start_date ?? '').slice(0, 10)
  const end = (promo.end_date ?? '').slice(0, 10)
  if (today < start) return 'scheduled'
  if (today > end) return 'expired'
  return 'live'
}

function daysLeft(promo) {
  const end = new Date((promo.end_date ?? '').slice(0, 10))
  const today = new Date(new Date().toISOString().slice(0, 10))
  return Math.ceil((end - today) / 86400000)
}

function isExpiringSoon(promo) {
  return status(promo) === 'live' && daysLeft(promo) <= 3 && daysLeft(promo) >= 0
}

const statusStyles = {
  live: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
  scheduled: 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400',
  expired: 'bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400',
  inactive: 'bg-red-50 dark:bg-red-500/10 text-red-500 dark:text-red-400',
}

const statusKeys = {
  live: 'promo_status_live',
  scheduled: 'promo_status_scheduled',
  expired: 'promo_status_expired',
  inactive: 'promo_status_inactive',
}

// Summary stats for the top cards row
const liveCount = computed(() => props.promotions.filter(p => status(p) === 'live').length)
const scheduledCount = computed(() => props.promotions.filter(p => status(p) === 'scheduled').length)
const expiringCount = computed(() => props.promotions.filter(isExpiringSoon).length)

function destroy(promo) {
  if (confirm(`${t.value.confirm_delete_prefix} ${promo.title}?`)) {
    router.delete(`/dashboard/promotions/${promo.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
          🔥 {{ t.promotions_title }}
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          {{ lang === 'km' ? 'គ្រប់គ្រង Flash Sale discount សម្រាប់ Membership Plan' : 'Manage flash-sale discounts for your membership plans' }}
        </p>
      </div>
      <Link
        href="/dashboard/promotions/create"
        class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-5 py-2.5 text-sm sm:text-base transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        {{ t.promotions_add_new }}
      </Link>
    </div>

    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="page.props.flash?.success"
        class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-4"
      >
        {{ page.props.flash.success }}
      </div>
    </Transition>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'កំពុងសកម្ម' : 'Active now' }}</p>
        <p class="text-2xl font-semibold text-emerald-500">{{ liveCount }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'នឹងចាប់ផ្តើម' : 'Scheduled' }}</p>
        <p class="text-2xl font-semibold text-amber-500">{{ scheduledCount }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 col-span-2 sm:col-span-1">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ជិតផុតកំណត់' : 'Expiring soon' }}</p>
        <p class="text-2xl font-semibold text-red-500">{{ expiringCount }}</p>
      </div>
    </div>

    <!-- Active/scheduled/expired promotions table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700 mb-8">
      <div class="overflow-x-auto">
        <table class="w-full text-sm sm:text-base min-w-[820px]">
          <thead>
            <tr class="text-left text-slate-500 dark:text-slate-500 border-b border-slate-200 dark:border-slate-800">
              <th class="px-5 py-3.5 font-medium">{{ t.promo_table_plan }}</th>
              <th class="px-5 py-3.5 font-medium">{{ lang === 'km' ? 'តម្លៃ' : 'Price' }}</th>
              <th class="px-5 py-3.5 font-medium">{{ t.promo_table_discount }}</th>
              <th class="px-5 py-3.5 font-medium">{{ lang === 'km' ? 'តម្លៃក្រោយបញ្ចុះ' : 'New price' }}</th>
              <th class="px-5 py-3.5 font-medium">{{ t.promo_table_period }}</th>
              <th class="px-5 py-3.5 font-medium">{{ t.promo_table_status }}</th>
              <th class="px-5 py-3.5 font-medium"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="promo in promotions"
              :key="promo.id"
              class="border-b border-slate-100 dark:border-slate-800/50 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/40"
            >
              <td class="px-5 py-3.5">
                <p class="text-slate-900 dark:text-white font-medium">{{ promo.title }}</p>
                <p class="text-xs text-slate-400">{{ promo.applicable_plan?.name ?? t.promo_plan_all }}</p>
              </td>
              <td class="px-5 py-3.5 text-slate-400 line-through">
                {{ originalPrice(promo) !== null ? `$${originalPrice(promo)}` : '—' }}
              </td>
              <td class="px-5 py-3.5">
                <span class="inline-flex items-center text-xs font-bold px-2 py-1 rounded-full bg-red-500 text-white">
                  -{{ formatDiscount(promo) }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                {{ discountedPrice(promo) !== null ? `$${discountedPrice(promo)}` : '—' }}
              </td>
              <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                <p>{{ formatDate(promo.start_date) }} – {{ formatDate(promo.end_date) }}</p>
                <p v-if="isExpiringSoon(promo)" class="text-xs text-red-500 font-medium mt-0.5">
                  {{ lang === 'km' ? `ជិតផុតកំណត់ក្នុង ${daysLeft(promo)} ថ្ងៃ` : `Ends in ${daysLeft(promo)}d` }}
                </p>
              </td>
              <td class="px-5 py-3.5">
                <span class="text-sm rounded-full px-3 py-1 transition-colors duration-150 whitespace-nowrap" :class="statusStyles[status(promo)]">
                  {{ t[statusKeys[status(promo)]] }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-right whitespace-nowrap">
                <Link
                  :href="`/dashboard/promotions/${promo.id}/edit`"
                  class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-sm mr-4 transition-colors duration-150"
                >{{ t.action_edit }}</Link>
                <button
                  @click="destroy(promo)"
                  class="text-red-500 dark:text-red-400 hover:text-red-400 text-sm transition-colors duration-150"
                >{{ t.action_delete }}</button>
              </td>
            </tr>
            <tr v-if="!promotions.length">
              <td colspan="7" class="px-5 py-10 text-center text-slate-500 dark:text-slate-500 text-base">{{ t.promotions_empty }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Plans without a discount yet: quick-add -->
    <div v-if="availablePlans.length">
      <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-3">
        {{ lang === 'km' ? 'ផែនការដែលមិនទាន់មាន Discount' : 'Plans without a discount' }}
      </h2>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden">
        <div
          v-for="plan in availablePlans"
          :key="plan.id"
          class="flex items-center justify-between px-5 py-3.5 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/40"
        >
          <div class="min-w-0">
            <p class="text-slate-900 dark:text-white font-medium truncate">{{ plan.name }}</p>
            <p class="text-xs text-slate-400">
              <span v-if="plan.price !== undefined">${{ plan.price }}</span>
              <span v-if="plan.price !== undefined && plan.duration_days"> · </span>
              <span v-if="plan.duration_days">{{ lang === 'km' ? `រយៈពេល ${plan.duration_days} ថ្ងៃ` : `${plan.duration_days} days` }}</span>
            </p>
          </div>
          <Link
            :href="`/dashboard/promotions/create?plan_id=${plan.id}`"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-500 hover:text-amber-400 transition-colors duration-150 shrink-0 ml-4"
          >
            🔥 {{ lang === 'km' ? 'បន្ថែម Discount' : 'Add Discount' }}
          </Link>
        </div>
      </div>
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