<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

defineProps({
  membershipPlans: Array,
})

const { t, lang } = useLang()

const fieldClass = 'w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-4 py-3 text-base text-slate-900 dark:text-white transition-all duration-200 ease-in-out hover:border-slate-400 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40'

const form = useForm({
  name: '',
  phone: '',
  email: '',
  gender: '',
  date_of_birth: '',
})

function submit() {
  form.post('/dashboard/members')
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <Link
      href="/dashboard/members"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors duration-200 mb-4 group"
    >
      <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
    </Link>
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6">{{ t.member_add_title }}</h1>

    <form
      @submit.prevent="submit"
      class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 sm:p-8 transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
        <div class="md:col-span-2">
          <label class="block text-base text-slate-600 dark:text-slate-300 mb-2">{{ t.member_full_name }}</label>
          <input v-model="form.name" type="text" :class="fieldClass" />
          <p v-if="form.errors.name" class="text-red-500 text-sm mt-1.5">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-600 dark:text-slate-300 mb-2">{{ t.member_phone }}</label>
          <input v-model="form.phone" type="tel" :class="fieldClass" />
        </div>

        <div>
          <label class="block text-base text-slate-600 dark:text-slate-300 mb-2">{{ t.member_email }}</label>
          <input v-model="form.email" type="email" :class="fieldClass" />
        </div>

        <div>
          <label class="block text-base text-slate-600 dark:text-slate-300 mb-2">{{ t.member_gender }}</label>
          <select v-model="form.gender" :class="fieldClass">
            <option value="">--</option>
            <option value="male">{{ t.member_gender_male }}</option>
            <option value="female">{{ t.member_gender_female }}</option>
          </select>
        </div>

        <div>
          <label class="block text-base text-slate-600 dark:text-slate-300 mb-2">{{ t.member_dob }}</label>
          <input v-model="form.date_of_birth" type="date" :class="fieldClass" />
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 mt-6">
        <button
          type="submit"
          :disabled="form.processing"
          class="flex-1 sm:flex-none sm:px-10 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:hover:bg-emerald-500 text-slate-950 font-medium text-base rounded-lg py-3 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
        >
          {{ form.processing ? t.member_saving : t.member_save }}
        </button>
        <Link
          href="/dashboard/members"
          class="flex-1 sm:flex-none sm:px-10 text-center border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-base rounded-lg py-3 transition-all duration-200 hover:bg-slate-50 dark:hover:bg-slate-800"
        >
          {{ t.member_cancel }}
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