<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  profile: Object,
})

const { t, lang } = useLang()

const form = useForm({
  name: props.profile.name,
  email: props.profile.email ?? '',
  photo: null,
  position: props.profile.position,
  salary_type: props.profile.salary_type,
  base_salary: props.profile.base_salary ?? '',
  hourly_rate: props.profile.hourly_rate ?? '',
  commission_percent: props.profile.commission_percent ?? '',
  commission_source: props.profile.commission_source ?? '',
  hire_date: props.profile.hire_date ?? '',
  active: props.profile.active,
})

const photoPreview = ref(props.profile.photo_url ?? null)

function onPhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.photo = file
  photoPreview.value = URL.createObjectURL(file)
}

function submit() {
  form.transform((data) => ({
    ...data,
    _method: 'patch', // file uploads need POST + method spoof
  })).post(`/dashboard/staff/${props.profile.id}`, {
    forceFormData: true,
  })
}

function resendQr() {
  router.post(`/dashboard/staff/${props.profile.id}/resend-telegram-qr`, {}, {
    preserveScroll: true,
  })
}

const inputClass = "w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm transition-all duration-200 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <Link
      href="/dashboard/staff"
      class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors duration-200 mb-4 group"
    >
      <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
    </Link>

    <div class="flex items-center gap-3 mb-6">
      <img v-if="photoPreview" :src="photoPreview" class="w-14 h-14 rounded-full object-cover" />
      <div v-else class="w-14 h-14 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xl font-semibold">
        {{ profile.name?.[0]?.toUpperCase() ?? '?' }}
      </div>
      <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ profile.name }}</h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
          {{ profile.payable_type === 'trainer' ? t.staff_type_trainer : t.staff_type_user }}
        </span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

      <form @submit.prevent="submit" class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6 transition-all duration-300 hover:border-slate-300 dark:hover:border-slate-700">

        <!-- NEW: Photo + Name + Email section -->
        <div class="pb-6 border-b border-slate-100 dark:border-slate-800 space-y-5">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ lang === 'km' ? 'ព័ត៌មានផ្ទាល់ខ្លួន' : 'Personal Info' }}
          </h2>

          <div class="flex items-center gap-4">
            <img v-if="photoPreview" :src="photoPreview" class="w-16 h-16 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800" />
            <div v-else class="w-16 h-16 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xl font-semibold">
              {{ profile.name?.[0]?.toUpperCase() ?? '?' }}
            </div>
            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {{ lang === 'km' ? 'ប្តូររូបភាព' : 'Change photo' }}
              <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPhotoChange" />
            </label>
          </div>
          <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
            <p v-if="form.errors.photo" class="text-xs text-red-500">{{ form.errors.photo }}</p>
          </Transition>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">
                {{ lang === 'km' ? 'ឈ្មោះ' : 'Name' }}
              </label>
              <input v-model="form.name" type="text" required :class="inputClass" />
              <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
              </Transition>
            </div>

            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">
                {{ lang === 'km' ? 'អ៊ីមែល' : 'Email' }}
                <span v-if="profile.payable_type === 'trainer'" class="text-slate-400 font-normal">
                  ({{ lang === 'km' ? 'ទំនាក់ទំនងតែប៉ុណ្ណោះ' : 'contact only' }})
                </span>
              </label>
              <input v-model="form.email" type="email" :class="inputClass" placeholder="email@example.com" />
              <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
                <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
              </Transition>
              <p v-if="profile.payable_type === 'trainer'" class="text-xs text-slate-400 mt-1">
                {{ lang === 'km'
                  ? 'នេះមិនមែនជា login email ទេ — ប្រើ "អញ្ជើញចូល Login" ដើម្បីបង្កើត account login ។'
                  : 'This is not a login email — use "Invite to Login" to create a login account.' }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-lg px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
          {{ lang === 'km'
            ? 'មិនអាចផ្លាស់ប្តូរអ្នកទទួលខុសត្រូវ (User/Trainer) ក្រោយពេលបង្កើតទេ — គ្រាន់តែកែប្រែព័ត៌មានប្រាក់ខែ/តួនាទីប៉ុណ្ណោះ។'
            : "Who this profile is linked to (User/Trainer) can't be changed after creation — only salary/position details below." }}
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_position }}</label>
            <input v-model="form.position" type="text" required :class="inputClass" />
            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
              <p v-if="form.errors.position" class="text-xs text-red-500 mt-1">{{ form.errors.position }}</p>
            </Transition>
          </div>

          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_salary_type }}</label>
            <select v-model="form.salary_type" :class="inputClass">
              <option value="fixed">{{ t.staff_salary_fixed }}</option>
              <option value="hourly">{{ t.staff_salary_hourly }}</option>
              <option value="commission">{{ t.staff_salary_commission }}</option>
              <option value="fixed_commission">{{ t.staff_salary_fixed_commission }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div v-if="form.salary_type === 'fixed' || form.salary_type === 'fixed_commission'">
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_base_salary }}</label>
            <input v-model="form.base_salary" type="number" step="0.01" min="0" :class="inputClass" />
            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
              <p v-if="form.errors.base_salary" class="text-xs text-red-500 mt-1">{{ form.errors.base_salary }}</p>
            </Transition>
          </div>

          <div v-if="form.salary_type === 'hourly'">
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_hourly_rate }}</label>
            <input v-model="form.hourly_rate" type="number" step="0.01" min="0" :class="inputClass" />
            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
              <p v-if="form.errors.hourly_rate" class="text-xs text-red-500 mt-1">{{ form.errors.hourly_rate }}</p>
            </Transition>
          </div>

          <template v-if="form.salary_type === 'commission' || form.salary_type === 'fixed_commission'">
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_commission_percent }}</label>
              <input v-model="form.commission_percent" type="number" step="0.01" min="0" max="100" :class="inputClass" />
              <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
                <p v-if="form.errors.commission_percent" class="text-xs text-red-500 mt-1">{{ form.errors.commission_percent }}</p>
              </Transition>
            </div>
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_commission_source }}</label>
              <select v-model="form.commission_source" :class="inputClass">
                <option value="">{{ t.staff_select_placeholder }}</option>
                <option value="pt_session">{{ t.staff_commission_pt_session }}</option>
                <option value="class_booking">{{ t.staff_commission_class_booking }}</option>
                <option value="payment_referred">{{ t.staff_commission_payment_referred }}</option>
              </select>
            </div>
          </template>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-end">
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_hire_date }}</label>
            <input v-model="form.hire_date" type="date" :class="inputClass" />
          </div>

          <div class="flex items-center gap-2 pb-2.5">
            <input v-model="form.active" type="checkbox" id="active-toggle" class="rounded border-slate-300 text-emerald-500 focus:ring-emerald-500/50" />
            <label for="active-toggle" class="text-sm text-slate-600 dark:text-slate-300">{{ t.staff_active }}</label>
          </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
          <button
            type="submit"
            :disabled="form.processing"
            class="mt-4 px-6 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:hover:scale-100 disabled:hover:shadow-none"
          >
            {{ form.processing ? t.staff_saving : t.staff_save }}
          </button>
          <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <span v-if="form.recentlySuccessful" class="mt-4 text-sm text-emerald-500">✓</span>
          </Transition>
        </div>
      </form>

      <div class="space-y-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ lang === 'km' ? 'QR កូដ Check-in' : 'Check-in QR Code' }}
          </h2>
          <div class="flex justify-center">
            <img
              :src="`/dashboard/staff/${profile.id}/qr?_=${Date.now()}`"
              class="w-40 h-40 border border-slate-200 dark:border-slate-700 rounded-lg p-2 bg-white"
              alt="Staff QR code"
            />
          </div>
          <a
            :href="`/dashboard/staff/${profile.id}/qr`"
            target="_blank"
            class="block text-center text-sm text-emerald-500 hover:underline"
          >
            {{ lang === 'km' ? 'បើក / ទាញយក QR' : 'Open / Download QR' }}
          </a>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4">
          <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Telegram</h2>

          <p v-if="profile.telegram_connected" class="text-sm text-emerald-500 flex items-center gap-1.5">
            <span>✓</span>
            {{ lang === 'km' ? 'បានភ្ជាប់ Telegram រួចហើយ' : 'Telegram connected' }}
          </p>
          <p v-else class="text-sm text-slate-500 dark:text-slate-400">
            {{ lang === 'km' ? 'មិនទាន់ភ្ជាប់ Telegram ទេ' : 'Not connected to Telegram yet' }}
          </p>

          <div class="flex flex-col gap-2">
            <a
              :href="`/dashboard/staff/${profile.id}/connect-telegram?_=${Date.now()}`"
              target="_blank"
              class="w-full text-center px-4 py-2.5 rounded-lg bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium transition-all duration-200"
            >
              {{ profile.telegram_connected
                ? (lang === 'km' ? 'ភ្ជាប់ម្តងទៀត' : 'Reconnect')
                : (lang === 'km' ? 'ភ្ជាប់ Telegram' : 'Connect Telegram') }}
            </a>

            <button
              v-if="profile.telegram_connected"
              type="button"
              @click="resendQr"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all duration-200"
            >
              {{ lang === 'km' ? 'ផ្ញើ QR ម្តងទៀត' : 'Resend QR' }}
            </button>
          </div>
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