<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  promotion: Object,
  membershipPlans: Array,
})

const { t, lang } = useLang()

const fieldClass = 'w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-4 py-3 text-base text-slate-900 dark:text-white transition-all duration-200 ease-in-out hover:border-slate-400 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40'

const form = useForm({
  title: props.promotion.title,
  description: props.promotion.description ?? '',
  discount_type: props.promotion.discount_type,
  discount_value: props.promotion.discount_value,
  applicable_plan_id: props.promotion.applicable_plan_id ?? '',
  start_date: props.promotion.start_date?.slice(0, 10) ?? '',
  end_date: props.promotion.end_date?.slice(0, 10) ?? '',
  active: props.promotion.active,
})

function submit() {
  form.put(`/dashboard/promotions/${props.promotion.id}`)
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <Link
      href="/dashboard/promotions"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors duration-200 mb-4 group"
    >
      <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
    </Link>
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6">{{ t.promo_edit_title }}</h1>

    <form
      @submit.prevent="submit"
      class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 sm:p-8 transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.promo_field_title }}</label>
          <input v-model="form.title" type="text" :class="fieldClass" />
          <p v-if="form.errors.title" class="text-red-500 text-sm mt-1.5">{{ form.errors.title }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.promo_field_description }}</label>
          <textarea v-model="form.description" rows="3" :class="[fieldClass, 'resize-y']"></textarea>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.promo_discount_type }}</label>
          <select v-model="form.discount_type" :class="fieldClass">
            <option value="percentage">{{ t.promo_discount_percentage }}</option>
            <option value="fixed_amount">{{ t.promo_discount_fixed }}</option>
          </select>
          <p v-if="form.errors.discount_type" class="text-red-500 text-sm mt-1.5">{{ form.errors.discount_type }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.promo_discount_value }}</label>
          <input v-model.number="form.discount_value" type="number" min="0" step="0.01" :class="fieldClass" />
          <p v-if="form.errors.discount_value" class="text-red-500 text-sm mt-1.5">{{ form.errors.discount_value }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.promo_applicable_plan }}</label>
          <select v-model="form.applicable_plan_id" :class="fieldClass">
            <option value="">{{ t.promo_plan_all }}</option>
            <option v-for="plan in membershipPlans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.promo_start_date }}</label>
          <input v-model="form.start_date" type="date" :class="fieldClass" />
          <p v-if="form.errors.start_date" class="text-red-500 text-sm mt-1.5">{{ form.errors.start_date }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.promo_end_date }}</label>
          <input v-model="form.end_date" type="date" :class="fieldClass" />
          <p v-if="form.errors.end_date" class="text-red-500 text-sm mt-1.5">{{ form.errors.end_date }}</p>
        </div>

        <div class="md:col-span-2 flex items-center gap-3 pt-1">
          <button
            type="button"
            @click="form.active = !form.active"
            class="relative w-12 h-6.5 rounded-full transition-colors duration-200 shrink-0"
            :class="form.active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"
          >
            <span
              class="absolute top-0.5 left-0.5 w-5.5 h-5.5 bg-white rounded-full transition-transform duration-200"
              :class="form.active ? 'translate-x-5.5' : 'translate-x-0'"
            ></span>
          </button>
          <span class="text-base text-slate-700 dark:text-slate-300">{{ t.promo_active_now }}</span>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 mt-6">
        <button
          type="submit"
          :disabled="form.processing"
          class="flex-1 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:hover:bg-emerald-500 text-slate-950 font-medium text-base rounded-lg py-3 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
        >
          {{ form.processing ? t.promo_saving : t.promo_save_changes }}
        </button>
        <a
          href="/dashboard/promotions"
          class="flex-1 text-center border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-base rounded-lg py-3 transition-all duration-200 hover:bg-slate-50 dark:hover:bg-slate-800"
        >
          {{ t.promo_cancel }}
        </a>
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