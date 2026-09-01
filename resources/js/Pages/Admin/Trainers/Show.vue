<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  trainer: Object,
  tenant: Object,
})

const { t, lang } = useLang()

function printQr() {
  window.print()
}

const cardNumber = computed(() => `TRN-${String(props.trainer.id).padStart(6, '0')}`)

const issuedAt = computed(() =>
  new Date().toLocaleString(lang.value === 'km' ? 'km-KH' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  })
)
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto animate-fade-in-up">
    <Link href="/dashboard/trainers" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-emerald-500 mb-4 print:hidden">
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
      {{ lang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}
    </Link>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      <!-- LEFT: Trainer info -->
      <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm print:hidden">
        <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-6">
          <img v-if="trainer.photo_url" :src="`${trainer.photo_url}`" class="w-14 h-14 sm:w-16 sm:h-16 rounded-full object-cover shrink-0" />
          <div v-else class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-emerald-500 flex items-center justify-center text-white text-lg sm:text-xl font-semibold shrink-0">
            {{ trainer.name?.[0]?.toUpperCase() }}
          </div>
          <div class="min-w-0">
            <h1 class="text-lg sm:text-xl font-semibold text-slate-900 dark:text-white truncate">{{ trainer.name }}</h1>
            <p v-if="trainer.specialty" class="text-sm text-emerald-600 dark:text-emerald-400 truncate">{{ trainer.specialty }}</p>
          </div>
          <Link
            :href="`/dashboard/trainers/${trainer.id}/edit`"
            class="w-full sm:w-auto sm:ml-auto px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:border-emerald-400 hover:text-emerald-500 transition-all text-center"
          >
            {{ lang === 'km' ? 'កែប្រែ' : 'Edit' }}
          </Link>
        </div>

        <div v-if="trainer.bio" class="mb-6">
          <p class="text-sm font-medium text-slate-900 dark:text-white mb-1">{{ lang === 'km' ? 'ជីវប្រវត្តិសង្ខេប' : 'Bio' }}</p>
          <p class="text-sm text-slate-500 dark:text-slate-400">{{ trainer.bio }}</p>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div><dt class="text-slate-400">{{ lang === 'km' ? 'ម៉ោងចាប់ផ្តើមវេន' : 'Shift start' }}</dt><dd class="text-slate-900 dark:text-white font-medium">{{ trainer.shift_start_time || '—' }}</dd></div>
          <div><dt class="text-slate-400">{{ lang === 'km' ? 'ចំនួន Class' : 'Classes' }}</dt><dd class="text-slate-900 dark:text-white font-medium">{{ trainer.classes_count ?? 0 }}</dd></div>
        </dl>
      </div>

      <!-- RIGHT: Ticket-style QR Card -->
      <div class="lg:col-span-1">
        <div id="qr-invoice-card" class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm print:shadow-none print:border-slate-300 max-w-sm mx-auto lg:max-w-none">

          <!-- Ticket header band -->
          <div class="bg-emerald-600 print:bg-emerald-600 px-5 sm:px-6 py-4 flex items-center gap-3">
            <img v-if="tenant?.logo_url" :src="`${tenant.logo_url}`" class="w-9 h-9 rounded-lg object-cover shrink-0 ring-2 ring-white/30" />
            <div v-else class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center text-white text-sm font-bold shrink-0">
              {{ tenant?.name?.[0] ?? 'G' }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-white truncate">{{ tenant?.name ?? 'GymSite' }}</p>
              <p class="text-[10px] text-emerald-100/90 uppercase tracking-wider truncate">
                {{ lang === 'km' ? 'សំបុត្រចូលហាត់ · គ្រូបង្វឹក' : 'Gym Access Pass · Trainer' }}
              </p>
            </div>
            <span class="ml-auto font-mono text-[11px] text-emerald-50/90 shrink-0">{{ cardNumber }}</span>
          </div>

          <!-- Status stamp -->
          <div class="absolute top-[4.25rem] right-4 sm:right-6 rotate-[-9deg] border-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded">
            {{ lang === 'km' ? 'សកម្ម' : 'Active' }}
          </div>

          <!-- Person block -->
          <div class="flex items-center gap-3 px-5 sm:px-6 pt-5 pb-4">
            <img v-if="trainer.photo_url" :src="`${trainer.photo_url}`" class="w-11 h-11 rounded-full object-cover shrink-0" />
            <div v-else class="w-11 h-11 rounded-full bg-emerald-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">
              {{ trainer.name?.[0]?.toUpperCase() }}
            </div>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ trainer.name }}</p>
              <p class="text-xs text-slate-400 truncate">{{ trainer.specialty || '—' }}</p>
            </div>
          </div>

          <!-- Details as invoice line items -->
          <dl class="grid grid-cols-2 gap-y-3 gap-x-4 px-5 sm:px-6 pb-5 text-xs">
            <div>
              <dt class="text-slate-400">{{ lang === 'km' ? 'ម៉ោងចាប់ផ្តើមវេន' : 'Shift start' }}</dt>
              <dd class="font-medium text-slate-900 dark:text-white mt-0.5">{{ trainer.shift_start_time || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-400">{{ lang === 'km' ? 'ចំនួន Class' : 'Classes' }}</dt>
              <dd class="font-medium text-slate-900 dark:text-white mt-0.5">{{ trainer.classes_count ?? 0 }}</dd>
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
              <img :src="`/dashboard/trainers/${trainer.id}/qr`" alt="Trainer QR" class="w-36 h-36 sm:w-40 sm:h-40" />
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
            :href="`/dashboard/trainers/${trainer.id}/connect-telegram`"
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