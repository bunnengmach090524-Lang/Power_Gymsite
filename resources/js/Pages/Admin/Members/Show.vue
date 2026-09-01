<script setup>
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  member: Object,
  tenant: Object,
  availableClasses: Array,
})

const { t, lang } = useLang()
const page = usePage()

const activeSub = props.member.subscriptions?.[0] ?? null

const memberStatus = computed(() => (activeSub ? 'active' : 'none'))

const stampClass = computed(() => ({
  active: 'border-emerald-500 text-emerald-600 dark:text-emerald-400',
  expiring: 'border-amber-500 text-amber-600 dark:text-amber-400',
  expired: 'border-red-500 text-red-600 dark:text-red-400',
  none: 'border-slate-400 text-slate-500 dark:text-slate-400',
}[memberStatus.value]))

const cardNumber = computed(() => `MBR-${String(props.member.id).padStart(6, '0')}`)

const issuedAt = computed(() =>
  new Date().toLocaleString(lang.value === 'km' ? 'km-KH' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  })
)

function printQr() {
  window.print()
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString(lang.value === 'km' ? 'km-KH' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function formatTime(t) {
  return t ? t.slice(0, 5) : ''
}

const dayLabels = { mon: 'ចន្ទ', tue: 'អង្គារ', wed: 'ពុធ', thu: 'ព្រហស្បតិ៍', fri: 'សុក្រ', sat: 'សៅរ៍', sun: 'អាទិត្យ' }
const dayLabelsEn = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri', sat: 'Sat', sun: 'Sun' }

// ===== Class booking management =====
const bookedClassIds = computed(() => new Set(props.member.classBookings?.map(b => b.class_id) ?? []))
const bookableClasses = computed(() => props.availableClasses?.filter(c => !bookedClassIds.value.has(c.id)) ?? [])

const selectedClassId = ref('')
const adding = ref(false)

function addToClass() {
  if (!selectedClassId.value) return
  adding.value = true
  router.post(
    `/dashboard/members/${props.member.id}/classes`,
    { class_id: selectedClassId.value },
    {
      preserveScroll: true,
      onFinish: () => { adding.value = false; selectedClassId.value = '' },
    }
  )
}

function removeFromClass(booking) {
  if (!confirm(lang.value === 'km' ? 'ដកសមាជិកនេះចេញពី class នេះមែនទេ?' : 'Remove this member from the class?')) return
  router.delete(`/dashboard/members/${props.member.id}/classes/${booking.id}`, { preserveScroll: true })
}
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto animate-fade-in-up">
    <Link href="/dashboard/members" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 mb-4 print:hidden">
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
      {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
    </Link>

    <!-- Flash -->
    <div
      v-if="page.props.flash?.success"
      class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-5 print:hidden"
    >
      {{ page.props.flash.success }}
    </div>
    <div
      v-if="page.props.errors && Object.keys(page.props.errors).length"
      class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-sm rounded-lg px-4 py-3 mb-5 print:hidden"
    >
      {{ Object.values(page.props.errors)[0] }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      <!-- LEFT: Member info -->
      <div class="lg:col-span-2 space-y-4 sm:space-y-6 print:hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm">
          <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-6">
            <img v-if="member.photo_url" :src="member.photo_url" class="w-14 h-14 sm:w-16 sm:h-16 rounded-full object-cover shrink-0" />
            <div v-else class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-emerald-500 flex items-center justify-center text-white text-lg sm:text-xl font-semibold shrink-0">
              {{ member.name?.[0]?.toUpperCase() }}
            </div>
            <div class="min-w-0">
              <h1 class="text-lg sm:text-xl font-semibold text-slate-900 dark:text-white truncate">{{ member.name }}</h1>
              <p class="text-sm text-slate-400 truncate">{{ member.phone }}</p>
            </div>
            <Link
              :href="`/dashboard/members/${member.id}/edit`"
              class="w-full sm:w-auto sm:ml-auto px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:border-emerald-400 hover:text-emerald-500 transition-all text-center"
            >
              {{ lang === 'km' ? 'កែប្រែ' : 'Edit' }}
            </Link>
          </div>

          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-400">{{ lang === 'km' ? 'អ៊ីមែល' : 'Email' }}</dt><dd class="text-slate-900 dark:text-white font-medium break-words">{{ member.email || '—' }}</dd></div>
            <div><dt class="text-slate-400">{{ lang === 'km' ? 'ភេទ' : 'Gender' }}</dt><dd class="text-slate-900 dark:text-white font-medium">{{ member.gender || '—' }}</dd></div>
            <div><dt class="text-slate-400">{{ lang === 'km' ? 'ថ្ងៃខែឆ្នាំកំណើត' : 'Date of birth' }}</dt><dd class="text-slate-900 dark:text-white font-medium">{{ formatDate(member.date_of_birth) }}</dd></div>
            <div><dt class="text-slate-400">{{ lang === 'km' ? 'ចូលរួមនៅ' : 'Joined' }}</dt><dd class="text-slate-900 dark:text-white font-medium">{{ formatDate(member.joined_date) }}</dd></div>
          </dl>

          <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
            <p class="text-sm font-medium text-slate-900 dark:text-white mb-2">{{ lang === 'km' ? 'សមាជិកភាព' : 'Membership' }}</p>
            <div v-if="activeSub" class="flex flex-wrap items-center gap-2">
              <span class="text-sm text-slate-500 dark:text-slate-400">{{ activeSub.plan_name ?? activeSub.membership_plan?.name }}</span>
              <span class="text-xs text-slate-400">{{ lang === 'km' ? 'ផុតកំណត់' : 'ends' }} {{ formatDate(activeSub.end_date) }}</span>
            </div>
            <p v-else class="text-sm text-slate-400">{{ lang === 'km' ? 'គ្មានសកម្ម' : 'No active plan' }}</p>
          </div>
        </div>

        <!-- Class Bookings -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm">
          <p class="text-sm font-medium text-slate-900 dark:text-white mb-4">{{ lang === 'km' ? 'Class ដែលបានចុះឈ្មោះ' : 'Enrolled Classes' }}</p>

          <div v-if="member.classBookings?.length" class="space-y-2 mb-4">
            <div
              v-for="booking in member.classBookings"
              :key="booking.id"
              class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 dark:border-slate-800 px-4 py-3"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ booking.gym_class?.name }}</p>
                <p class="text-xs text-slate-400">
                  {{ lang === 'km' ? dayLabels[booking.gym_class?.schedule_day] : dayLabelsEn[booking.gym_class?.schedule_day] }}
                  · {{ formatTime(booking.gym_class?.start_time) }}–{{ formatTime(booking.gym_class?.end_time) }}
                </p>
              </div>
              <button
                @click="removeFromClass(booking)"
                class="shrink-0 text-red-500 dark:text-red-400 hover:text-red-600 text-sm font-medium transition-colors duration-150"
              >
                {{ lang === 'km' ? 'ដកចេញ' : 'Remove' }}
              </button>
            </div>
          </div>
          <p v-else class="text-sm text-slate-400 mb-4">{{ lang === 'km' ? 'សមាជិកមិនទាន់ចុះឈ្មោះ class ណាមួយទេ' : 'Not enrolled in any class yet' }}</p>

          <div class="flex items-center gap-2">
            <select
              v-model="selectedClassId"
              class="flex-1 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
            >
              <option value="" disabled>{{ lang === 'km' ? '-- ជ្រើសរើស class --' : '-- Select a class --' }}</option>
              <option v-for="c in bookableClasses" :key="c.id" :value="c.id">
                {{ c.name }} · {{ lang === 'km' ? dayLabels[c.schedule_day] : dayLabelsEn[c.schedule_day] }} {{ formatTime(c.start_time) }}
                ({{ c.spots_left }}/{{ c.capacity }} {{ lang === 'km' ? 'នៅសល់' : 'left' }})
              </option>
            </select>
            <button
              @click="addToClass"
              :disabled="!selectedClassId || adding"
              class="shrink-0 text-sm font-medium px-4 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all duration-200 disabled:opacity-50"
            >
              {{ lang === 'km' ? '+ បន្ថែម' : '+ Add' }}
            </button>
          </div>
          <p v-if="!bookableClasses.length && availableClasses?.length" class="text-xs text-slate-400 mt-2">
            {{ lang === 'km' ? 'សមាជិកបានចុះឈ្មោះ class ទាំងអស់រួចហើយ' : 'Member is enrolled in every available class' }}
          </p>
          <p v-if="!availableClasses?.length" class="text-xs text-slate-400 mt-2">
            {{ lang === 'km' ? 'មិនទាន់មាន class ណាមួយសម្រាប់ gym នេះទេ' : 'No classes exist for this gym yet' }}
          </p>
        </div>
      </div>

      <!-- RIGHT: Ticket-style QR Card -->
      <div class="lg:col-span-1">
        <div id="qr-invoice-card" class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm print:shadow-none print:border-slate-300 max-w-sm mx-auto lg:max-w-none">

          <!-- Ticket header band -->
          <div class="bg-emerald-600 print:bg-emerald-600 px-5 sm:px-6 py-4 flex items-center gap-3">
            <img v-if="tenant?.logo_url" :src="tenant.logo_url" class="w-9 h-9 rounded-lg object-cover shrink-0 ring-2 ring-white/30" />
            <div v-else class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center text-white text-sm font-bold shrink-0">
              {{ tenant?.name?.[0] ?? 'G' }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-white truncate">{{ tenant?.name ?? 'GymSite' }}</p>
              <p class="text-[10px] text-emerald-100/90 uppercase tracking-wider truncate">
                {{ lang === 'km' ? 'សំបុត្រចូលហាត់ · សមាជិក' : 'Gym Access Pass · Member' }}
              </p>
            </div>
            <span class="ml-auto font-mono text-[11px] text-emerald-50/90 shrink-0">{{ cardNumber }}</span>
          </div>

          <!-- Status stamp -->
          <div
            class="absolute top-[4.25rem] right-4 sm:right-6 rotate-[-9deg] border-2 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded"
            :class="stampClass"
          >
            {{ activeSub ? (lang === 'km' ? 'សកម្ម' : 'Active') : (lang === 'km' ? 'អសកម្ម' : 'Inactive') }}
          </div>

          <!-- Person block -->
          <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 pb-4">
            <img v-if="member.photo_url" :src="member.photo_url" class="w-11 h-11 rounded-full object-cover shrink-0" />
            <div v-else class="w-11 h-11 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
              {{ member.name?.[0]?.toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ member.name }}</p>
              <p class="text-xs text-slate-400 truncate">{{ member.phone || member.email }}</p>
            </div>
          </div>

          <!-- Details as invoice line items -->
          <dl class="grid grid-cols-2 gap-y-3 gap-x-4 px-5 sm:px-6 pb-5 text-xs">
            <div>
              <dt class="text-slate-400">{{ lang === 'km' ? 'គម្រោង' : 'Plan' }}</dt>
              <dd class="font-medium text-slate-900 dark:text-white mt-0.5 truncate">
                {{ activeSub ? (activeSub.plan_name ?? activeSub.membership_plan?.name) : '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-slate-400">{{ lang === 'km' ? 'ផុតកំណត់' : 'Expires' }}</dt>
              <dd class="font-medium text-slate-900 dark:text-white mt-0.5">{{ activeSub ? formatDate(activeSub.end_date) : '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-400">{{ lang === 'km' ? 'ចូលរួមតាំងពី' : 'Member since' }}</dt>
              <dd class="font-medium text-slate-900 dark:text-white mt-0.5">{{ formatDate(member.joined_date) }}</dd>
            </div>
            <div>
              <dt class="text-slate-400">{{ lang === 'km' ? 'ទំនាក់ទំនង' : 'Contact' }}</dt>
              <dd class="font-medium text-slate-900 dark:text-white mt-0.5 truncate">{{ member.phone || member.email || '—' }}</dd>
            </div>
          </dl>

          <!-- Tear line with punch notches -->
          <div class="relative">
            <div class="border-t-2 border-dashed border-slate-200 dark:border-slate-700"></div>
            <span class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 print:bg-white"></span>
            <span class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 print:bg-white"></span>
          </div>

          <!-- QR stub -->
          <div class="flex flex-col items-center py-6 sm:py-7 px-5 sm:px-6">
            <div class="p-3 bg-white border border-slate-100 rounded-xl">
              <img :src="`/dashboard/members/${member.id}/qr`" alt="Member QR" class="w-36 h-36 sm:w-40 sm:h-40" />
            </div>
            <p class="text-xs text-slate-400 mt-3 text-center">{{ lang === 'km' ? 'ស្កេនដើម្បី check-in' : 'Scan to check in' }}</p>

            <div class="h-5 w-36 mt-4 opacity-70 print:opacity-100 bg-[repeating-linear-gradient(90deg,#0f172a_0px,#0f172a_2px,transparent_2px,transparent_4px)] dark:bg-[repeating-linear-gradient(90deg,#94a3b8_0px,#94a3b8_2px,transparent_2px,transparent_4px)]"></div>
            <p class="font-mono text-[11px] text-slate-400 mt-1.5 tracking-widest">{{ cardNumber }}</p>
          </div>

          <!-- Footer -->
          <div class="px-5 sm:px-6 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 print:bg-white text-center">
            <p class="text-[10px] text-slate-400">
              {{ lang === 'km' ? 'បង្ហាញកាតនេះនៅតុទទួលភ្ញៀវសម្រាប់ការផ្ទៀងផ្ទាត់' : 'Present this pass at the front desk for verification.' }}
            </p>
            <p class="text-[10px] text-slate-300 dark:text-slate-600 mt-0.5">{{ lang === 'km' ? 'ចេញនៅ' : 'Issued' }} {{ issuedAt }}</p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 mt-4 print:hidden max-w-sm mx-auto lg:max-w-none">
          <button
            @click="printQr"
            class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-sm font-medium transition-all duration-200"
          >
            {{ lang === 'km' ? 'បោះពុម្ព' : 'Print' }}
          </button>
          <a
            :href="`/dashboard/members/${member.id}/connect-telegram`"
            target="_blank"
            class="flex-1 px-4 py-2.5 rounded-xl border border-sky-300 text-sky-600 hover:bg-sky-50 text-sm font-medium transition-all duration-200 text-center"
          >
            {{ lang === 'km' ? 'ភ្ជាប់ Telegram' : 'Connect Telegram' }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fade-in-up { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in-up { animation: fade-in-up 0.35s ease-out; }
@media print {
  #qr-invoice-card { break-inside: avoid; }
}
</style>