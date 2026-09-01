<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  profiles: Array,
  availableUsers: Array,
  availableTrainers: Array,
})

const { t, lang } = useLang()
const page = usePage()

const showModal = ref(false)

const peopleOptions = computed(() => [
  ...props.availableUsers.map(u => ({ value: `user:${u.id}`, label: `${u.name} (${u.role})` })),
  ...props.availableTrainers.map(tr => ({ value: `trainer:${tr.id}`, label: `${tr.name} (${t.value.staff_type_trainer})` })),
])

const form = useForm({
  payable_type: '',
  payable_id: '',
  position: '',
  salary_type: 'fixed',
  base_salary: '',
  hourly_rate: '',
  commission_percent: '',
  commission_source: '',
  hire_date: '',
})

function openAddModal() {
  form.reset()
  showModal.value = true
}

function submitPerson(value) {
  const [type, id] = value.split(':')
  form.payable_type = type
  form.payable_id = id
}

function submit() {
  form.post('/dashboard/staff', {
    preserveScroll: true,
    onSuccess: () => { showModal.value = false; form.reset() },
  })
}

function removeProfile(profile) {
  if (confirm(t.value.staff_confirm_remove)) {
    useForm({}).delete(`/dashboard/staff/${profile.id}`, { preserveScroll: true })
  }
}

function salaryTypeLabel(type) {
  return {
    fixed: t.value.staff_salary_fixed,
    hourly: t.value.staff_salary_hourly,
    commission: t.value.staff_salary_commission,
    fixed_commission: t.value.staff_salary_fixed_commission,
  }[type] ?? type
}

function salaryDisplay(profile) {
  if (profile.salary_type === 'fixed') return `$${profile.base_salary ?? 0}/mo`
  if (profile.salary_type === 'hourly') return `$${profile.hourly_rate ?? 0}/hr`
  if (profile.salary_type === 'commission') return `${profile.commission_percent ?? 0}%`
  if (profile.salary_type === 'fixed_commission') return `$${profile.base_salary ?? 0} + ${profile.commission_percent ?? 0}%`
  return '—'
}

// ===== Invite to Login (trainer -> user account) =====
const showInviteModal = ref(false)
const invitingProfile = ref(null)

const inviteForm = useForm({
  email: '',
})

function openInviteModal(profile) {
  invitingProfile.value = profile
  inviteForm.reset()
  inviteForm.clearErrors()
  // Auto-fill with the trainer's saved email (if they have one on file),
  // so the admin doesn't have to retype it — they can still edit it.
  inviteForm.email = profile.email ?? ''
  showInviteModal.value = true
}

function closeInviteModal() {
  showInviteModal.value = false
  invitingProfile.value = null
}

function submitInvite() {
  if (!invitingProfile.value) return

  inviteForm.post(`/dashboard/staff/${invitingProfile.value.id}/invite-login`, {
    preserveScroll: true,
    onSuccess: () => closeInviteModal(),
  })
}
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ t.staff_title }}</h1>
      <button
        @click="openAddModal"
        class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-5 py-2.5 text-base transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        {{ t.staff_add_new }}
      </button>
    </div>

    <div
      v-if="page.props.flash?.success"
      class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-4 transition-opacity duration-300"
    >
      {{ page.props.flash.success }}
    </div>
    <div
      v-if="page.props.errors && Object.keys(page.props.errors).length"
      class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-sm rounded-lg px-4 py-3 mb-4"
    >
      {{ Object.values(page.props.errors)[0] }}
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-x-auto transition-colors duration-300">
      <table class="w-full text-base min-w-[820px]">
        <thead>
          <tr class="text-left text-slate-500 border-b border-slate-200 dark:border-slate-800">
            <th class="px-5 py-3.5 font-medium">{{ t.staff_table_name }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.staff_table_position }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.staff_table_type }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.staff_table_salary }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.staff_table_status }}</th>
            <th class="px-5 py-3.5 font-medium text-right">{{ t.staff_table_actions }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="profile in profiles"
            :key="profile.id"
            class="border-b border-slate-100 dark:border-slate-800/50 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/40"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <img v-if="profile.photo_url" :src="profile.photo_url" class="w-9 h-9 rounded-full object-cover" />
                <div v-else class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold">
                  {{ profile.name?.[0]?.toUpperCase() ?? '?' }}
                </div>
                <p class="text-slate-900 dark:text-white font-medium truncate">{{ profile.name }}</p>
              </div>
            </td>
            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">{{ profile.position }}</td>
            <td class="px-5 py-3.5">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                {{ profile.payable_type === 'trainer' ? t.staff_type_trainer : t.staff_type_user }}
              </span>
              <!-- Login status badge: only meaningful for trainer-type rows -->
              <span
                v-if="profile.payable_type === 'user'"
                class="ml-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
              >
                {{ lang === 'km' ? '✓ មាន Login' : '✓ Has login' }}
              </span>
            </td>
            <td class="px-5 py-3.5 text-slate-700 dark:text-slate-200">
              <p>{{ salaryDisplay(profile) }}</p>
              <p class="text-xs text-slate-400">{{ salaryTypeLabel(profile.salary_type) }}</p>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex flex-col gap-1">
                <span
                  v-if="profile.active"
                  class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 text-sm font-medium"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  {{ t.staff_active }}
                </span>
                <span v-else class="inline-flex items-center gap-1.5 text-slate-400 text-sm font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                  {{ t.staff_inactive }}
                </span>
                <span v-if="profile.payable_type === 'user' && profile.invitation_accepted_at === null" class="text-[11px] text-amber-500">
                  {{ lang === 'km' ? '(មិនទាន់ login ចូល)' : '(login not accepted yet)' }}
                </span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-right space-x-3 whitespace-nowrap">
              <a
                :href="`/dashboard/staff/${profile.id}/qr`"
                target="_blank"
                class="text-slate-500 dark:text-slate-400 hover:text-emerald-500 text-sm font-medium transition-colors"
              >
                {{ t.staff_view_qr }}
              </a>
              <a
                v-if="profile.payable_type === 'trainer'"
                :href="`/dashboard/staff/${profile.id}/self-service-qr`"
                target="_blank"
                class="text-purple-500 dark:text-purple-400 hover:text-purple-600 text-sm font-medium transition-colors"
              >
                {{ lang === 'km' ? 'QR ព័ត៌មានផ្ទាល់ខ្លួន' : 'Self-Service QR' }}
              </a>
              <!-- Invite to Login: only for trainer-type profiles (they don't have a User account yet) -->
              <button
                v-if="profile.payable_type === 'trainer'"
                @click="openInviteModal(profile)"
                class="text-sky-500 dark:text-sky-400 hover:text-sky-600 dark:hover:text-sky-300 text-sm font-medium transition-colors"
              >
                {{ lang === 'km' ? 'អញ្ជើញចូល Login' : 'Invite to Login' }}
              </button>
              <Link
                :href="`/dashboard/staff/${profile.id}/edit`"
                class="text-emerald-600 dark:text-emerald-400 hover:underline text-sm font-medium transition-colors"
              >
                {{ lang === 'km' ? 'កែប្រែ' : 'Edit' }}
              </Link>
              <button
                @click="removeProfile(profile)"
                class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 text-sm font-medium transition-colors"
              >
                {{ t.staff_remove }}
              </button>
            </td>
          </tr>
          <tr v-if="!profiles.length">
            <td colspan="6" class="px-5 py-10 text-center text-slate-400 dark:text-slate-500">{{ t.staff_empty }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ADD MODAL -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div v-if="showModal" class="fixed inset-0 bg-slate-950/60 flex items-center justify-center z-[60] p-4" @click.self="showModal = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t.staff_add_title }}</h2>
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_person }}</label>
              <select
                @change="submitPerson($event.target.value)"
                required
                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              >
                <option value="" disabled selected>{{ t.staff_select_placeholder }}</option>
                <option v-for="opt in peopleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <p v-if="!peopleOptions.length" class="text-xs text-amber-500 mt-1">{{ t.staff_no_available_people }}</p>
              <p v-if="form.errors.payable_id" class="text-xs text-red-500 mt-1">{{ form.errors.payable_id }}</p>
            </div>

            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_position }}</label>
              <input
                v-model="form.position"
                type="text"
                required
                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              />
            </div>

            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_salary_type }}</label>
              <select v-model="form.salary_type" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                <option value="fixed">{{ t.staff_salary_fixed }}</option>
                <option value="hourly">{{ t.staff_salary_hourly }}</option>
                <option value="commission">{{ t.staff_salary_commission }}</option>
                <option value="fixed_commission">{{ t.staff_salary_fixed_commission }}</option>
              </select>
            </div>

            <div v-if="form.salary_type === 'fixed' || form.salary_type === 'fixed_commission'">
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_base_salary }}</label>
              <input v-model="form.base_salary" type="number" step="0.01" min="0" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
            </div>

            <div v-if="form.salary_type === 'hourly'">
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_hourly_rate }}</label>
              <input v-model="form.hourly_rate" type="number" step="0.01" min="0" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
            </div>

            <template v-if="form.salary_type === 'commission' || form.salary_type === 'fixed_commission'">
              <div>
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_commission_percent }}</label>
                <input v-model="form.commission_percent" type="number" step="0.01" min="0" max="100" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
              </div>
              <div>
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_commission_source }}</label>
                <select v-model="form.commission_source" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                  <option value="">{{ t.staff_select_placeholder }}</option>
                  <option value="pt_session">{{ t.staff_commission_pt_session }}</option>
                  <option value="class_booking">{{ t.staff_commission_class_booking }}</option>
                  <option value="payment_referred">{{ t.staff_commission_payment_referred }}</option>
                </select>
              </div>
            </template>

            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.staff_field_hire_date }}</label>
              <input v-model="form.hire_date" type="date" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50" />
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button type="submit" :disabled="form.processing" class="flex-1 px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium transition-colors disabled:opacity-50">
                {{ form.processing ? t.staff_saving : t.staff_save }}
              </button>
              <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                {{ t.staff_cancel }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- INVITE TO LOGIN MODAL -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div v-if="showInviteModal" class="fixed inset-0 bg-slate-950/60 flex items-center justify-center z-[60] p-4" @click.self="closeInviteModal">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 w-full max-w-md">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-1.5">
            {{ lang === 'km' ? 'អញ្ជើញចូល Login' : 'Invite to Login' }}
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            {{ lang === 'km'
              ? `បង្កើត account login សម្រាប់ ${invitingProfile?.name ?? ''} ។ email invite នឹងត្រូវផ្ញើទៅភ្លាមៗ។`
              : `Create a login account for ${invitingProfile?.name ?? ''}. An invite email will be sent immediately.` }}
          </p>

          <form @submit.prevent="submitInvite" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">
                {{ lang === 'km' ? 'អ៊ីមែល' : 'Email' }}
              </label>
              <input
                v-model="inviteForm.email"
                type="email"
                required
                placeholder="trainer@example.com"
                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/50"
              />
              <p v-if="inviteForm.errors.email" class="text-xs text-red-500 mt-1">{{ inviteForm.errors.email }}</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button
                type="submit"
                :disabled="inviteForm.processing"
                class="flex-1 px-5 py-2.5 rounded-lg bg-sky-500 hover:bg-sky-600 text-white text-sm font-medium transition-colors disabled:opacity-50"
              >
                {{ inviteForm.processing
                  ? (lang === 'km' ? 'កំពុងផ្ញើ...' : 'Sending...')
                  : (lang === 'km' ? 'ផ្ញើការអញ្ជើញ' : 'Send Invite') }}
              </button>
              <button
                type="button"
                @click="closeInviteModal"
                :disabled="inviteForm.processing"
                class="px-5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              >
                {{ t.staff_cancel }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
</style>