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
  trainers: Array,
})

const { theme } = useTheme()
const { t } = useLang()

const brokenTrainerPhotos = ref(new Set())
function onTrainerPhotoError(id) {
  brokenTrainerPhotos.value = new Set(brokenTrainerPhotos.value).add(id)
}

const contactHref = computed(() => `/gym/${props.tenant.slug}/contact`)
</script>

<template>
  <!-- ===== PAGE HEADER ===== -->
  <section :class="[theme.bgAlt, theme.border]" class="border-b transition-colors duration-300">
    <div class="max-w-5xl mx-auto px-6 py-16 sm:py-20 text-center" v-reveal>
      <h1 class="text-3xl sm:text-4xl font-extrabold mb-3">{{ t.trainers_title ?? 'Our Trainers' }}</h1>
      <p class="max-w-lg mx-auto" :class="theme.textMuted">{{ t.trainers_subtitle ?? 'The coaches behind every session.' }}</p>
    </div>
  </section>

  <!-- ===== TRAINERS GRID ===== -->
  <section class="max-w-6xl mx-auto px-6 py-16 sm:py-20">
    <div v-if="trainers?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="(trainer, i) in trainers"
        :key="trainer.id"
        v-reveal="{ delay: i * 80 }"
        :class="[theme.card, theme.border]"
        class="group rounded-2xl border overflow-hidden transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-emerald-500/10 hover:border-emerald-500/50 flex flex-col"
      >
        <Link :href="`/gym/${tenant.slug}/trainers/${trainer.id}`" class="relative aspect-[4/3] overflow-hidden block">
          <img
            v-if="trainer.photo_url && !brokenTrainerPhotos.has(trainer.id)"
            :src="trainer.photo_url"
            :alt="trainer.name"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
            @error="onTrainerPhotoError(trainer.id)"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-5xl font-bold text-emerald-400 bg-gradient-to-br from-emerald-800/30 to-slate-900">
            {{ trainer.name?.charAt(0) }}
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>

          <span
            v-if="trainer.specialty"
            class="absolute bottom-3 left-3 inline-flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-950 bg-emerald-400 px-2.5 py-1 rounded-full shadow"
          >
            💪 {{ trainer.specialty }}
          </span>
        </Link>

        <div class="p-5 flex flex-col flex-1">
          <Link :href="`/gym/${tenant.slug}/trainers/${trainer.id}`" class="hover:text-emerald-400 transition-colors">
            <h3 class="font-semibold text-lg mb-1.5">{{ trainer.name }}</h3>
          </Link>

          <p v-if="trainer.bio" class="text-sm leading-relaxed line-clamp-3 mb-4 flex-1" :class="theme.textMuted">
            {{ trainer.bio }}
          </p>
          <p v-else class="text-sm mb-4 flex-1" :class="theme.textMuted">
            {{ t.trainers_no_bio ?? 'A dedicated coach ready to help you reach your goals.' }}
          </p>

          <Link
            :href="`/gym/${tenant.slug}/trainers/${trainer.id}`"
            class="mt-auto flex items-center justify-center gap-1.5 text-sm font-medium border border-emerald-500/40 text-emerald-400 hover:bg-emerald-500 hover:text-slate-950 rounded-lg py-2.5 transition-all duration-200"
          >
            {{ t.trainers_view_profile ?? 'View trainer' }}
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </Link>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-20">
      <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center" :class="theme.bgAlt">
        <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>
      <p :class="theme.textMuted">{{ t.trainers_empty ?? 'មិនទាន់មានគ្រូបង្វឹកទេ' }}</p>
    </div>
  </section>
</template>