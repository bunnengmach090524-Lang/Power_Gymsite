<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const { t } = useLang()

const props = defineProps({ userProfile: Object })

const avatarPreview = ref(props.userProfile.avatar ?? null)
const fileInput = ref(null)

const form = useForm({
  name: props.userProfile.name,
  email: props.userProfile.email,
  password: '',
  password_confirmation: '',
  avatar: null,
})

function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.avatar = file
  avatarPreview.value = URL.createObjectURL(file)
}

function submit() {
  form.post('/dashboard/profile', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.password = ''
      form.password_confirmation = ''
    },
  })
}

function cancel() {
  form.reset()
  form.clearErrors()
   avatarPreview.value = props.userProfile.avatar ?? null
}
</script>

<template>
  <div class="max-w-4xl animate-fade-in-up">
    <Link
      href="/dashboard"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors mb-4"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      {{ t.profile_back }}
    </Link>

    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6">{{ t.profile_title }}</h1>

    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Avatar card -->
      <div class="md:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 flex flex-col items-center text-center transition-colors duration-300">
        <img v-if="avatarPreview" :src="avatarPreview" class="w-28 h-28 rounded-full object-cover mb-4 ring-2 ring-emerald-500/30" />
        <div v-else class="w-28 h-28 rounded-full bg-emerald-500 flex items-center justify-center text-white text-3xl font-semibold mb-4">
          {{ form.name?.[0]?.toUpperCase() ?? 'A' }}
        </div>
        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
        <button type="button" @click="fileInput.click()" class="px-4 py-2 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-emerald-400 hover:text-emerald-500 transition-colors">
          {{ t.profile_change_photo }}
        </button>
        <p class="text-xs text-slate-400 mt-2">{{ t.profile_photo_hint }}</p>
        <p v-if="form.errors.avatar" class="text-xs text-red-500 mt-2">{{ form.errors.avatar }}</p>
      </div>

      <!-- Info card -->
      <div class="md:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 space-y-5 transition-colors duration-300">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">{{ t.profile_name }}</label>
            <input v-model="form.name" type="text" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">{{ t.profile_email }}</label>
            <input v-model="form.email" type="email" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">{{ t.profile_password }}</label>
          <input v-model="form.password" type="password" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
          <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
        </div>
        <div v-if="form.password">
          <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">{{ t.profile_password_confirm }}</label>
          <input v-model="form.password_confirmation" type="password" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
        </div>

        <div class="flex items-center gap-3 pt-2">
          <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium transition-colors disabled:opacity-50">
            {{ t.profile_save }}
          </button>
          <button
            type="button"
            @click="cancel"
            class="px-5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            {{ t.profile_cancel }}
          </button>
          <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <span v-if="form.recentlySuccessful" class="text-sm text-emerald-500">{{ t.profile_saved }}</span>
          </Transition>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
</style>