<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({ members: Array })
const { t, lang } = useLang()
const page = usePage()

const showInviteModal = ref(false)

const inviteForm = useForm({
  email: '',
  role: 'staff',
})

const roleForms = {} // per-row role update forms, created lazily

function getRoleForm(member) {
  if (!roleForms[member.id]) {
    roleForms[member.id] = useForm({ role: member.role })
  }
  return roleForms[member.id]
}

function sendInvite() {
  inviteForm.post('/dashboard/team/invite', {
    preserveScroll: true,
    onSuccess: () => {
      inviteForm.reset()
      showInviteModal.value = false
    },
  })
}

function resendInvite(member) {
  useForm({}).post(`/dashboard/team/${member.id}/resend`, { preserveScroll: true })
}

function updateRole(member) {
  const form = getRoleForm(member)
  form.patch(`/dashboard/team/${member.id}/role`, { preserveScroll: true })
}

function removeMember(member) {
  if (confirm(t.value.team_confirm_remove)) {
    useForm({}).delete(`/dashboard/team/${member.id}`, { preserveScroll: true })
  }
}

function formatDate(dateStr) {
  if (!dateStr) return null
  return new Date(dateStr).toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ t.team_title }}</h1>
      <button
        @click="showInviteModal = true"
        class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-5 py-2.5 text-base transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        + {{ t.team_invite_new }}
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
      <table class="w-full text-base min-w-[720px]">
        <thead>
          <tr class="text-left text-slate-500 border-b border-slate-200 dark:border-slate-800">
            <th class="px-5 py-3.5 font-medium">{{ t.team_table_member }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.team_table_role }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.team_table_status }}</th>
            <th class="px-5 py-3.5 font-medium">{{ t.team_table_joined }}</th>
            <th class="px-5 py-3.5 font-medium text-right">{{ t.team_table_actions }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="member in members"
            :key="member.id"
            class="border-b border-slate-100 dark:border-slate-800/50 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/40"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <img v-if="member.avatar" :src="member.avatar" class="w-9 h-9 rounded-full object-cover" />
                <div v-else class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold">
                  {{ member.name?.[0]?.toUpperCase() ?? '?' }}
                </div>
                <div class="min-w-0">
                  <p class="text-slate-900 dark:text-white font-medium truncate">{{ member.name }}</p>
                  <p class="text-slate-400 text-sm truncate">{{ member.email }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <span
                v-if="member.is_owner"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400"
              >
                ⭐ {{ t.team_role_owner }}
              </span>
              <select
                v-else
                v-model="getRoleForm(member).role"
                @change="updateRole(member)"
                class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
              >
                <option value="gym_admin">{{ t.team_role_admin }}</option>
                <option value="staff">{{ t.team_role_staff }}</option>
              </select>
            </td>
            <td class="px-5 py-3.5">
              <span
                v-if="member.invitation_accepted_at"
                class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 text-sm font-medium"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                {{ t.team_status_active }}
              </span>
              <button
                v-else
                @click="resendInvite(member)"
                class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 text-sm font-medium hover:underline"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                {{ t.team_status_pending }} — {{ t.team_resend }}
              </button>
            </td>
            <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
              {{ formatDate(member.invitation_accepted_at) ?? '—' }}
            </td>
            <td class="px-5 py-3.5 text-right">
              <button
                v-if="!member.is_owner"
                @click="removeMember(member)"
                class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 text-sm font-medium transition-colors"
              >
                {{ t.team_remove }}
              </button>
              <span v-else class="text-slate-300 dark:text-slate-600 text-sm">—</span>
            </td>
          </tr>
          <tr v-if="!members.length">
            <td colspan="5" class="px-5 py-10 text-center text-slate-400 dark:text-slate-500">{{ t.team_empty }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- INVITE MODAL -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div v-if="showInviteModal" class="fixed inset-0 bg-slate-950/60 flex items-center justify-center z-[60] p-4" @click.self="showInviteModal = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 w-full max-w-md">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t.team_invite_title }}</h2>
          <form @submit.prevent="sendInvite" class="space-y-4">
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.team_field_email }}</label>
              <input
                v-model="inviteForm.email"
                type="email"
                required
                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              />
              <p v-if="inviteForm.errors.email" class="text-xs text-red-500 mt-1">{{ inviteForm.errors.email }}</p>
            </div>
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.team_field_role }}</label>
              <select v-model="inviteForm.role" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                <option value="staff">{{ t.team_role_staff }}</option>
                <option value="gym_admin">{{ t.team_role_admin }}</option>
              </select>
            </div>
            <div class="flex items-center gap-3 pt-2">
              <button type="submit" :disabled="inviteForm.processing" class="flex-1 px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium transition-colors disabled:opacity-50">
                {{ inviteForm.processing ? t.team_sending : t.team_send_invite }}
              </button>
              <button type="button" @click="showInviteModal = false" class="px-5 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                {{ t.team_cancel }}
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