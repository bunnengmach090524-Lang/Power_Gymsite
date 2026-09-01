<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

defineProps({
  trainers: Array,
})

const { t, lang } = useLang()

const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']

const fieldClass = 'w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-4 py-3 text-base text-slate-900 dark:text-white transition-all duration-200 ease-in-out hover:border-slate-400 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40'

const form = useForm({
  trainer_id: '',
  name: '',
  description: '',
  schedule_day: 'mon',
  start_time: '',
  end_time: '',
  capacity: 15,
  price: null,
  image: null,
})

const imagePreview = ref(null)
function onImageChange(e) {
  const file = e.target.files[0]
  form.image = file ?? null
  imagePreview.value = file ? URL.createObjectURL(file) : null
}

function submit() {
  form.post('/dashboard/classes', { forceFormData: true })
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <Link
      href="/dashboard/classes"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors duration-200 mb-4 group"
    >
      <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
    </Link>
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6">{{ t.class_add_title }}</h1>

    <form
      @submit.prevent="submit"
      class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 sm:p-8 transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">
            {{ t.class_image ?? 'រូបភាព Class' }}
            <span class="text-sm text-slate-400 font-normal">({{ t.class_image_hint ?? 'ស្រេចចិត្ត' }})</span>
          </label>
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-xl overflow-hidden shrink-0 bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
              <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
              <svg v-else class="w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <input type="file" accept="image/*" @change="onImageChange" class="text-sm text-slate-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-emerald-50 dark:file:bg-emerald-500/10 file:text-emerald-600 dark:file:text-emerald-400 file:font-medium hover:file:bg-emerald-100 dark:hover:file:bg-emerald-500/20 file:cursor-pointer" />
          </div>
          <p v-if="form.errors.image" class="text-red-500 text-sm mt-1.5">{{ form.errors.image }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.class_name }}</label>
          <input v-model="form.name" type="text" :class="fieldClass" />
          <p v-if="form.errors.name" class="text-red-500 text-sm mt-1.5">{{ form.errors.name }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.class_description }}</label>
          <textarea v-model="form.description" rows="3" :class="[fieldClass, 'resize-y']"></textarea>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.class_trainer }}</label>
          <select v-model="form.trainer_id" :class="fieldClass">
            <option value="">{{ t.class_trainer_none }}</option>
            <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">{{ trainer.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.class_schedule_day }}</label>
          <select v-model="form.schedule_day" :class="fieldClass">
            <option v-for="day in days" :key="day" :value="day">{{ t[`day_${day}`] }}</option>
          </select>
          <p v-if="form.errors.schedule_day" class="text-red-500 text-sm mt-1.5">{{ form.errors.schedule_day }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.class_start_time }}</label>
          <input v-model="form.start_time" type="time" :class="fieldClass" />
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.class_end_time }}</label>
          <input v-model="form.end_time" type="time" :class="fieldClass" />
          <p v-if="form.errors.end_time" class="text-red-500 text-sm mt-1.5">{{ form.errors.end_time }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">{{ t.class_capacity }}</label>
          <input v-model.number="form.capacity" type="number" min="1" :class="fieldClass" />
          <p v-if="form.errors.capacity" class="text-red-500 text-sm mt-1.5">{{ form.errors.capacity }}</p>
        </div>

        <div>
          <label class="block text-base text-slate-700 dark:text-slate-300 mb-2">
            {{ t.class_price ?? 'តម្លៃ ($)' }}
            <span class="text-sm text-slate-400 font-normal">({{ t.class_price_hint ?? 'ទុកទទេ = ឥតគិតថ្លៃ' }})</span>
          </label>
          <input v-model.number="form.price" type="number" min="0" step="0.01" placeholder="0.00" :class="fieldClass" />
          <p v-if="form.errors.price" class="text-red-500 text-sm mt-1.5">{{ form.errors.price }}</p>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 mt-6">
        <button
          type="submit"
          :disabled="form.processing"
          class="flex-1 sm:flex-none sm:px-10 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:hover:bg-emerald-500 text-slate-950 font-medium text-base rounded-lg py-3 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
        >
          {{ form.processing ? t.class_saving : t.class_save }}
        </button>
        <Link
          href="/dashboard/classes"
          class="flex-1 sm:flex-none sm:px-10 text-center border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-base rounded-lg py-3 transition-all duration-200 hover:bg-slate-50 dark:hover:bg-slate-800"
        >
          {{ t.class_cancel }}
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