<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  userEmail: String,
  userId: [String, Number],
  signedUrl: String,
})

const form = useForm({
  name: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  // signedUrl already contains valid signature + expiry query params
  form.post(props.signedUrl)
}

const showPassword = ref(false)
const showConfirm = ref(false)
const passwordLongEnough = computed(() => form.password.length >= 8)
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-emerald-500 flex items-center justify-center text-slate-950 font-bold text-xl">
          G
        </div>
        <h1 class="text-xl font-semibold text-white mb-1">សូមស្វាគមន៍មកកាន់ GymSite</h1>
        <p class="text-sm text-slate-400">{{ userEmail }}</p>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-xl">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm text-slate-300 mb-1.5">ឈ្មោះ</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
            />
            <p v-if="form.errors.name" class="text-red-400 text-xs mt-1.5">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-1.5">Password</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 pr-10 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                <svg v-if="showPassword" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" /></svg>
                <svg v-else class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </button>
            </div>

            <!-- Live requirement checklist — mirrors AppServiceProvider's Password::min(8) rule -->
            <p v-if="form.password" class="text-xs flex items-center gap-1.5 mt-1.5" :class="passwordLongEnough ? 'text-emerald-400' : 'text-slate-500'">
              <svg v-if="passwordLongEnough" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" />
              </svg>
              At least 8 characters
            </p>
            <p v-else class="text-xs text-slate-500 mt-1.5">At least 8 characters</p>

            <p v-if="form.errors.password" class="text-red-400 text-xs mt-1.5">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-1.5">បញ្ជាក់ Password</label>
            <div class="relative">
              <input
                v-model="form.password_confirmation"
                :type="showConfirm ? 'text' : 'password'"
                required
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3.5 pr-10 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
              <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                <svg v-if="showConfirm" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" /></svg>
                <svg v-else class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </button>
            </div>
            <p v-if="form.errors.password_confirmation" class="text-red-400 text-xs mt-1.5">{{ form.errors.password_confirmation }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold rounded-lg py-3 transition"
          >
            {{ form.processing ? 'កំពុងបង្កើត...' : 'បង្កើត Account' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>