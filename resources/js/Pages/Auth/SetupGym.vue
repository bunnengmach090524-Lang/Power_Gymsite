<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  gym_name: '',
})

function submit() {
  form.post('/setup-gym')
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- step indicator -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <span class="w-8 h-1.5 rounded-full bg-emerald-500"></span>
        <span class="w-8 h-1.5 rounded-full bg-emerald-500"></span>
        <span class="w-8 h-1.5 rounded-full bg-slate-800"></span>
      </div>

      <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-emerald-500 flex items-center justify-center">
          <svg class="w-7 h-7 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m16 0v-3.5M5 21v-3.5M9 7h1m-1 4h1m4-4h1m-1 4h1M9 21v-4a1 1 0 011-1h4a1 1 0 011 1v4" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">សូមស្វាគមន៍! មួយជំហានចុងក្រោយ</h1>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-xl">
        <h2 class="text-lg font-semibold text-white mb-1">តើ Gym របស់អ្នកឈ្មោះអ្វី?</h2>
        <p class="text-sm text-slate-400 mb-6">យើងនឹងប្រើឈ្មោះនេះដើម្បីបង្កើតគេហទំព័រសម្រាប់ Gym របស់អ្នក</p>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm text-slate-300 mb-1.5">ឈ្មោះ Gym</label>
            <div class="relative">
              <svg class="w-4.5 h-4.5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m16 0v-3.5M5 21v-3.5M9 7h1m-1 4h1m4-4h1m-1 4h1M9 21v-4a1 1 0 011-1h4a1 1 0 011 1v4" />
              </svg>
              <input
                v-model="form.gym_name"
                type="text"
                placeholder="PowerGym Phnom Penh"
                autofocus
                class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
            </div>
            <p v-if="form.errors.gym_name" class="text-red-400 text-xs mt-1.5">{{ form.errors.gym_name }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:cursor-not-allowed text-slate-950 font-semibold rounded-lg py-3 transition mt-2"
          >
            {{ form.processing ? 'កំពុងបង្កើត...' : 'បន្ត' }}
            <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>