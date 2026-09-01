<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })
const { t } = useLang()

const form = useForm({ name: '', email: '', specialty: '', bio: '', photo: null, shift_start_time: '' })
const preview = ref(null)
const fileInput = ref(null)

function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.photo = file
  preview.value = URL.createObjectURL(file)
}

function submit() {
  form.post('/dashboard/trainers', { forceFormData: true })
}
</script>

<template>
  <div class="p-6 sm:p-8 animate-fade-in-up">
    <Link href="/dashboard/trainers" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 transition-colors duration-200 mb-4 group">
      <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
      {{ t.trainer_back }}
    </Link>
    <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white mb-6 tracking-tight">{{ t.trainer_add_new }}</h1>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
      <!-- Photo card -->
      <div class="lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 flex flex-col items-center justify-center transition-all duration-300 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-xl hover:shadow-slate-200/40 dark:hover:shadow-black/20">
        <img
          v-if="preview"
          :src="preview"
          class="w-28 h-28 rounded-full object-cover mb-4 ring-4 ring-emerald-500/10 transition-transform duration-300 hover:scale-[1.03]"
        />
        <div v-else class="w-28 h-28 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 flex items-center justify-center mb-4 ring-4 ring-emerald-500/10">
          <svg class="w-9 h-9 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
        </div>
        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
        <button
          type="button"
          @click="fileInput.click()"
          class="text-sm px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 transition-all duration-200 hover:border-emerald-400 hover:text-emerald-500 hover:scale-[1.02] active:scale-[0.98]"
        >
          {{ t.trainer_upload_photo }}
        </button>
      </div>

      <!-- Info card -->
      <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-5 transition-all duration-300 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-xl hover:shadow-slate-200/40 dark:hover:shadow-black/20">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.trainer_name }}</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm transition-all duration-200 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
            />
            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 mb-1.5">
              {{ t.trainer_email ?? 'អ៊ីមែល' }}
              <span class="text-xs text-slate-400 font-normal">({{ t.trainer_optional ?? 'ស្រេចចិត្ត' }})</span>
            </label>
            <div class="relative">
              <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <input
                v-model="form.email"
                type="email"
                placeholder="trainer@example.com"
                class="w-full pl-9 pr-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm transition-all duration-200 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
              />
            </div>
            <p class="text-xs text-slate-400 mt-1">
              {{ t.trainer_email_hint ?? 'ប្រើសម្រាប់ផ្ញើការអញ្ជើញ login (Invite to Login)' }}
            </p>
            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.trainer_specialty }}</label>
          <input
            v-model="form.specialty"
            type="text"
            :placeholder="t.trainer_specialty_placeholder"
            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm transition-all duration-200 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
          />
        </div>

        <div>
          <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">
            {{ t.trainer_shift_start_time ?? 'ម៉ោងចាប់ផ្តើមការងារ' }}
            <span class="text-xs text-slate-400 font-normal">({{ t.trainer_optional ?? 'ស្រេចចិត្ត' }})</span>
          </label>
          <input
            v-model="form.shift_start_time"
            type="time"
            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm transition-all duration-200 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
          />
          <p class="text-xs text-slate-400 mt-1">{{ t.trainer_shift_hint ?? 'ប្រើសម្រាប់គណនាថាតើ check-in យឺតឬអត់' }}</p>
          <p v-if="form.errors.shift_start_time" class="text-xs text-red-500 mt-1">{{ form.errors.shift_start_time }}</p>
        </div>

        <div>
          <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.trainer_bio }}</label>
          <textarea
            v-model="form.bio"
            rows="5"
            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm resize-y transition-all duration-200 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
          ></textarea>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:hover:scale-100 disabled:hover:shadow-none"
        >
          {{ form.processing ? t.trainer_saving : t.trainer_save }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
</style>