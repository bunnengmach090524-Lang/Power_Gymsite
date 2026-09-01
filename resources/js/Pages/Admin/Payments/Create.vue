<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  members: Array,
  subscriptions: Array,
})

const { t, lang } = useLang()

const fieldClass = 'w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-base text-slate-900 dark:text-white transition-all duration-200 ease-in-out hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40 disabled:opacity-50 disabled:cursor-not-allowed'

// NOTE: these must match the DB enum exactly —
// enum('cash','aba_payway','bakong_khqr','simulation').
// 'simulation' is intentionally excluded here: it's a dev/testing-only
// value written by ClassCheckoutController, never something an admin
// should be able to pick for a manual entry.
const form = useForm({
  member_id: '',
  amount: '',
  method: 'cash',
  paid_at: new Date().toISOString().slice(0, 16),
  reference_id: '',
})

const methods = [
  { v: 'cash', l: () => t.value.payment_method_cash ?? 'សាច់ប្រាក់', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m0-9a9 9 0 100 8' },
  { v: 'aba_payway', l: () => t.value.payment_method_aba_payway ?? 'ABA PayWay', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
  { v: 'bakong_khqr', l: () => t.value.payment_method_bakong_khqr ?? 'Bakong KHQR', icon: 'M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4zm0 6h4m-4 4h1m3 0h.01M12 4v1m0 4v1m0 6v5m-4-8h.01M16 16h4v4h-4v-4z' },
]

const selectedMember = computed(() => props.members.find(m => m.id === form.member_id))

const memberSubscriptions = computed(() =>
  props.subscriptions.filter((s) => s.member_id === form.member_id)
)

const selectedSubscription = computed(() =>
  props.subscriptions.find(s => s.id === form.reference_id)
)

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function subscriptionLabel(sub) {
  return `${sub.membership_plan?.name ?? '—'} (${formatDate(sub.start_date)} – ${formatDate(sub.end_date)}) · $${sub.final_price}`
}

function onMemberChange() {
  form.reference_id = ''
}

function useSubscriptionAmount() {
  if (selectedSubscription.value) {
    form.amount = selectedSubscription.value.final_price
  }
}

function submit() {
  form.post('/dashboard/payments')
}
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <a
        href="/dashboard/payments"
        class="w-9 h-9 rounded-lg flex items-center justify-center border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors shrink-0"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
      </a>
      <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2.5">
        <span class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2M9 12h11M17 9l3 3-3 3"/>
          </svg>
        </span>
        {{ t.payment_add_title ?? 'កត់ត្រាការទូទាត់ថ្មី' }}
      </h1>
    </div>

    <!-- Full-width two-column layout -->
    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- LEFT: Form fields (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Member section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6">
          <p class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ lang === 'km' ? 'សមាជិក' : 'Member' }}</p>

          <div class="mb-5">
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">{{ t.payment_field_member ?? 'សមាជិក' }}</label>
            <select v-model="form.member_id" @change="onMemberChange" :class="fieldClass">
              <option value="" disabled>{{ t.payment_field_member_select ?? 'ជ្រើសរើសសមាជិក' }}</option>
              <option v-for="member in members" :key="member.id" :value="member.id">{{ member.name }}</option>
            </select>
            <p v-if="form.errors.member_id" class="text-red-500 dark:text-red-400 text-sm mt-1.5">{{ form.errors.member_id }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">{{ t.payment_field_subscription ?? 'គម្រោងសមាជិកភាព (មិនចាំបាច់)' }}</label>
            <select v-model="form.reference_id" @change="useSubscriptionAmount" :disabled="!form.member_id" :class="fieldClass">
              <option value="">{{ form.member_id ? (t.payment_subscription_none ?? 'គ្មានគម្រោងភ្ជាប់') : (t.payment_select_member_first ?? 'ជ្រើសរើសសមាជិកមុនសិន') }}</option>
              <option v-for="sub in memberSubscriptions" :key="sub.id" :value="sub.id">{{ subscriptionLabel(sub) }}</option>
            </select>
            <p v-if="form.errors.reference_id" class="text-red-500 dark:text-red-400 text-sm mt-1.5">{{ form.errors.reference_id }}</p>
          </div>
        </div>

        <!-- Amount + Method section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6">
          <p class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ lang === 'km' ? 'ព័ត៌មានទូទាត់' : 'Payment details' }}</p>

          <div class="mb-5">
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">{{ t.payment_field_amount ?? 'ចំនួនទឹកប្រាក់ ($)' }}</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">$</span>
              <input
                v-model.number="form.amount"
                type="number" min="0.01" step="0.01"
                :class="[fieldClass, 'pl-8 text-lg font-semibold']"
                placeholder="0.00"
              />
            </div>
            <p v-if="form.errors.amount" class="text-red-500 dark:text-red-400 text-sm mt-1.5">{{ form.errors.amount }}</p>
          </div>

          <div class="mb-5">
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">{{ t.payment_field_method ?? 'វិធីទូទាត់' }}</label>
            <!--
              IMPORTANT: values here must exactly match the `payments.method`
              DB enum: enum('cash','aba_payway','bakong_khqr','simulation').
              'simulation' is deliberately omitted — it's written only by the
              dev-only checkout simulate() flow, never picked by an admin.
            -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
              <button
                v-for="m in methods"
                :key="m.v"
                type="button"
                @click="form.method = m.v"
                class="flex items-center gap-2.5 px-4 py-3 rounded-xl border-2 text-sm font-medium transition-all duration-150"
                :class="form.method === m.v
                  ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                  : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600'"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" :d="m.icon"/></svg>
                <span class="truncate">{{ m.l() }}</span>
              </button>
            </div>
            <p v-if="form.errors.method" class="text-red-500 dark:text-red-400 text-sm mt-1.5">{{ form.errors.method }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">{{ t.payment_field_paid_at ?? 'កាលបរិច្ឆេទទូទាត់' }}</label>
            <input v-model="form.paid_at" type="datetime-local" :class="[fieldClass, 'sm:max-w-sm']" />
            <p v-if="form.errors.paid_at" class="text-red-500 dark:text-red-400 text-sm mt-1.5">{{ form.errors.paid_at }}</p>
          </div>
        </div>
      </div>

      <!-- RIGHT: Live summary preview (1/3 width, sticky) -->
      <div class="lg:col-span-1">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sticky top-20">
          <p class="text-sm font-semibold text-slate-900 dark:text-white mb-5">{{ lang === 'km' ? 'សង្ខេប' : 'Summary' }}</p>

          <div class="flex items-center gap-3 pb-5 mb-5 border-b border-slate-100 dark:border-slate-800">
            <div class="w-11 h-11 rounded-full bg-emerald-500 flex items-center justify-center text-white font-semibold shrink-0">
              {{ selectedMember?.name?.[0]?.toUpperCase() ?? '?' }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ selectedMember?.name ?? (lang === 'km' ? 'មិនទាន់ជ្រើសរើស' : 'No member selected') }}</p>
              <p v-if="selectedSubscription" class="text-xs text-slate-400 truncate">{{ selectedSubscription.membership_plan?.name }}</p>
            </div>
          </div>

          <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">{{ lang === 'km' ? 'ចំនួនទឹកប្រាក់' : 'Amount' }}</span>
              <span class="font-semibold text-slate-900 dark:text-white">${{ form.amount ? Number(form.amount).toFixed(2) : '0.00' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">{{ lang === 'km' ? 'វិធីទូទាត់' : 'Method' }}</span>
              <span class="font-medium text-slate-700 dark:text-slate-200">{{ methods.find(m => m.v === form.method)?.l() }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">{{ lang === 'km' ? 'កាលបរិច្ឆេទ' : 'Date' }}</span>
              <span class="font-medium text-slate-700 dark:text-slate-200">{{ form.paid_at ? new Date(form.paid_at).toLocaleDateString(lang === 'km' ? 'km-KH' : 'en-US', { month: 'short', day: 'numeric' }) : '—' }}</span>
            </div>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="mt-6 w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:hover:bg-emerald-500 text-slate-950 font-medium text-base rounded-xl py-3 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
          >
            {{ form.processing ? (t.payment_saving ?? 'កំពុងរក្សាទុក...') : (t.payment_save ?? 'រក្សាទុកការទូទាត់') }}
          </button>
        </div>
      </div>
    </form>
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