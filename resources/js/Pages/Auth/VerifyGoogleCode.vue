<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3'

const page = usePage()

defineProps({
  maskedEmail: String,
})

const form = useForm({ code: '' })

function submit() {
  form.post('/auth/google/verify')
}

function resend() {
  router.post('/auth/google/resend', {}, { preserveScroll: true })
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-500/15 flex items-center justify-center">
          <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">GymSite</h1>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-xl">
        <h2 class="text-lg font-semibold text-white mb-2 text-center">ផ្ទៀងផ្ទាត់អត្តសញ្ញាណ</h2>
        <p class="text-sm text-slate-400 text-center mb-6">
          យើងបានផ្ញើលេខកូដ 6 ខ្ទង់ទៅ
          <span class="text-emerald-400 font-medium">{{ maskedEmail }}</span>
        </p>

        <div
          v-if="page.props.flash?.success"
          class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3 mb-4 text-center"
        >
          {{ page.props.flash.success }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <input
              v-model="form.code"
              type="text"
              inputmode="numeric"
              maxlength="6"
              autocomplete="one-time-code"
              placeholder="000000"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-3 text-white text-center text-2xl tracking-[0.5em] placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
            />
            <p v-if="form.errors.code" class="text-red-400 text-xs mt-2 text-center">{{ form.errors.code }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing || form.code.length !== 6"
            class="w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold rounded-lg py-3 transition"
          >
            {{ form.processing ? 'កំពុងផ្ទៀងផ្ទាត់...' : 'បញ្ជាក់' }}
          </button>
        </form>

        <p class="text-center text-sm text-slate-400 mt-6">
          មិនទាន់ទទួលបានលេខកូដ?
          <button @click="resend" class="text-emerald-400 hover:text-emerald-300 font-medium">ផ្ញើម្តងទៀត</button>
        </p>
      </div>
    </div>
  </div>
</template>