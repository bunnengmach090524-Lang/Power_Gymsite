<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

defineProps({ trainers: Array })
const { t, lang } = useLang()
const page = usePage()

function deleteTrainer(trainer) {
  if (confirm(t.value.trainer_confirm_delete)) {
    router.delete(`/dashboard/trainers/${trainer.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <div class="p-6 sm:p-8 animate-fade-in-up">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white tracking-tight">
        🏋️ {{ t.trainer_title }}
      </h1>
      <Link
        href="/dashboard/trainers/create"
        class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-5 py-2.5 text-sm transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        + {{ t.trainer_add_new }}
      </Link>
    </div>

    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="page.props.flash?.success"
        class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-4"
      >
        {{ page.props.flash.success }}
      </div>
    </Transition>
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-150" leave-to-class="opacity-0">
      <div
        v-if="page.props.errors && Object.keys(page.props.errors).length"
        class="bg-red-50 dark:bg-red-500/10 border border-red-300 dark:border-red-500/30 text-red-600 dark:text-red-400 text-sm rounded-lg px-4 py-3 mb-4"
      >
        {{ Object.values(page.props.errors)[0] }}
      </div>
    </Transition>

    <div v-if="trainers.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="(trainer, i) in trainers"
        :key="trainer.id"
        class="trainer-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-black/30 hover:border-emerald-300 dark:hover:border-emerald-700/50"
        :style="{ animationDelay: `${i * 60}ms` }"
      >
        <Link :href="`/dashboard/trainers/${trainer.id}`" class="flex items-center gap-4 mb-3 group">
          <img
            v-if="trainer.photo_url"
            :src="`${trainer.photo_url}`"
            class="w-16 h-16 rounded-full object-cover ring-2 ring-transparent group-hover:ring-emerald-400/40 transition-all duration-300"
          />
          <div
            v-else
            class="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-xl font-semibold ring-2 ring-transparent group-hover:ring-emerald-400/40 transition-all duration-300"
          >
            {{ trainer.name?.[0]?.toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-slate-900 dark:text-white truncate group-hover:text-emerald-500 transition-colors duration-200">
              {{ trainer.name }}
            </p>
            <p v-if="trainer.specialty" class="text-xs text-emerald-600 dark:text-emerald-400 truncate">
              {{ trainer.specialty }}
            </p>
          </div>
        </Link>

        <p v-if="trainer.email" class="flex items-center gap-1.5 text-xs text-slate-400 mb-2 truncate">
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span class="truncate">{{ trainer.email }}</span>
        </p>

        <p v-if="trainer.bio" class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 mb-3">
          {{ trainer.bio }}
        </p>

        <p class="inline-flex items-center gap-1.5 text-xs text-slate-400 mb-4 bg-slate-50 dark:bg-slate-800/60 px-2.5 py-1 rounded-full">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          {{ trainer.classes_count }} {{ t.trainer_classes_count }}
        </p>

        <div class="flex items-center gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
          <Link :href="`/dashboard/trainers/${trainer.id}`" class="text-sm text-slate-500 dark:text-slate-400 font-medium hover:text-emerald-500 transition-colors duration-200">
            {{ lang === 'km' ? 'មើល' : 'View' }}
          </Link>
          <Link :href="`/dashboard/trainers/${trainer.id}/edit`" class="text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline transition-colors duration-200">
            {{ t.trainer_edit }}
          </Link>
          <button @click="deleteTrainer(trainer)" class="text-sm text-red-500 dark:text-red-400 font-medium hover:underline transition-colors duration-200 ml-auto">
            {{ t.trainer_delete }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl py-16 flex flex-col items-center text-center">
      <div class="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>
      <p class="text-slate-400">{{ t.trainer_empty }}</p>
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
.trainer-card {
  animation: fade-in-up 0.4s ease-out backwards;
}
</style>