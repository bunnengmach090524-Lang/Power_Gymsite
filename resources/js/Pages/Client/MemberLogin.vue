<script setup>
import { ref } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'

const props = defineProps({
  tenant: { type: Object, required: true },
  settings: Object,
})

const page = usePage()

// Posts to the GLOBAL /login route — AuthenticatedSessionController
// resolves the member's tenant automatically after auth and redirects
// to /gym/{slug}/account. We don't post to a tenant-scoped login route
// because none exists (and doesn't need to — one users table, one
// login endpoint for member/staff/admin alike).
const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post('/login')
}

const showPassword = ref(false)
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex">
    <!-- ===== LEFT VISUAL PANEL ===== -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
      <img
        v-if="settings?.heroBannerImage?.url"
        :src="settings.heroBannerImage.url"
        :alt="tenant.name"
        class="absolute inset-0 w-full h-full object-cover"
      />
      <div v-else class="absolute inset-0 bg-gradient-to-br from-emerald-900 via-slate-900 to-slate-950"></div>
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/30"></div>

      <div class="relative z-10 flex flex-col justify-between p-12 w-full">
        <div class="flex items-center gap-2.5">
          <div v-if="settings?.logoImage?.url" class="w-10 h-10 rounded-xl overflow-hidden">
            <img :src="settings.logoImage.url" :alt="tenant.name" class="w-full h-full object-cover" />
          </div>
          <div v-else class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center">
            <svg class="w-5 h-5 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 12a2 2 0 100 4h1a2 2 0 100-4m-1 0a2 2 0 110-4h1a2 2 0 110 4m14 0a2 2 0 100 4h-1a2 2 0 100-4m1 0a2 2 0 110-4h-1a2 2 0 110 4" />
            </svg>
          </div>
          <span class="text-white font-bold text-lg">{{ tenant.name }}</span>
        </div>

        <div>
          <h2 class="text-4xl font-extrabold text-white leading-tight mb-4">
            សូមស្វាគមន៍មកវិញ<br />ទៅកាន់ {{ tenant.name }}
          </h2>
          <p class="text-slate-300 text-base max-w-sm mb-8">
            ចូលគណនីដើម្បីកក់ class, តាមដាន membership និងព័ត៌មានផ្សេងទៀត
          </p>
          <div class="space-y-3">
            <div class="flex items-center gap-3 text-slate-200 text-sm">
              <span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
              </span>
              កក់ Class online គ្រប់ពេល
            </div>
            <div class="flex items-center gap-3 text-slate-200 text-sm">
              <span class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
              </span>
              តាមដាន membership របស់អ្នក
            </div>
          </div>
        </div>

        <p class="text-slate-400 text-xs">&copy; {{ new Date().getFullYear() }} {{ tenant.name }}</p>
      </div>
    </div>

    <!-- ===== RIGHT FORM PANEL ===== -->
    <div class="flex-1 flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md">
        <div class="text-center mb-8 lg:hidden">
          <h1 class="text-2xl font-bold text-white">{{ tenant.name }}</h1>
        </div>

        <div class="mb-8">
          <h2 class="text-2xl font-bold text-white mb-1.5">ចូលគណនីសមាជិក 👋</h2>
          <p class="text-sm text-slate-400">ចូលដើម្បីបន្តគ្រប់គ្រងគណនីរបស់អ្នក</p>
        </div>

        <!-- Google OAuth failures land in page.props.errors, not form.errors -->
        <p
          v-if="page.props.errors?.email && !form.errors.email"
          class="text-red-400 text-sm text-center bg-red-500/10 border border-red-500/30 rounded-lg py-2.5 px-3 mb-4"
        >
          {{ page.props.errors.email }}
        </p>

        <a
          :href="`/auth/google/redirect?tenant=${tenant.slug}`"
          class="w-full flex items-center justify-center gap-2.5 bg-white hover:bg-slate-100 text-slate-800 font-medium rounded-lg py-2.5 transition mb-5"
        >
          <svg class="w-4.5 h-4.5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
          </svg>
          ចូលគណនីជាមួយ Google
        </a>

        <div class="flex items-center gap-3 mb-5">
          <div class="flex-1 h-px bg-slate-800"></div>
          <span class="text-xs text-slate-500">ឬចូលដោយ email</span>
          <div class="flex-1 h-px bg-slate-800"></div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
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
            <p v-if="form.errors.email" class="text-red-400 text-xs mt-1.5">{{ form.errors.email }}</p>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="rounded border-slate-700 bg-slate-800 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-950" />
              ចងចាំខ្ញុំ
            </label>
            <Link href="/forgot-password" class="text-sm text-emerald-400 hover:text-emerald-300">ភ្លេចពាក្យសម្ងាត់?</Link>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 font-semibold rounded-lg py-3 transition"
          >
            {{ form.processing ? 'កំពុងចូល...' : 'ចូលគណនី' }}
            <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
          </button>
        </form>

        <p class="text-center text-sm text-slate-400 mt-6">
          មិនទាន់មានគណនី?
          <Link :href="`/gym/${tenant.slug}/register`" class="text-emerald-400 font-medium hover:text-emerald-300">ចុះឈ្មោះជាសមាជិក</Link>
        </p>
      </div>
    </div>
  </div>
</template>