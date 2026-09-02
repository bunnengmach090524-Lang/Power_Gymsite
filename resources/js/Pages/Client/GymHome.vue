<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import ClientLayout from '../../Layouts/ClientLayout.vue'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'
import { useCountUp } from '../../composables/useCountUp'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  tenant: Object,
  settings: Object,
  heroImages: Array,
  gallery: Array,
  plans: Array,
  activePromotions: Array,
  trainers: Array,
  classes: Array,
})

const { theme } = useTheme()
const { t } = useLang()

// ===== Hero slideshow =====
// Cycles through every hero_banner image automatically, crossfading every
// 6 seconds, when random_hero_banner is on and there's more than 1 image.
// Falls back to a single static image otherwise. Dots let the visitor jump
// to a specific slide manually (and pause the auto-advance briefly).
const heroIndex = ref(0)
let heroSlideTimer = null

const heroSlideCount = computed(() =>
  props.settings?.random_hero_banner ? (props.heroImages?.length ?? 0) : 0
)

const heroImage = computed(() => {
  if (props.settings?.random_hero_banner && props.heroImages?.length) {
    const idx = heroIndex.value % props.heroImages.length
    return props.heroImages[idx]?.image_url ?? null
  }
  return props.settings?.hero_banner_image?.image_url ?? props.heroImages?.[0]?.image_url ?? null
})

function startHeroSlideshow() {
  if (heroSlideTimer) clearInterval(heroSlideTimer)
  if (props.settings?.random_hero_banner && props.heroImages?.length > 1) {
    // Start on a random slide so different visitors don't all see the
    // same image first, then advance sequentially every 6s.
    heroIndex.value = Math.floor(Math.random() * props.heroImages.length)
    heroSlideTimer = setInterval(() => {
      heroIndex.value = (heroIndex.value + 1) % props.heroImages.length
    }, 6000)
  }
}

function goToSlide(i) {
  heroIndex.value = i
  startHeroSlideshow() // reset the timer so it doesn't jump right after a manual click
}

const plansCount = useCountUp(props.plans?.length ?? 0)
const photosCount = useCountUp(props.gallery?.length ?? 0)
const promoCount = useCountUp(props.activePromotions?.length ?? 0)
const trainersCount = useCountUp(props.trainers?.length ?? 0)

function promotionFor(planId) {
  return props.activePromotions?.find(p => !p.applicable_plan_id || p.applicable_plan_id === planId)
}

function isFeatured(i) {
  return (props.plans?.length ?? 0) === 3 && i === 1
}

const plansPreview = computed(() => (props.plans ?? []).slice(0, 3))

// Same tier icon + feature-parsing logic as GymPricing.vue, so the Home
// teaser matches the full Pricing page look.
const tierIcons = [
  'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M12 6a9 9 0 100 18 9 9 0 000-18z',
  'M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7-6.2-3.9-6.2 3.9 1.6-7L2 9.2l7.1-.6L12 2z',
  'M5 16l-2-9 6 3 3-6 3 6 6-3-2 9H5zm0 0v2a1 1 0 001 1h12a1 1 0 001-1v-2',
]

function featuresFor(plan) {
  const text = plan.description?.trim()
  if (!text) return []
  return text.split('+').map(f => f.trim()).filter(Boolean)
}

const brokenTrainerPhotos = ref(new Set())
function onTrainerPhotoError(id) {
  brokenTrainerPhotos.value = new Set(brokenTrainerPhotos.value).add(id)
}
const trainersPreview = computed(() => (props.trainers ?? []).slice(0, 4))

// ===== Video support in gallery preview/lightbox (mirrors GymGallery.vue).
//       An item is a video if it has a `video_url`, or `media_kind === 'video'`.
//       `type` is reserved for hero_banner/gallery/trainer_photo/logo — never overloaded here. =====
function isVideo(img) {
  return img?.media_kind === 'video' || !!img?.video_url
}
function mediaSrc(img) {
  return isVideo(img) ? (img.video_url ?? img.image_url) : img.image_url
}

const galleryPreview = computed(() => (props.gallery ?? []).slice(0, 8))

// The About section pairs its text with a photo — prefer a gallery shot,
// falling back to a hero image, so it never renders empty.
const aboutImage = computed(() =>
  props.gallery?.[0]?.image_url ?? props.heroImages?.[0]?.image_url ?? null
)

// ===== "Why Choose Us" — a lightweight, always-available features section.
//       Uses fixed, translatable copy rather than admin-entered data, so it
//       looks complete on day one without extra setup. =====
const whyChooseUs = computed(() => [
  {
    icon: 'M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z',
    title: t.value.why_equipment_title ?? 'ឧបករណ៍ទំនើប',
    desc: t.value.why_equipment_desc ?? 'ម៉ាស៊ីនហាត់ប្រាណគុណភាពខ្ពស់ ថែទាំជាប្រចាំ សម្រាប់ការហាត់ប្រកបដោយសុវត្ថិភាព',
  },
  {
    icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 10-8 0 4 4 0 008 0zm8 0a4 4 0 10-8 0 4 4 0 008 0z',
    title: t.value.why_trainers_title ?? 'គ្រូបង្វឹកជំនាញ',
    desc: t.value.why_trainers_desc ?? 'ក្រុមគ្រូបង្វឹកមានបទពិសោធន៍ ណែនាំកម្មវិធីហាត់ប្រាណសមស្របតាមគោលដៅរបស់អ្នក',
  },
  {
    icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    title: t.value.why_hours_title ?? 'ម៉ោងបើកទូលាយ',
    desc: t.value.why_hours_desc ?? 'បើកប្រចាំថ្ងៃ ងាយស្រួលចូលហាត់ តាមម៉ោងទំនេររបស់អ្នក',
  },
  {
    icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    title: t.value.why_community_title ?? 'សហគមន៍គាំទ្រ',
    desc: t.value.why_community_desc ?? 'ថ្នាក់ក្រុម ព្រឹត្តិការណ៍ និងបរិយាកាសលើកទឹកចិត្តគ្នាទៅវិញទៅមក',
  },
])

const lightboxIndex = ref(null)
const lightboxOpen = computed(() => lightboxIndex.value !== null)
const activeItem = computed(() => lightboxIndex.value !== null ? props.gallery[lightboxIndex.value] : null)
function openLightbox(i) { lightboxIndex.value = i }
function closeLightbox() { lightboxIndex.value = null }
function nextImage(e) {
  e?.stopPropagation()
  if (lightboxIndex.value === null) return
  lightboxIndex.value = (lightboxIndex.value + 1) % props.gallery.length
}
function prevImage(e) {
  e?.stopPropagation()
  if (lightboxIndex.value === null) return
  lightboxIndex.value = (lightboxIndex.value - 1 + props.gallery.length) % props.gallery.length
}
function onKeydown(e) {
  if (!lightboxOpen.value) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowRight') nextImage()
  if (e.key === 'ArrowLeft') prevImage()
}

// ===== Services showcase — uses each class's own photo now that classes
//       support image_url; falls back to a cycled gallery photo, then a
//       plain gradient, so the section still looks intentional either way. =====
const servicesPreview = computed(() => (props.classes ?? []).slice(0, 6))
function serviceBgImage(cls, i) {
  if (cls.image_url) return cls.image_url
  if (!props.gallery?.length) return null
  return props.gallery[i % props.gallery.length]?.image_url ?? null
}

function todayCode() {
  return ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][new Date().getDay()]
}
function formatTime(time) {
  return time ? time.slice(0, 5) : ''
}
function isLiveNow(cls) {
  if (cls.schedule_day !== todayCode()) return false
  const now = new Date()
  const nowMin = now.getHours() * 60 + now.getMinutes()
  const [sh, sm] = cls.start_time.split(':').map(Number)
  const [eh, em] = cls.end_time.split(':').map(Number)
  return nowMin >= sh * 60 + sm && nowMin < eh * 60 + em
}

// ===== Today's Schedule widget (Hero sidebar, "The Place"-style) =====
const todaySchedule = computed(() =>
  (props.classes ?? [])
    .filter(c => c.schedule_day === todayCode())
    .sort((a, b) => a.start_time.localeCompare(b.start_time))
    .slice(0, 5)
)
const todayDateLabel = computed(() =>
  new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' })
)

onMounted(async () => {
  startHeroSlideshow()

  if (!window.location.hash) return
  await nextTick()
  requestAnimationFrame(() => {
    const el = document.querySelector(window.location.hash)
    el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
})

onUnmounted(() => {
  if (heroSlideTimer) clearInterval(heroSlideTimer)
})
</script>

<template>
  <!-- ===== HERO with Ken Burns background + floating blobs ===== -->
  <section class="relative overflow-hidden">
    <div class="absolute -top-20 -left-20 w-72 h-72 bg-emerald-500/20 rounded-full blur-3xl animate-[float1_9s_ease-in-out_infinite]"></div>
    <div class="absolute top-40 -right-10 w-64 h-64 bg-teal-500/20 rounded-full blur-3xl animate-[float2_11s_ease-in-out_infinite]"></div>

    <div class="relative h-[520px] sm:h-[600px] flex items-center justify-center text-center px-6 overflow-hidden">
      <Transition name="hero-fade">
        <div
          v-if="heroImage"
          :key="heroImage"
          class="absolute inset-0 bg-cover bg-center animate-[kenburns_18s_ease-in-out_infinite_alternate]"
          :style="`background-image: url(${heroImage})`"
        ></div>
      </Transition>
      <div v-if="!heroImage" class="absolute inset-0 bg-gradient-to-br from-emerald-900 via-slate-950 to-teal-900 animate-[gradientShift_10s_ease_infinite] bg-[length:200%_200%]"></div>
      <div class="absolute inset-0 bg-slate-950/60"></div>

      <div class="relative animate-[fadeUp_0.9s_ease-out] max-w-2xl">
        <div v-if="settings?.logo_image?.image_url" class="mb-5 flex justify-center">
          <img :src="settings.logo_image.image_url" :alt="tenant.name" class="w-16 h-16 rounded-2xl object-cover shadow-xl ring-2 ring-white/20" />
        </div>
        <h1 class="text-4xl sm:text-6xl font-extrabold mb-4 text-white drop-shadow-lg tracking-tight">{{ tenant.name }}</h1>
        <p class="text-slate-200 max-w-lg mx-auto text-base sm:text-lg">{{ settings?.tagline ?? t.hero_default_tag }}</p>
        <Link :href="`/gym/${tenant.slug}/contact`" class="inline-block mt-8 bg-emerald-500 hover:bg-emerald-400 hover:scale-105 text-slate-950 font-semibold rounded-full px-8 py-3.5 transition-all duration-200 shadow-xl shadow-emerald-500/25">
          {{ t.hero_cta }}
        </Link>
      </div>

      <!-- Slideshow dot indicators — only shown when random_hero_banner has 2+ images -->
      <div
        v-if="heroSlideCount > 1"
        class="absolute bottom-14 left-1/2 -translate-x-1/2 flex items-center gap-2 z-10"
      >
        <button
          v-for="(img, i) in heroImages"
          :key="img.id"
          type="button"
          @click="goToSlide(i)"
          :aria-label="`Slide ${i + 1}`"
          class="h-1.5 rounded-full transition-all duration-300"
          :class="i === (heroIndex % heroImages.length) ? 'w-6 bg-emerald-400' : 'w-1.5 bg-white/40 hover:bg-white/70'"
        ></button>
      </div>

      <a href="#about" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/70 hover:text-white transition-colors animate-[bounceY_2s_ease-in-out_infinite]" aria-label="Scroll down">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
      </a>

      <!-- Today's Schedule widget -->
      <div
        v-if="todaySchedule.length"
        class="hidden lg:block absolute left-6 xl:left-12 bottom-10 w-72 bg-slate-950/70 backdrop-blur-md border border-white/10 rounded-2xl p-5 shadow-2xl animate-[fadeUp_1s_ease-out]"
      >
        <p class="text-white font-semibold text-sm mb-0.5">{{ t.hero_schedule_title ?? "Today's Schedule" }}</p>
        <p class="text-emerald-400 text-xs mb-3">{{ todayDateLabel }}</p>
        <ul class="space-y-2.5">
          <li v-for="cls in todaySchedule" :key="cls.id" class="flex items-center justify-between gap-3 text-sm border-b border-white/5 pb-2 last:border-0 last:pb-0">
            <span class="text-white/90 truncate">{{ cls.name }}</span>
            <span class="text-emerald-400 text-xs shrink-0 font-medium">{{ formatTime(cls.start_time) }}</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- ===== STATS (modern icon cards) ===== -->
  <section class="relative z-10 px-6 py-10">
    <div class="max-w-4xl mx-auto grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4" v-reveal>
      <div
        v-for="stat in [
          { value: plansCount, label: t.stats_plans, icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
          { value: photosCount, label: t.stats_photos, icon: 'M4 5h16v14H4V5zm4 10l3-4 2 2 3-4 4 6H8z' },
          { value: trainersCount, label: t.trainers_title ?? 'គ្រូបង្វឹក', icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 10-8 0 4 4 0 008 0zm8 0a4 4 0 10-8 0 4 4 0 008 0z' },
          { value: promoCount, label: t.stats_promotions ?? 'Promotions', icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.956a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.955c.3.922-.755 1.688-1.538 1.118l-3.368-2.447a1 1 0 00-1.176 0l-3.368 2.447c-.783.57-1.838-.196-1.539-1.118l1.287-3.955a1 1 0 00-.363-1.118L2.063 9.383c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.956z' },
        ]"
        :key="stat.label"
        :class="theme.card"
        class="rounded-2xl border p-4 sm:p-5 flex flex-col items-center text-center shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-emerald-500/10"
      >
        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-2.5">
          <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" :d="stat.icon" />
          </svg>
        </div>
        <p class="text-2xl sm:text-3xl font-extrabold text-emerald-400 leading-none">{{ stat.value }}</p>
        <p class="text-xs mt-1.5" :class="theme.textMuted">{{ stat.label }}</p>
      </div>
    </div>
  </section>

  <!-- ===== ABOUT (split layout: text + photo, feels more like a real brand page) ===== -->
  <section id="about" class="max-w-6xl mx-auto px-6 py-24 scroll-mt-20">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">
      <div v-reveal>
        <span class="inline-block text-xs font-semibold uppercase tracking-widest text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full mb-4">
          {{ t.about_badge ?? 'អំពីយើង' }}
        </span>
        <h2 class="text-2xl sm:text-4xl font-bold mb-5 leading-tight">{{ t.about_title }}</h2>
        <p class="leading-relaxed text-base sm:text-lg mb-6" :class="theme.textMuted">
          {{ settings?.about_text ?? t.about_default }}
        </p>
        <Link
          :href="`/gym/${tenant.slug}/contact`"
          class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-400 hover:scale-105 text-slate-950 font-semibold rounded-full px-6 py-3 transition-all duration-200 shadow-lg shadow-emerald-500/20"
        >
          {{ t.hero_cta }}
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </Link>
      </div>

      <div v-reveal="{ delay: 120 }" class="relative">
        <div class="absolute -inset-3 rounded-[1.75rem] bg-emerald-400/10 blur-2xl pointer-events-none"></div>
        <div class="relative rounded-2xl overflow-hidden aspect-[4/3] shadow-2xl">
          <img
            v-if="aboutImage"
            :src="aboutImage"
            :alt="tenant.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full bg-gradient-to-br from-emerald-800 via-slate-900 to-teal-800"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== WHY CHOOSE US (new section — always-available feature cards) ===== -->
  <section :class="theme.bgAlt" class="py-24 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-6">
      <h2 class="text-2xl sm:text-3xl font-bold text-center mb-3" v-reveal>{{ t.why_title ?? 'ហេតុអ្វីជ្រើសរើសយើង' }}</h2>
      <p class="text-center mb-12 max-w-lg mx-auto" :class="theme.textMuted" v-reveal>
        {{ t.why_subtitle ?? 'អ្វីៗគ្រប់យ៉ាងដែលអ្នកត្រូវការសម្រាប់ដំណើរហាត់ប្រាណរបស់អ្នក' }}
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div
          v-for="(item, i) in whyChooseUs"
          :key="item.title"
          v-reveal="{ delay: i * 90 }"
          :class="[theme.card, theme.border]"
          class="group rounded-2xl p-6 border transition-all duration-300 hover:-translate-y-1.5 hover:border-emerald-500/40 hover:shadow-xl hover:shadow-emerald-500/10"
        >
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110 group-hover:bg-emerald-500/20">
            <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
              <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
            </svg>
          </div>
          <h3 class="font-semibold text-base mb-1.5">{{ item.title }}</h3>
          <p class="text-sm leading-relaxed" :class="theme.textMuted">{{ item.desc }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== SERVICES SHOWCASE (modern minimal cards, linked to class detail) ===== -->
  <section v-if="servicesPreview.length" id="services" class="py-24 transition-colors duration-300 scroll-mt-20">
    <div class="max-w-6xl mx-auto px-6">
      <span class="block text-center mb-3" v-reveal>
        <span class="inline-block text-xs font-semibold uppercase tracking-widest text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full">
          {{ t.services_badge ?? 'សេវាកម្ម' }}
        </span>
      </span>
      <h2 class="text-2xl sm:text-3xl font-bold text-center mb-3" v-reveal>{{ t.services_title ?? 'OUR SERVICES' }}</h2>
      <p class="text-center mb-12 max-w-lg mx-auto" :class="theme.textMuted" v-reveal>
        {{ t.services_subtitle ?? 'Everything you need for your fitness journey' }}
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <Link
          v-for="(cls, i) in servicesPreview"
          :key="cls.id"
          :href="`/gym/${tenant.slug}/classes/${cls.id}`"
          v-reveal="{ delay: i * 80 }"
          :class="[theme.card, theme.border]"
          class="group block rounded-2xl overflow-hidden border transition-colors duration-200 hover:border-emerald-500/50"
        >
          <div class="relative h-40 overflow-hidden">
            <div
              v-if="serviceBgImage(cls, i)"
              class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105"
              :style="`background-image: url(${serviceBgImage(cls, i)})`"
            ></div>
            <div v-else class="absolute inset-0 flex items-center justify-center" :class="theme.bgAlt">
              <svg class="w-8 h-8 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 12a2 2 0 100 4h1a2 2 0 100-4m-1 0a2 2 0 110-4h1a2 2 0 110 4m14 0a2 2 0 100 4h-1a2 2 0 100-4m1 0a2 2 0 110-4h-1a2 2 0 110 4" />
              </svg>
            </div>
            <span
              v-if="isLiveNow(cls)"
              class="absolute top-3 left-3 inline-flex items-center gap-1 text-[11px] font-semibold text-white bg-emerald-500/90 backdrop-blur px-2.5 py-1 rounded-full"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-white animate-[pulse_1.5s_ease-in-out_infinite]"></span>
              {{ t.classes_live_now ?? 'ផ្សាយផ្ទាល់' }}
            </span>
          </div>

          <div class="p-5">
            <p class="text-xs mb-1.5 flex items-center gap-1.5" :class="theme.textMuted">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ formatTime(cls.start_time) }} – {{ formatTime(cls.end_time) }}
            </p>
            <h3 class="font-semibold text-base leading-snug group-hover:text-emerald-400 transition-colors">{{ cls.name }}</h3>
          </div>
        </Link>
      </div>

      <div class="text-center mt-10" v-reveal>
        <Link
          :href="`/gym/${tenant.slug}/classes`"
          class="inline-flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 font-medium transition-colors"
        >
          {{ t.classes_view_all ?? 'មើលកាលវិភាគពេញលេញ' }}
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </Link>
      </div>
    </div>
  </section>

  <!-- ===== PRICING TEASER (full plans page lives at /pricing) — matches GymPricing.vue styling ===== -->
  <section id="pricing" :class="theme.bgAlt" class="py-24 transition-colors duration-300 scroll-mt-20">
    <div class="max-w-5xl mx-auto px-6">
      <h2 class="text-2xl sm:text-3xl font-bold text-center mb-12" v-reveal>{{ t.pricing_title }}</h2>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 lg:gap-8 items-start pt-4">
        <div
          v-for="(plan, i) in plansPreview"
          :key="plan.id"
          v-reveal="{ delay: i * 100 }"
          class="relative group"
          :class="isFeatured(i) ? 'lg:scale-105 mt-4 lg:mt-0' : ''"
        >
          <!-- Ambient halo glow, same treatment as the full Pricing page -->
          <div
            v-if="isFeatured(i)"
            class="absolute -inset-3 rounded-[1.75rem] bg-gradient-to-br from-amber-400 via-yellow-300 to-amber-500 blur-2xl opacity-40 animate-[glowPulse_3s_ease-in-out_infinite] pointer-events-none"
          ></div>
          <div
            v-else
            class="absolute -inset-3 rounded-[1.75rem] bg-emerald-400 blur-2xl opacity-0 group-hover:opacity-25 transition-opacity duration-500 pointer-events-none"
          ></div>

          <!-- Badge lives OUTSIDE the overflow-hidden card so its top half isn't clipped -->
          <span
            v-if="isFeatured(i)"
            class="absolute -top-3 left-1/2 -translate-x-1/2 z-10 bg-gradient-to-r from-amber-300 to-yellow-400 text-slate-950 text-xs font-bold px-3 py-1 rounded-full shadow-lg shadow-amber-500/40 tracking-wide whitespace-nowrap"
          >
            ⭐ {{ t.pricing_popular ?? 'MOST POPULAR' }}
          </span>

          <div
            :class="[
              theme.card,
              'relative rounded-2xl p-7 pt-8 border overflow-hidden transition-all duration-300 hover:-translate-y-2 flex flex-col',
              isFeatured(i) ? 'border-amber-400/60 shadow-2xl shadow-amber-500/20' : 'hover:border-emerald-500/50 hover:shadow-2xl hover:shadow-emerald-500/20',
            ]"
          >
            <div v-if="isFeatured(i)" class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-300"></div>

            <span
              v-if="promotionFor(plan.id)"
              class="absolute top-4 right-4 bg-amber-400 text-slate-950 text-xs font-bold px-2.5 py-1 rounded-full shadow animate-[pulse_2s_ease-in-out_infinite]"
            >
              -{{ promotionFor(plan.id).discount_value }}{{ promotionFor(plan.id).discount_type === 'percentage' ? '%' : '$' }}
            </span>

            <div class="relative w-12 h-12 mb-4">
              <div
                v-if="isFeatured(i)"
                class="absolute -inset-1.5 rounded-2xl bg-amber-400/40 blur-md animate-[pulse_2.5s_ease-in-out_infinite]"
              ></div>
              <div
                class="relative w-12 h-12 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110"
                :class="isFeatured(i) ? 'bg-gradient-to-br from-amber-300 to-yellow-400' : 'bg-emerald-500/10 group-hover:bg-emerald-500/20'"
              >
                <svg class="w-6 h-6" :class="isFeatured(i) ? 'text-slate-950' : 'text-emerald-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" :d="tierIcons[i % tierIcons.length]" />
                </svg>
              </div>
            </div>

            <h3 class="font-semibold text-lg mb-1">{{ plan.name }}</h3>
            <div class="flex items-baseline gap-1 mb-1">
              <span
                class="text-3xl font-extrabold group-hover:scale-105 transition-transform origin-left inline-block"
                :class="isFeatured(i) ? 'bg-gradient-to-r from-amber-300 via-yellow-300 to-amber-400 bg-clip-text text-transparent' : 'text-emerald-400'"
              >${{ plan.price }}</span>
              <span class="text-xs" :class="theme.textMuted">/ {{ plan.duration_days }} {{ t.pricing_days_suffix ?? 'days' }}</span>
            </div>

            <ul v-if="featuresFor(plan).length" class="space-y-2 my-4 flex-1">
              <li v-for="(feature, fi) in featuresFor(plan)" :key="fi" class="flex items-start gap-2 text-sm">
                <svg class="w-4 h-4 mt-0.5 shrink-0" :class="isFeatured(i) ? 'text-amber-400' : 'text-emerald-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span :class="theme.textMuted">{{ feature }}</span>
              </li>
            </ul>
            <p v-else class="text-sm mb-6 flex-1" :class="theme.textMuted">{{ plan.description ?? '' }}</p>

            <Link
              :href="`/gym/${tenant.slug}/pricing`"
              class="block text-center font-semibold rounded-lg py-2.5 transition-all hover:scale-[1.02]"
              :class="isFeatured(i)
                ? 'bg-gradient-to-r from-amber-300 to-yellow-400 hover:from-amber-200 hover:to-yellow-300 text-slate-950 shadow-lg shadow-amber-500/30 hover:shadow-amber-400/50'
                : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 hover:shadow-lg hover:shadow-emerald-500/30'"
            >
              {{ t.pricing_choose }}
            </Link>
          </div>
        </div>
        <p v-if="!plansPreview?.length" class="col-span-3 text-center py-8" :class="theme.textMuted">{{ t.pricing_empty }}</p>
      </div>
      <div v-if="plans?.length > 3" class="text-center mt-10" v-reveal>
        <Link
          :href="`/gym/${tenant.slug}/pricing`"
          class="inline-flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 font-medium transition-colors"
        >
          {{ t.pricing_view_all ?? 'View all plans' }}
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </Link>
      </div>
    </div>
  </section>

  <!-- ===== NEWS & PROMOTIONS (template-style blog cards) ===== -->
  <section v-if="activePromotions?.length" id="promotions" class="py-24 transition-colors duration-300">
    <div class="max-w-6xl mx-auto px-6">
      <h2 class="text-2xl sm:text-3xl font-bold text-center mb-12" v-reveal>{{ t.promotions_title ?? 'NEWS & PROMOTIONS' }}</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="(promo, i) in activePromotions"
          :key="promo.id"
          v-reveal="{ delay: i * 90 }"
          :class="[theme.card, theme.border]"
          class="group rounded-2xl overflow-hidden border transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-emerald-500/10"
        >
          <div class="relative h-40 bg-gradient-to-br from-emerald-700 via-slate-900 to-teal-700 overflow-hidden">
            <div class="absolute inset-0 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
              <span class="text-white font-extrabold text-3xl">
                -{{ promo.discount_value }}{{ promo.discount_type === 'percentage' ? '%' : '$' }}
              </span>
            </div>
          </div>
          <div class="p-5">
            <span class="inline-block text-[11px] font-semibold uppercase tracking-wide text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full mb-2">
              {{ t.promotions_badge ?? 'Promotion' }}
            </span>
            <h3 class="font-semibold mb-1">{{ promo.title ?? promo.name }}</h3>
            <p v-if="promo.description" class="text-sm line-clamp-2" :class="theme.textMuted">{{ promo.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== TRAINERS TEASER (photo + name now link to trainer detail page) ===== -->
  <section v-if="trainers?.length" id="trainers" :class="theme.bgAlt" class="py-24 transition-colors duration-300 scroll-mt-20">
    <div class="max-w-5xl mx-auto px-6">
      <h2 class="text-2xl sm:text-3xl font-bold text-center mb-3" v-reveal>{{ t.trainers_title ?? 'Our Trainers' }}</h2>
      <p class="text-center mb-12 max-w-lg mx-auto" :class="theme.textMuted" v-reveal>
        {{ t.trainers_subtitle ?? 'The coaches behind every session.' }}
      </p>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
        <div
          v-for="(trainer, i) in trainersPreview"
          :key="trainer.id"
          v-reveal="{ delay: i * 80 }"
          class="group text-center"
        >
          <Link :href="`/gym/${tenant.slug}/trainers/${trainer.id}`" class="block">
            <div class="relative aspect-square rounded-2xl overflow-hidden mb-4 shadow-lg transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-2xl group-hover:shadow-emerald-500/10">
              <img
                v-if="trainer.photo_url && !brokenTrainerPhotos.has(trainer.id)"
                :src="trainer.photo_url"
                :alt="trainer.name"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                @error="onTrainerPhotoError(trainer.id)"
              />
              <div v-else :class="theme.card" class="w-full h-full flex items-center justify-center text-3xl font-bold text-emerald-400">
                {{ trainer.name?.charAt(0) }}
              </div>
            </div>
            <h3 class="font-semibold text-sm group-hover:text-emerald-400 transition-colors">{{ trainer.name }}</h3>
            <p v-if="trainer.specialty" class="text-xs text-emerald-400 mt-1 uppercase tracking-wide">{{ trainer.specialty }}</p>
          </Link>
        </div>
      </div>
      <div class="text-center mt-10" v-reveal>
        <Link
          :href="`/gym/${tenant.slug}/trainers`"
          class="inline-flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 font-medium transition-colors"
        >
          {{ t.trainers_view_all ?? 'មើលគ្រូបង្វឹកទាំងអស់' }}
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </Link>
      </div>
    </div>
  </section>

  <!-- ===== GALLERY TEASER (full grid + lightbox lives at /gallery) — now video-aware ===== -->
  <section v-if="gallery?.length" id="gallery" class="py-24 transition-colors duration-300 scroll-mt-20">
    <div class="max-w-6xl mx-auto px-6">
      <h2 class="text-2xl sm:text-3xl font-bold text-center mb-3" v-reveal>{{ t.gallery_title }}</h2>
      <p class="text-center mb-12 max-w-lg mx-auto" :class="theme.textMuted" v-reveal>
        {{ t.gallery_subtitle ?? 'A look inside the gym.' }}
      </p>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <button
          v-for="(img, i) in galleryPreview"
          :key="img.id"
          v-reveal="{ delay: i * 60 }"
          type="button"
          class="overflow-hidden rounded-xl aspect-square group relative block w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
          @click="openLightbox(i)"
        >
          <video
            v-if="isVideo(img)"
            :src="mediaSrc(img)"
            muted loop playsinline autoplay
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
          />
          <img
            v-else
            :src="mediaSrc(img)"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
            loading="lazy"
          />
          <div class="absolute inset-0 bg-slate-950/0 group-hover:bg-slate-950/30 transition-colors duration-300"></div>
          <div v-if="isVideo(img)" class="absolute inset-0 flex items-center justify-center">
            <svg class="w-8 h-8 text-white opacity-90 group-hover:scale-110 transition-transform duration-300 drop-shadow" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          </div>
        </button>
      </div>

      <div class="text-center mt-10" v-reveal>
        <Link
          :href="`/gym/${tenant.slug}/gallery`"
          class="inline-flex items-center gap-1.5 text-emerald-400 hover:text-emerald-300 font-medium transition-colors"
        >
          {{ t.gallery_view_all ?? 'មើលរូបភាពទាំងអស់' }}
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </Link>
      </div>
    </div>
  </section>

  <!-- ===== GALLERY LIGHTBOX — now video-aware, with counter + caption ===== -->
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="lightboxOpen"
      class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-sm flex items-center justify-center p-6"
      @click="closeLightbox"
      @keydown="onKeydown"
      tabindex="0"
      role="dialog"
      aria-modal="true"
    >
      <div v-if="gallery.length > 1" class="absolute top-5 left-1/2 -translate-x-1/2 text-white/70 text-sm font-medium tracking-wide">
        {{ lightboxIndex + 1 }} / {{ gallery.length }}
      </div>

      <button @click="closeLightbox" class="absolute top-5 right-5 text-white/70 hover:text-white transition-colors" aria-label="Close">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <button v-if="gallery.length > 1" @click="prevImage" class="absolute left-4 sm:left-8 text-white/70 hover:text-white hover:scale-110 transition-all" aria-label="Previous">
        <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </button>

      <div v-if="activeItem" class="flex flex-col items-center max-w-full" @click.stop>
        <video
          v-if="isVideo(activeItem)"
          :src="mediaSrc(activeItem)"
          controls autoplay playsinline
          class="max-w-full max-h-[80vh] rounded-lg shadow-2xl animate-[fadeUp_0.25s_ease-out]"
        />
        <img
          v-else
          :src="mediaSrc(activeItem)"
          class="max-w-full max-h-[80vh] rounded-lg shadow-2xl animate-[fadeUp_0.25s_ease-out]"
        />
        <p v-if="activeItem.caption" class="mt-4 text-white/80 text-sm text-center max-w-lg px-4">
          {{ activeItem.caption }}
        </p>
      </div>

      <button v-if="gallery.length > 1" @click="nextImage" class="absolute right-4 sm:right-8 text-white/70 hover:text-white hover:scale-110 transition-all" aria-label="Next">
        <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </button>
    </div>
  </Transition>

  <!-- ===== CONTACT TEASER (full form lives at /contact) ===== -->
  <section id="contact-teaser" :class="theme.bgAlt" class="py-24 transition-colors duration-300">
    <div class="max-w-2xl mx-auto px-6 text-center" v-reveal>
      <h2 class="text-2xl sm:text-3xl font-bold mb-4">{{ t.contact_title }}</h2>
      <p class="mb-8" :class="theme.textMuted">{{ t.pricing_cta_body ?? 'Reach out and our team will help you get started.' }}</p>
      <Link
        :href="`/gym/${tenant.slug}/contact`"
        class="inline-block bg-emerald-500 hover:bg-emerald-400 hover:scale-105 text-slate-950 font-semibold rounded-full px-8 py-3.5 transition-all duration-200 shadow-xl shadow-emerald-500/25"
      >
        {{ t.contact_title }}
      </Link>
    </div>
  </section>
</template>

<style>
@keyframes kenburns {
  0%   { transform: scale(1) translate(0, 0); }
  100% { transform: scale(1.15) translate(-1.5%, -1.5%); }
}
@keyframes gradientShift {
  0%, 100% { background-position: 0% 50%; }
  50%      { background-position: 100% 50%; }
}
@keyframes float1 {
  0%, 100% { transform: translate(0, 0); }
  50%      { transform: translate(30px, 40px); }
}
@keyframes float2 {
  0%, 100% { transform: translate(0, 0); }
  50%      { transform: translate(-25px, -30px); }
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}
@keyframes bounceY {
  0%, 100% { transform: translate(-50%, 0); }
  50%      { transform: translate(-50%, 8px); }
}
@keyframes glowPulse {
  0%, 100% { opacity: 0.35; transform: scale(1); }
  50%      { opacity: 0.55; transform: scale(1.03); }
}
.hero-fade-enter-active,
.hero-fade-leave-active {
  transition: opacity 1.2s ease;
}
.hero-fade-enter-from,
.hero-fade-leave-to {
  opacity: 0;
}
</style>