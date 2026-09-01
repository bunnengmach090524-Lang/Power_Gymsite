<script setup>
import { ref } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'

const page = usePage()
const form = useForm({ email: '' })
const sent = ref(false)

function submit() {
  form.post('/forgot-password', {
    preserveScroll: true,
    onSuccess: () => { sent.value = true },
  })
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-500/15 flex items-center justify-center">
          <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">ភ្លេចពាក្យសម្ងាត់?</h1>
        <p class="text-slate-400 text-sm mt-1.5">បញ្ចូល email របស់អ្នក យើងនឹងផ្ញើតំណ reset ទៅឲ្យ</p>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-xl">
        <div
          v-if="sent || page.props.flash?.status"
          class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3 mb-5 text-center"
        >
          {{ page.props.flash?.status ?? 'តំណ Reset ត្រូវបានផ្ញើទៅ email របស់អ្នកហើយ សូមពិនិត្យមើល' }}
        </div>

        <form v-if="!sent" @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm text-slate-300 mb-1.5">អ៊ីមែល</label>
            <div class="relative">
              <svg class="w-4.5 h-4.5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <input
                v-model="form.email"
                type="email"
                placeholder="you@example.com"
                autofocus
                class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
            </div>
            <p v-if="form.errors.email" class="text-red-400 text-xs mt-1.5">{{ form.errors.email }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold rounded-lg py-3 transition"
          >
            {{ form.processing ? 'កំពុងផ្ញើ...' : 'ផ្ញើតំណ Reset' }}
            <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
          </button>
        </form>

        <p class="text-center text-sm text-slate-400 mt-6">
          ចាំបានពាក្យសម្ងាត់ហើយ?
          <Link href="/login" class="text-emerald-400 font-medium hover:text-emerald-300">ត្រឡប់ទៅចូលគណនី</Link>
        </p>
      </div>
    </div>
  </div>
</template>