<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const { t, lang } = useLang()

const fieldClass = 'w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-4 py-3 text-base text-slate-900 dark:text-white transition-all duration-200 ease-in-out hover:border-slate-400 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40'

const form = useForm({
  name: '',
  price: null,
  duration_days: 30,
  description: '',
})

function submit() {
  form.post('/dashboard/plans')
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <Link
      href="/dashboard/plans"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors duration-200 mb-4 group"
    >
      <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
    </Link>
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6">
      {{ lang === 'km' ? 'បន្ថែម Plan ថ្មី' : 'Add New Plan' }}
    </h1>

    <form
      @submit.prevent="submit"
      class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 sm:p-8 transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">
            {{ lang === 'km' ? 'ឈ្មោះ Plan' : 'Plan Name' }}
          </label>
          <input v-model="form.name" type="text" placeholder="e.g. Basic, Standard, Premium" :class="fieldClass" />
          <p v-if="form.errors.name" class="text-red-500 text-sm mt-1.5">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">
            {{ lang === 'km' ? 'តម្លៃ ($)' : 'Price ($)' }}
          </label>
          <input v-model.number="form.price" type="number" min="0" step="0.01" placeholder="0.00" :class="fieldClass" />
          <p v-if="form.errors.price" class="text-red-500 text-sm mt-1.5">{{ form.errors.price }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">
            {{ lang === 'km' ? 'រយៈពេល (ថ្ងៃ)' : 'Duration (days)' }}
          </label>
          <input v-model.number="form.duration_days" type="number" min="1" :class="fieldClass" />
          <p class="text-xs text-slate-400 mt-1.5">{{ lang === 'km' ? '30=1ខែ, 90=3ខែ, 365=1ឆ្នាំ' : '30 = 1 month, 90 = 3 months, 365 = 1 year' }}</p>
          <p v-if="form.errors.duration_days" class="text-red-500 text-sm mt-1.5">{{ form.errors.duration_days }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">
            {{ lang === 'km' ? 'ការពិពណ៌នា' : 'Description' }}
            <span class="text-sm text-slate-400 font-normal">({{ lang === 'km' ? 'ស្រេចចិត្ត' : 'optional' }})</span>
          </label>
          <textarea v-model="form.description" rows="3" :class="[fieldClass, 'resize-y']"></textarea>
          <p v-if="form.errors.description" class="text-red-500 text-sm mt-1.5">{{ form.errors.description }}</p>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 mt-6">
        <button
          type="submit"
          :disabled="form.processing"
          class="flex-1 sm:flex-none sm:px-10 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:hover:bg-emerald-500 text-slate-950 font-medium text-base rounded-lg py-3 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
        >
          {{ form.processing ? (lang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...') : (lang === 'km' ? 'រក្សាទុក' : 'Save') }}
        </button>
        <Link
          href="/dashboard/plans"
          class="flex-1 sm:flex-none sm:px-10 text-center border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-base rounded-lg py-3 transition-all duration-200 hover:bg-slate-50 dark:hover:bg-slate-800"
        >
          {{ lang === 'km' ? 'បោះបង់' : 'Cancel' }}
        </Link>
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