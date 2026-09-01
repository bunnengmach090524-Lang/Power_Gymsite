<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({
  gym_name: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post('/register')
}

const showPassword = ref(false)
const showConfirm = ref(false)

const passwordLongEnough = computed(() => form.password.length >= 8)
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex">
    <!-- ===== LEFT VISUAL PANEL (hidden on mobile) ===== -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
      <!-- swap for a real gym action photo -->
      <div class="absolute inset-0 bg-gradient-to-br from-emerald-900 via-slate-900 to-slate-950"></div>
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_30%,rgba(16,185,129,0.15),transparent_50%)]"></div>

      <div class="relative z-10 flex flex-col justify-between p-12 w-full">
        <div class="flex items-center gap-2.5">
          <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center">
            <svg class="w-5 h-5 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 12a2 2 0 100 4h1a2 2 0 100-4m-1 0a2 2 0 110-4h1a2 2 0 110 4m14 0a2 2 0 100 4h-1a2 2 0 100-4m1 0a2 2 0 110-4h-1a2 2 0 110 4" />
            </svg>
          </div>
          <span class="text-white font-bold text-lg">GymSite</span>
        </div>

        <div>
          <h2 class="text-4xl font-extrabold text-white leading-tight mb-4">
            បង្កើតគេហទំព័រ Gym<br />ក្នុងប៉ុន្មាននាទី
          </h2>
          <p class="text-slate-300 text-base max-w-sm mb-8">
            គ្មាន code, គ្មាន developer — ចាប់ផ្តើមទទួល member online ថ្ងៃនេះ
          </p>
          <div class="space-y-3">
            <div class="flex items-center gap-3 text-slate-200 text-sm">
              <span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
              </span>
              Trial ឥតគិតថ្លៃ 14 ថ្ងៃ
            </div>
            <div class="flex items-center gap-3 text-slate-200 text-sm">
              <span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
              </span>
              បោះបង់ពេលណាក៏បាន
            </div>
            <div class="flex items-center gap-3 text-slate-200 text-sm">
              <span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
              </span>
              គាំទ្រ 24/7
            </div>
          </div>
        </div>

        <p class="text-slate-500 text-xs">&copy; {{ new Date().getFullYear() }} GymSite. All rights reserved.</p>
      </div>
    </div>

    <!-- ===== RIGHT FORM PANEL ===== -->
    <div class="flex-1 flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md">
        <div class="text-center mb-8 lg:hidden">
          <h1 class="text-2xl font-bold text-white">GymSite</h1>
        </div>

        <div class="mb-8">
          <h2 class="text-2xl font-bold text-white mb-1.5">បង្កើតគណនី Gym ✨</h2>
          <p class="text-sm text-slate-400">ចាប់ផ្តើមដោយឥតគិតថ្លៃ — trial 14 ថ្ងៃ</p>
        </div>

        <a
          href="/auth/google/redirect?intent=register"
          class="w-full flex items-center justify-center gap-2.5 bg-white hover:bg-slate-100 text-slate-800 font-medium rounded-lg py-2.5 transition mb-5"
        >
          <svg class="w-4.5 h-4.5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          បង្កើតគណនីជាមួយ Google
        </a>

        <div class="flex items-center gap-3 mb-5">
          <div class="flex-1 h-px bg-slate-800"></div>
          <span class="text-xs text-slate-500">ឬ</span>
          <div class="flex-1 h-px bg-slate-800"></div>
        </div>

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
                class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
            </div>
            <p v-if="form.errors.gym_name" class="text-red-400 text-xs mt-1.5">{{ form.errors.gym_name }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-1.5">ឈ្មោះអ្នក</label>
            <div class="relative">
              <svg class="w-4.5 h-4.5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <input
                v-model="form.name"
                type="text"
                placeholder="Sok Dara"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
            </div>
            <p v-if="form.errors.name" class="text-red-400 text-xs mt-1.5">{{ form.errors.name }}</p>
          </div>

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
                class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-3.5 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
            </div>
            <p v-if="form.errors.email" class="text-red-400 text-xs mt-1.5">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-1.5">ពាក្យសម្ងាត់</label>
            <div class="relative">
              <svg class="w-4.5 h-4.5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-10 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
              <button type="button" @click="showPassword = !showPassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                <svg v-if="showPassword" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" /></svg>
                <svg v-else class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </button>
            </div>

            <!-- Live requirement checklist — turns emerald once satisfied -->
            <p v-if="form.password" class="text-xs flex items-center gap-1.5 mt-1.5" :class="passwordLongEnough ? 'text-emerald-400' : 'text-slate-500'">
              <svg v-if="passwordLongEnough" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" />
              </svg>
              យ៉ាងតិច 8 តួអក្សរ
            </p>
            <p v-else class="text-xs text-slate-500 mt-1.5">យ៉ាងតិច 8 តួអក្សរ</p> 
            <p v-if="form.errors.password" class="text-red-400 text-xs mt-1.5">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-1.5">បញ្ជាក់ពាក្យសម្ងាត់</label>
            <div class="relative">
              <svg class="w-4.5 h-4.5 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <input
                v-model="form.password_confirmation"
                :type="showConfirm ? 'text' : 'password'"
                placeholder="••••••••"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-10 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
              />
              <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors">
                <svg v-if="showConfirm" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" /></svg>
                <svg v-else class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:cursor-not-allowed text-slate-950 font-semibold rounded-lg py-3 transition mt-2"
          >
            {{ form.processing ? 'កំពុងបង្កើត...' : 'បង្កើតគណនី' }}
            <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
          </button>
        </form>

        <p class="text-center text-sm text-slate-400 mt-6">
          មានគណនីរួចហើយ?
          <Link href="/login" class="text-emerald-400 font-medium hover:text-emerald-300">ចូលគណនី</Link>
        </p>
      </div>
    </div>
  </div>
</template>