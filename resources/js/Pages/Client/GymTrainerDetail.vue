<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import ClientLayout from '../../Layouts/ClientLayout.vue'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  tenant: Object,
  settings: Object,
  trainer: Object,
  classes: { type: Array, default: () => [] },
})

const { theme } = useTheme()
const { t } = useLang()

const dayLabels = {
  mon: t.value?.day_mon ?? 'ចន្ទ',
  tue: t.value?.day_tue ?? 'អង្គារ',
  wed: t.value?.day_wed ?? 'ពុធ',
  thu: t.value?.day_thu ?? 'ព្រហស្បតិ៍',
  fri: t.value?.day_fri ?? 'សុក្រ',
  sat: t.value?.day_sat ?? 'សៅរ៍',
  sun: t.value?.day_sun ?? 'អាទិត្យ',
}

function formatTime(time) {
  return time ? time.slice(0, 5) : ''
}

const photoBroken = ref(false)
function onPhotoError() {
  photoBroken.value = true
}

const contactHref = computed(() => `/gym/${props.tenant.slug}/contact`)
</script>

<template>
  <!-- ===== HEADER / BREADCRUMB ===== -->
  <section :class="[theme.bgAlt, theme.border]" class="border-b transition-colors duration-300">
    <div class="max-w-4xl mx-auto px-6 py-6">
      <Link
        :href="`/gym/${tenant.slug}/trainers`"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-400 hover:text-emerald-300 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ t.trainers_back_to_all ?? 'ត្រឡប់ទៅគ្រូបង្វឹកទាំងអស់' }}
      </Link>
    </div>
  </section>

  <!-- ===== TRAINER DETAIL ===== -->
  <section class="max-w-4xl mx-auto px-6 py-12 sm:py-16">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
      <!-- Photo -->
      <div class="lg:col-span-2">
        <div class="relative rounded-2xl overflow-hidden aspect-square shadow-xl lg:sticky lg:top-6">
          <img
            v-if="trainer.photo_url && !photoBroken"
            :src="trainer.photo_url"
            :alt="trainer.name"
            class="w-full h-full object-cover"
            @error="onPhotoError"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-6xl font-bold text-emerald-400 bg-gradient-to-br from-emerald-800/30 to-slate-900">
            {{ trainer.name?.charAt(0) }}
          </div>
        </div>
      </div>

      <!-- Info + bio -->
      <div class="lg:col-span-3">
        <span
          v-if="trainer.specialty"
          class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-emerald-950 bg-emerald-400 px-2.5 py-1 rounded-full mb-4"
        >
          💪 {{ trainer.specialty }}
        </span>

        <h1 class="text-2xl sm:text-3xl font-bold mb-4">{{ trainer.name }}</h1>

        <p v-if="trainer.bio" class="leading-relaxed text-base mb-8" :class="theme.textMuted">
          {{ trainer.bio }}
        </p>
        <p v-else class="text-base italic mb-8" :class="theme.textMuted">
          {{ t.trainers_no_bio ?? 'A dedicated coach ready to help you reach your goals.' }}
        </p>

        <Link
          :href="contactHref"
          class="inline-flex items-center gap-1.5 text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-xl px-6 py-3 transition-colors"
        >
          {{ t.trainers_contact ?? 'Book a session' }}
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </Link>

        <!-- Classes taught by this trainer -->
        <div v-if="classes.length" class="mt-10 pt-8 border-t" :class="theme.border">
          <h2 class="text-lg font-semibold mb-4">{{ t.trainers_classes_title ?? 'ថ្នាក់ដែលបង្រៀន' }}</h2>
          <div class="space-y-3">
            <Link
              v-for="cls in classes"
              :key="cls.id"
              :href="`/gym/${tenant.slug}/classes/${cls.id}`"
              :class="[theme.card, theme.border]"
              class="group flex items-center justify-between gap-4 rounded-xl border p-4 transition-colors hover:border-emerald-500/50"
            >
              <div class="min-w-0">
                <p class="font-medium text-sm truncate group-hover:text-emerald-400 transition-colors">{{ cls.name }}</p>
                <p class="text-xs mt-0.5" :class="theme.textMuted">
                  {{ dayLabels[cls.schedule_day] }} · {{ formatTime(cls.start_time) }} – {{ formatTime(cls.end_time) }}
                </p>
              </div>
              <svg class="w-4 h-4 shrink-0 opacity-40 group-hover:opacity-100 group-hover:text-emerald-400 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>