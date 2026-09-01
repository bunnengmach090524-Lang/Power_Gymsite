<script setup>
import { computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

defineProps({
  classes: Array,
})

const { t } = useLang()
const page = usePage()

const canDelete = computed(() => page.props.auth?.user?.role === 'gym_admin')

function dayLabel(day) {
  return t.value[`day_${day}`] ?? day
}

// Distinct color per weekday so the eye can scan the schedule quickly
// without reading every label.
const dayColors = {
  mon: 'bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400',
  tue: 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400',
  wed: 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400',
  thu: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
  fri: 'bg-pink-50 dark:bg-pink-500/10 text-pink-600 dark:text-pink-400',
  sat: 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400',
  sun: 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400',
}

function bookedCount(gymClass) {
  return gymClass.bookings?.length ?? 0
}

function spotsLeft(gymClass) {
  return Math.max(0, gymClass.capacity - bookedCount(gymClass))
}

function fillRatio(gymClass) {
  if (!gymClass.capacity) return 0
  return Math.min(100, Math.round((bookedCount(gymClass) / gymClass.capacity) * 100))
}

function fillBarColor(gymClass) {
  const ratio = fillRatio(gymClass)
  if (ratio >= 100) return 'bg-red-500'
  if (ratio >= 75) return 'bg-amber-500'
  return 'bg-emerald-500'
}

function formatTime(timeStr) {
  if (!timeStr) return '—'
  return timeStr.slice(0, 5)
}

function destroy(gymClass) {
  if (confirm(`${t.value.confirm_delete_prefix} ${gymClass.name}?`)) {
    router.delete(`/dashboard/classes/${gymClass.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <div class="w-full animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white">{{ t.classes_title }}</h1>
      <Link
        href="/dashboard/classes/create"
        class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-medium rounded-lg px-5 py-2.5 text-sm sm:text-base transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
      >
        {{ t.classes_add_new }}
      </Link>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-x-auto transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700">
      <table class="w-full text-sm sm:text-base min-w-[920px]">
        <thead>
          <tr class="text-left text-slate-500 dark:text-slate-500 border-b border-slate-200 dark:border-slate-800">
            <th class="px-5 py-3 font-normal">{{ t.class_table_name }}</th>
            <th class="px-5 py-3 font-normal">{{ t.class_table_trainer }}</th>
            <th class="px-5 py-3 font-normal">{{ t.class_table_day }}</th>
            <th class="px-5 py-3 font-normal">{{ t.class_table_time }}</th>
            <th class="px-5 py-3 font-normal">{{ t.class_table_spots }}</th>
            <th class="px-5 py-3 font-normal">{{ t.class_table_price ?? 'តម្លៃ' }}</th>
            <th class="px-5 py-3 font-normal"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="gymClass in classes"
            :key="gymClass.id"
            class="border-b border-slate-100 dark:border-slate-800/50 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-800/40"
          >
            <td class="px-5 py-3.5">
              <p class="text-slate-900 dark:text-white font-medium">{{ gymClass.name }}</p>
            </td>

            <td class="px-5 py-3.5">
              <Link
                v-if="gymClass.trainer"
                :href="`/dashboard/trainers/${gymClass.trainer.id}/edit`"
                class="flex items-center gap-2 group w-fit"
              >
                <img
                  v-if="gymClass.trainer.photo_url"
                  :src="gymClass.trainer.photo_url"
                  class="w-7 h-7 rounded-full object-cover shrink-0"
                />
                <div v-else class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white text-xs font-semibold shrink-0">
                  {{ gymClass.trainer.name?.[0]?.toUpperCase() }}
                </div>
                <span class="text-slate-600 dark:text-slate-400 group-hover:text-emerald-500 transition-colors duration-150 truncate">
                  {{ gymClass.trainer.name }}
                </span>
              </Link>
              <span v-else class="text-slate-400">—</span>
            </td>

            <td class="px-5 py-3.5">
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap" :class="dayColors[gymClass.schedule_day] ?? 'bg-slate-100 text-slate-500'">
                {{ dayLabel(gymClass.schedule_day) }}
              </span>
            </td>

            <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400 whitespace-nowrap">
              {{ formatTime(gymClass.start_time) }} – {{ formatTime(gymClass.end_time) }}
            </td>

            <td class="px-5 py-3.5 min-w-[140px]">
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-medium" :class="spotsLeft(gymClass) === 0 ? 'text-red-500' : 'text-slate-600 dark:text-slate-300'">
                  {{ spotsLeft(gymClass) === 0 ? t.class_full : `${bookedCount(gymClass)} / ${gymClass.capacity}` }}
                </span>
              </div>
              <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-300"
                  :class="fillBarColor(gymClass)"
                  :style="{ width: `${fillRatio(gymClass)}%` }"
                ></div>
              </div>
            </td>

            <td class="px-5 py-3.5">
              <span v-if="gymClass.price > 0" class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                ${{ Number(gymClass.price).toFixed(2) }}
              </span>
              <span v-else class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                {{ t.class_free ?? 'ឥតគិតថ្លៃ' }}
              </span>
            </td>

            <td class="px-5 py-3.5 text-right whitespace-nowrap">
              <Link :href="`/dashboard/classes/${gymClass.id}/roster`" class="text-sky-600 dark:text-sky-400 hover:text-sky-500 text-sm mr-3 transition-colors duration-150">{{ t.class_roster ?? 'Roster' }}</Link>
              <Link :href="`/dashboard/classes/${gymClass.id}/edit`" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-sm mr-3 transition-colors duration-150">{{ t.action_edit }}</Link>
              <button
                v-if="canDelete"
                @click="destroy(gymClass)"
                class="text-red-500 dark:text-red-400 hover:text-red-400 text-sm transition-colors duration-150"
              >{{ t.action_delete }}</button>
            </td>
          </tr>
          <tr v-if="!classes.length">
            <td colspan="7" class="px-5 py-10 text-center text-slate-500 dark:text-slate-500">{{ t.classes_empty }}</td>
          </tr>
        </tbody>
      </table>
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
</style>