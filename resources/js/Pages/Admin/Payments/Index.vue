<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

defineProps({
  payments: Object,
  stats: Object,
})

const { t, lang } = useLang()

// Must match payments.method DB enum exactly:
// enum('cash','aba_payway','bakong_khqr','simulation')
const methodKeys = {
  cash: 'payment_method_cash',
  aba_payway: 'payment_method_aba_payway',
  bakong_khqr: 'payment_method_bakong_khqr',
  simulation: 'payment_method_simulation',
}

function methodLabel(method) {
  return methodKeys[method] ? (t.value[methodKeys[method]] ?? method) : method
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}

function formatAmount(amount) {
  return `$${Number(amount).toFixed(2)}`
}

function referenceLabel(payment) {
  if (!payment.reference_type) return '—'
  return payment.reference_type.split('\\').pop()
}

// ===== Refund flow =====
const refundTarget = ref(null) // payment currently in the confirm modal
const refundForm = useForm({ refund_note: '' })

function openRefund(payment) {
  refundForm.reset()
  refundForm.clearErrors()
  refundTarget.value = payment
}

function closeRefund() {
  refundTarget.value = null
}

function confirmRefund() {
  refundForm.patch(`/dashboard/payments/${refundTarget.value.id}/refund`, {
    preserveScroll: true,
    onSuccess: () => { refundTarget.value = null },
  })
}
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ t.payments_title ?? 'ការទូទាត់' }}</h1>
      <Link
        href="/dashboard/payments/create"
        class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-5 py-2.5 text-base transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        {{ t.payments_add_new ?? '+ កត់ត្រាការទូទាត់' }}
      </Link>
    </div>

    <!-- Summary cards -->
    <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ចំណូលសរុប' : 'Total revenue' }}</p>
        <p class="text-2xl font-semibold text-emerald-500">${{ Number(stats.total_revenue).toFixed(2) }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'ចំណូលខែនេះ' : 'This month' }}</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-white">${{ Number(stats.this_month_revenue).toFixed(2) }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
        <p class="text-xs text-slate-400 mb-1">{{ lang === 'km' ? 'បាន Refund សរុប' : 'Total refunded' }}</p>
        <p class="text-2xl font-semibold text-red-500">${{ Number(stats.total_refunded).toFixed(2) }}</p>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-x-auto transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700">
      <table class="w-full text-base min-w-[820px]">
        <thead>
          <tr class="text-left text-slate-500 dark:text-slate-500 border-b border-slate-200 dark:border-slate-800">
            <th class="px-5 py-3.5 font-medium">{{ t.payment_table_member ?? 'សមាជិក' }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.payment_table_amount ?? 'ចំនួនទឹកប្រាក់' }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.payment_table_method ?? 'វិធីទូទាត់' }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.payment_table_reference ?? 'សម្រាប់' }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.payment_table_date ?? 'កាលបរិច្ឆេទ' }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.payment_table_status ?? 'ស្ថានភាព' }}</th>
            <th class="px-5 py-3.5 font-medium text-right">{{ t.payment_table_actions ?? 'សកម្មភាព' }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="payment in payments.data"
            :key="payment.id"
            class="border-b border-slate-100 dark:border-slate-800/50 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/40"
            :class="{ 'opacity-60': payment.refunded_at }"
          >
            <td class="px-5 py-3.5 text-slate-900 dark:text-white">{{ payment.member?.name ?? '—' }}</td>
            <td class="px-5 py-3.5 font-medium" :class="payment.refunded_at ? 'text-red-500 dark:text-red-400 line-through' : 'text-emerald-600 dark:text-emerald-400'">
              {{ formatAmount(payment.amount) }}
            </td>
            <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400">{{ methodLabel(payment.method) }}</td>
            <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400">{{ referenceLabel(payment) }}</td>
            <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ formatDate(payment.paid_at) }}</td>
            <td class="px-5 py-3.5">
              <span
                v-if="payment.refunded_at"
                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-red-500/10 text-red-500 dark:text-red-400"
                :title="payment.refund_note || ''"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                {{ t.payment_status_refunded ?? 'បាន Refund' }}
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                {{ t.payment_status_paid ?? 'បានទូទាត់' }}
              </span>
            </td>
            <td class="px-5 py-3.5 text-right">
              <button
                v-if="!payment.refunded_at"
                @click="openRefund(payment)"
                class="text-xs font-medium px-3 py-1.5 rounded-lg border border-red-300 dark:border-red-500/30 text-red-500 dark:text-red-400 hover:bg-red-500/10 transition-colors"
              >
                {{ t.payment_refund_btn ?? 'Refund' }}
              </button>
              <span v-else class="text-xs text-slate-400 dark:text-slate-600">
                {{ payment.refunded_by?.name ? `${t.payment_refunded_by ?? 'ដោយ'} ${payment.refunded_by.name}` : '—' }}
              </span>
            </td>
          </tr>
          <tr v-if="!payments.data.length">
            <td colspan="7" class="px-5 py-10 text-center text-slate-400 dark:text-slate-500 text-base">{{ t.payments_empty ?? 'មិនទាន់មានការទូទាត់ទេ' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="payments.links.length > 3" class="flex flex-wrap items-center justify-center gap-1.5 mt-6">
      <template v-for="(link, i) in payments.links" :key="i">
        <Link
          v-if="link.url"
          :href="link.url"
          v-html="link.label"
          class="px-3.5 py-2 rounded-lg text-sm transition-all duration-150"
          :class="link.active
            ? 'bg-emerald-500 text-slate-950 font-medium'
            : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'"
        />
        <span
          v-else
          v-html="link.label"
          class="px-3.5 py-2 rounded-lg text-sm text-slate-400 dark:text-slate-600 border border-slate-200 dark:border-slate-800/50"
        />
      </template>
    </div>

    <!-- ===== Refund confirmation modal ===== -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="refundTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeRefund">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 w-full max-w-md shadow-xl">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">
            {{ t.payment_refund_confirm_title ?? 'បញ្ជាក់ការ Refund' }}
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            {{ t.payment_refund_confirm_desc ?? 'សកម្មភាពនេះកត់ត្រាតែស្ថានភាព refund ប៉ុណ្ណោះ — មិនធ្វើប្រតិបត្តិការទូទាត់ប្រាក់ត្រឡប់វិញដោយស្វ័យប្រវត្តិទេ។ សូមប្រាកដថាបានប្រគល់ប្រាក់ត្រឡប់ទៅសមាជិកដោយផ្ទាល់រួចហើយ។' }}
          </p>

          <div class="bg-slate-50 dark:bg-slate-800/60 rounded-lg p-3 mb-4 text-sm">
            <p class="flex justify-between py-0.5"><span class="text-slate-500 dark:text-slate-400">{{ t.payment_field_member ?? 'សមាជិក' }}</span><span class="font-medium text-slate-900 dark:text-white">{{ refundTarget?.member?.name ?? '—' }}</span></p>
            <p class="flex justify-between py-0.5"><span class="text-slate-500 dark:text-slate-400">{{ t.payment_field_amount ?? 'ចំនួនទឹកប្រាក់' }}</span><span class="font-medium text-red-500">{{ formatAmount(refundTarget?.amount) }}</span></p>
          </div>

          <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">
            {{ t.payment_refund_note_label ?? 'មូលហេតុ Refund (មិនចាំបាច់)' }}
          </label>
          <textarea
            v-model="refundForm.refund_note"
            rows="2"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40"
            :placeholder="t.payment_refund_note_placeholder ?? 'ឧ. Member ចាកចេញពី class មុនចាប់ផ្តើម'"
          ></textarea>
          <p v-if="refundForm.errors.refund_note" class="text-red-500 dark:text-red-400 text-xs mt-1">{{ refundForm.errors.refund_note }}</p>

          <div class="flex items-center gap-3 mt-5">
            <button
              @click="closeRefund"
              type="button"
              class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
            >
              {{ t.cancel ?? 'បោះបង់' }}
            </button>
            <button
              @click="confirmRefund"
              :disabled="refundForm.processing"
              type="button"
              class="flex-1 px-4 py-2.5 rounded-lg bg-red-500 hover:bg-red-400 disabled:opacity-50 text-white text-sm font-medium transition-colors"
            >
              {{ refundForm.processing ? (t.payment_refunding ?? 'កំពុងធ្វើ Refund...') : (t.payment_refund_confirm_btn ?? 'បញ្ជាក់ Refund') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
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