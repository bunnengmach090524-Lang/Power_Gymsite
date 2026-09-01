<script setup>
import { ref, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  profiles: Array,
  payments: Array,
})

const { lang } = useLang()
const page = usePage()

function todayIso() { return new Date().toISOString().slice(0, 10) }
function startOfMonthIso() {
  const d = new Date()
  return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10)
}

const showModal = ref(false)
const selectedStaff = ref(null)
const calculating = ref(false)
const saving = ref(false)

const form = reactive({
  staff_profile_id: null,
  period_start: startOfMonthIso(),
  period_end: todayIso(),
  base_amount: 0,
  bonus: 0,
  deduction: 0,
})

const preview = ref(null)

function openGenerate(profile) {
  selectedStaff.value = profile
  form.staff_profile_id = profile.id
  form.period_start = startOfMonthIso()
  form.period_end = todayIso()
  form.base_amount = 0
  form.bonus = 0
  form.deduction = 0
  preview.value = null
  showModal.value = true
  runPreview()
}

async function runPreview() {
  calculating.value = true
  try {
    const { data } = await axios.post('/dashboard/salary/calculate', {
      staff_profile_id: form.staff_profile_id,
      period_start: form.period_start,
      period_end: form.period_end,
    })
    preview.value = data
    form.base_amount = data.base_amount
    form.bonus = data.commission
  } finally {
    calculating.value = false
  }
}

function submit() {
  saving.value = true
  router.post('/dashboard/salary', form, {
    preserveScroll: true,
    onSuccess: () => { showModal.value = false },
    onFinish: () => { saving.value = false },
  })
}

function markPaid(payment) {
  if (!confirm(lang.value === 'km' ? 'បញ្ជាក់ថាបានបង់ប្រាក់ខែនេះ?' : 'Confirm this salary was paid?')) return
  router.patch(`/dashboard/salary/${payment.id}/mark-paid`, {}, { preserveScroll: true })
}

function removePayment(payment) {
  if (!confirm(lang.value === 'km' ? 'លុបកំណត់ត្រានេះ?' : 'Delete this record?')) return
  router.delete(`/dashboard/salary/${payment.id}`, { preserveScroll: true })
}

function statusLabel(status) {
  return status === 'paid'
    ? (lang.value === 'km' ? 'បានបង់' : 'Paid')
    : (lang.value === 'km' ? 'មិនទាន់បង់' : 'Pending')
}
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6">
      {{ lang === 'km' ? 'ប្រាក់ខែបុគ្គលិក' : 'Staff Salary' }}
    </h1>

    <div
      v-if="page.props.flash?.success"
      class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-6"
    >
      {{ page.props.flash.success }}
    </div>

    <!-- Staff cards: click to generate -->
    <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-3">
      {{ lang === 'km' ? 'បង្កើតការគណនាថ្មី' : 'Generate a new calculation' }}
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
      <button
        v-for="p in profiles"
        :key="p.id"
        @click="openGenerate(p)"
        class="text-left bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center gap-3 transition-all duration-200 hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-500/10"
      >
        <img v-if="p.photo_url" :src="p.photo_url" class="w-10 h-10 rounded-full object-cover" />
        <div v-else class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold">
          {{ p.name?.[0]?.toUpperCase() ?? '?' }}
        </div>
        <div>
          <p class="text-slate-900 dark:text-white font-medium">{{ p.name }}</p>
          <p class="text-xs text-slate-400">{{ p.position }}</p>
        </div>
      </button>
    </div>

    <!-- Payment history -->
    <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-3">
      {{ lang === 'km' ? 'ប្រវត្តិប្រាក់ខែ' : 'Salary history' }}
    </h2>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-x-auto">
      <table class="w-full text-sm min-w-[760px]">
        <thead>
          <tr class="text-left text-slate-500 border-b border-slate-200 dark:border-slate-800">
            <th class="px-5 py-3 font-medium">{{ lang === 'km' ? 'បុគ្គលិក' : 'Staff' }}</th>
            <th class="px-5 py-3 font-medium">{{ lang === 'km' ? 'កំឡុងពេល' : 'Period' }}</th>
            <th class="px-5 py-3 font-medium">{{ lang === 'km' ? 'មូលដ្ឋាន' : 'Base' }}</th>
            <th class="px-5 py-3 font-medium">Bonus</th>
            <th class="px-5 py-3 font-medium">{{ lang === 'km' ? 'ដក' : 'Deduction' }}</th>
            <th class="px-5 py-3 font-medium">{{ lang === 'km' ? 'សរុប' : 'Total' }}</th>
            <th class="px-5 py-3 font-medium">{{ lang === 'km' ? 'ស្ថានភាព' : 'Status' }}</th>
            <th class="px-5 py-3 font-medium text-right">{{ lang === 'km' ? 'សកម្មភាព' : 'Actions' }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="pay in payments"
            :key="pay.id"
            class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/40"
          >
            <td class="px-5 py-3 text-slate-900 dark:text-white font-medium">{{ pay.staff_name }}</td>
            <td class="px-5 py-3 text-slate-500 text-xs">{{ pay.period_start }} → {{ pay.period_end }}</td>
            <td class="px-5 py-3">${{ pay.base_amount }}</td>
            <td class="px-5 py-3">${{ pay.bonus }}</td>
            <td class="px-5 py-3">${{ pay.deduction }}</td>
            <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white">${{ pay.total }}</td>
            <td class="px-5 py-3">
              <span
                :class="pay.status === 'paid'
                  ? 'text-emerald-500'
                  : 'text-amber-500'"
                class="text-xs font-medium"
              >
                {{ statusLabel(pay.status) }}
              </span>
            </td>
            <td class="px-5 py-3 text-right space-x-3 whitespace-nowrap">
              <button
                v-if="pay.status !== 'paid'"
                @click="markPaid(pay)"
                class="text-emerald-600 dark:text-emerald-400 hover:underline text-xs font-medium"
              >
                {{ lang === 'km' ? 'សម្គាល់ថាបានបង់' : 'Mark paid' }}
              </button>
              <button
                v-if="pay.status !== 'paid'"
                @click="removePayment(pay)"
                class="text-red-500 hover:text-red-600 text-xs font-medium"
              >
                {{ lang === 'km' ? 'លុប' : 'Delete' }}
              </button>
            </td>
          </tr>
          <tr v-if="!payments.length">
            <td colspan="8" class="px-5 py-10 text-center text-slate-400">
              {{ lang === 'km' ? 'មិនទាន់មានកំណត់ត្រាទេ' : 'No salary records yet' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Generate modal -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div v-if="showModal" class="fixed inset-0 bg-slate-950/60 flex items-center justify-center z-[60] p-4" @click.self="showModal = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 w-full max-w-md">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">{{ selectedStaff?.name }}</h2>
          <p class="text-xs text-slate-400 mb-4">{{ selectedStaff?.position }}</p>

          <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
              <label class="block text-xs text-slate-500 mb-1">{{ lang === 'km' ? 'ចាប់ពី' : 'From' }}</label>
              <input v-model="form.period_start" @change="runPreview" type="date" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">{{ lang === 'km' ? 'ដល់' : 'To' }}</label>
              <input v-model="form.period_end" @change="runPreview" type="date" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
          </div>

          <div v-if="calculating" class="text-sm text-slate-400 mb-4">{{ lang === 'km' ? 'កំពុងគណនា...' : 'Calculating...' }}</div>

          <div v-else-if="preview" class="space-y-3 mb-4">
            <div v-if="preview.hours_worked !== null" class="text-xs text-slate-500">
              {{ lang === 'km' ? 'ម៉ោងធ្វើការ' : 'Hours worked' }}: {{ preview.hours_worked }}h
            </div>
            <div v-if="preview.commission_note === 'manual_required'" class="bg-amber-50 dark:bg-amber-500/10 border border-amber-300 dark:border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs rounded-lg px-3 py-2">
              {{ lang === 'km'
                ? 'Commission ប្រភេទនេះមិនអាចគណនាស្វ័យប្រវត្តិបានទេ សូមវាយបញ្ចូល Bonus ដោយដៃ'
                : "This commission source can't be auto-calculated — please enter Bonus manually." }}
            </div>

            <div>
              <label class="block text-xs text-slate-500 mb-1">{{ lang === 'km' ? 'មូលដ្ឋាន ($)' : 'Base ($)' }}</label>
              <input v-model.number="form.base_amount" type="number" step="0.01" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Bonus / Commission ($)</label>
              <input v-model.number="form.bonus" type="number" step="0.01" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">{{ lang === 'km' ? 'ដក ($)' : 'Deduction ($)' }}</label>
              <input v-model.number="form.deduction" type="number" step="0.01" class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" />
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-sm font-semibold text-slate-900 dark:text-white">
              <span>{{ lang === 'km' ? 'សរុប' : 'Total' }}</span>
              <span>${{ (form.base_amount + form.bonus - form.deduction).toFixed(2) }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              @click="submit"
              :disabled="saving || calculating"
              class="flex-1 px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium transition-colors disabled:opacity-50"
            >
              {{ saving ? (lang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...') : (lang === 'km' ? 'រក្សាទុក' : 'Save') }}
            </button>
            <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800">
              {{ lang === 'km' ? 'បោះបង់' : 'Cancel' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
</style>