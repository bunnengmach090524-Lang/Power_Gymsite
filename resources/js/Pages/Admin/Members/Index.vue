<script setup>
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  members: Object,
  search: { type: String, default: '' },
})

const { t, lang } = useLang()
const page = usePage()

const canDelete = computed(() => page.props.auth?.user?.role === 'gym_admin')

const searchQuery = ref(props.search ?? '')

function submitSearch() {
  router.get('/dashboard/members', { search: searchQuery.value || undefined }, {
    preserveState: true,
    replace: true,
  })
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// A member has an "active" subscription if the eager-loaded `subscriptions`
// relation (limited to 1 active, non-expired row server-side) is non-empty.
function hasActiveSubscription(member) {
  return Array.isArray(member.subscriptions) && member.subscriptions.length > 0
}

function destroy(member) {
  if (confirm(`${t.value.confirm_delete_prefix} ${member.name}?`)) {
    router.delete(`/dashboard/members/${member.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
      <div>
        <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white">👥 {{ t.members_title }}</h1>
        <p class="text-sm text-slate-400 mt-1">
          {{ lang === 'km' ? `សរុប ${members.total ?? members.data.length} នាក់` : `${members.total ?? members.data.length} total members` }}
        </p>
      </div>
      <Link
        href="/dashboard/members/create"
        class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-4 sm:px-5 py-2 sm:py-2.5 text-sm sm:text-base transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        {{ t.members_add_new }}
      </Link>
    </div>

    <!-- Search bar -->
    <form @submit.prevent="submitSearch" class="flex gap-2 my-5">
      <input
        v-model="searchQuery"
        type="text"
        :placeholder="lang === 'km' ? 'ស្វែងរកតាមឈ្មោះ ឬ Email...' : 'Search by name or email...'"
        class="flex-1 px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm transition-all duration-200 hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30"
      />
      <button
        type="submit"
        class="px-5 py-2.5 rounded-lg bg-slate-900 dark:bg-slate-700 text-white text-sm font-medium transition-all duration-200 hover:bg-slate-800 dark:hover:bg-slate-600 hover:scale-[1.02] active:scale-[0.98] shrink-0"
      >
        🔍 {{ lang === 'km' ? 'ស្វែងរក' : 'Search' }}
      </button>
    </form>

    <!-- Card grid -->
    <div v-if="members.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="(member, i) in members.data"
        :key="member.id"
        class="member-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-black/30 hover:border-emerald-300 dark:hover:border-emerald-700/50"
        :style="{ animationDelay: `${i * 50}ms` }"
      >
        <div class="flex items-start justify-between gap-2 mb-3">
          <Link :href="`/dashboard/members/${member.id}`" class="flex items-center gap-3 min-w-0 group">
            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-semibold shrink-0">
              {{ member.name?.[0]?.toUpperCase() ?? '?' }}
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <p class="font-semibold text-slate-900 dark:text-white truncate group-hover:text-emerald-500 transition-colors duration-200">
                  {{ member.name }}
                </p>
                <span
                  v-if="hasActiveSubscription(member)"
                  class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                >
                  {{ lang === 'km' ? 'សកម្ម' : 'Active' }}
                </span>
              </div>
              <p class="text-xs text-slate-400 truncate">{{ member.email ?? '—' }}</p>
            </div>
          </Link>
          <div class="flex items-center gap-2.5 shrink-0">
            <Link
              :href="`/dashboard/members/${member.id}/edit`"
              class="text-slate-400 hover:text-emerald-500 transition-colors duration-150"
              :title="t.action_edit"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </Link>
            <button
              v-if="canDelete"
              @click="destroy(member)"
              class="text-slate-400 hover:text-red-500 transition-colors duration-150"
              :title="t.action_delete"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800 mb-4">
          <div>
            <p class="text-xs text-slate-400 mb-0.5">{{ lang === 'km' ? 'ការចុះឈ្មោះ' : 'Subscriptions' }}</p>
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ member.subscriptions_count ?? 0 }}</p>
          </div>
          <div class="text-right">
            <p class="text-xs text-slate-400 mb-0.5">{{ lang === 'km' ? 'ចំណាយសរុប' : 'Total spent' }}</p>
            <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">${{ Number(member.payments_sum_amount ?? 0).toFixed(2) }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-400 mb-4">
          <span>{{ lang === 'km' ? 'ចូលរួម' : 'Joined' }} {{ formatDate(member.joined_date) }}</span>
          <span v-if="member.phone">{{ member.phone }}</span>
        </div>

        <Link
          :href="`/dashboard/members/${member.id}`"
          class="block w-full text-center py-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm font-medium transition-all duration-200 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400"
        >
          {{ lang === 'km' ? 'មើលព័ត៌មានលម្អិត' : 'View Details' }} →
        </Link>
      </div>
    </div>

    <div v-else class="bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl py-16 flex flex-col items-center text-center">
      <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3.13a4 4 0 00-8 0" />
        </svg>
      </div>
      <p class="text-slate-400">
        {{ searchQuery ? (lang === 'km' ? 'រកមិនឃើញលទ្ធផលទេ' : 'No results found') : t.members_empty }}
      </p>
    </div>

    <!-- Pagination -->
    <div v-if="members.links && members.links.length > 3" class="flex flex-wrap items-center justify-center gap-1.5 mt-8">
      <template v-for="(link, i) in members.links" :key="i">
        <Link
          v-if="link.url"
          :href="link.url"
          preserve-state
          preserve-scroll
          class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors duration-150"
          :class="link.active
            ? 'bg-emerald-500 text-slate-950'
            : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-emerald-400 hover:text-emerald-500'"
          v-html="link.label"
        />
        <span
          v-else
          class="px-3.5 py-2 rounded-lg text-sm text-slate-300 dark:text-slate-600"
          v-html="link.label"
        />
      </template>
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
.member-card {
  animation: fade-in-up 0.4s ease-out backwards;
}
</style>