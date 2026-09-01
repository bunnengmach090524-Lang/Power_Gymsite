<script setup>
import { ref, computed, reactive } from 'vue'
import ClientLayout from '../../Layouts/ClientLayout.vue'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  tenant: Object,
  settings: Object,
  gallery: Array,
})

const { theme } = useTheme()
const { t } = useLang()

// ===== 2. Category filter tabs =====
// Only shows if at least one item has a `category` field — safe no-op otherwise.
const categories = computed(() => {
  const cats = [...new Set((props.gallery ?? []).map(g => g.category).filter(Boolean))]
  return cats.length ? cats : []
})
const activeCategory = ref('all')

const filteredGallery = computed(() => {
  if (!categories.value.length || activeCategory.value === 'all') return props.gallery ?? []
  return (props.gallery ?? []).filter(g => g.category === activeCategory.value)
})

function categoryLabel(cat) {
  if (cat === 'all') return t.value?.gallery_filter_all ?? 'ទាំងអស់'
  return cat
}

// ===== 6. Video support =====
// An item is treated as a video if it has a `video_url`, or `media_kind === 'video'`.
// NOTE: `type` is reserved for hero_banner/gallery/trainer_photo/logo — never overload it here.
function isVideo(img) {
  return img?.media_kind === 'video' || !!img?.video_url
}
function mediaSrc(img) {
  return isVideo(img) ? (img.video_url ?? img.image_url) : img.image_url
}

// ===== 4. Loading skeleton / blur-up =====
const loaded = reactive({})
function onMediaLoaded(id) {
  loaded[id] = true
}

// ===== 1. Lightbox: image counter + caption, 5. swipe support =====
const lightboxIndex = ref(null)
const lightboxOpen = computed(() => lightboxIndex.value !== null)
const activeItem = computed(() => lightboxIndex.value !== null ? filteredGallery.value[lightboxIndex.value] : null)

function openLightbox(i) { lightboxIndex.value = i }
function closeLightbox() { lightboxIndex.value = null }
function nextImage(e) {
  e?.stopPropagation()
  if (lightboxIndex.value === null) return
  lightboxIndex.value = (lightboxIndex.value + 1) % filteredGallery.value.length
}
function prevImage(e) {
  e?.stopPropagation()
  if (lightboxIndex.value === null) return
  lightboxIndex.value = (lightboxIndex.value - 1 + filteredGallery.value.length) % filteredGallery.value.length
}
function onKeydown(e) {
  if (!lightboxOpen.value) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowRight') nextImage()
  if (e.key === 'ArrowLeft') prevImage()
}

// Swipe handling (mobile lightbox)
let touchStartX = 0
let touchStartY = 0
const SWIPE_THRESHOLD = 50

function onTouchStart(e) {
  touchStartX = e.changedTouches[0].clientX
  touchStartY = e.changedTouches[0].clientY
}
function onTouchEnd(e) {
  const dx = e.changedTouches[0].clientX - touchStartX
  const dy = e.changedTouches[0].clientY - touchStartY
  // ignore mostly-vertical swipes so it doesn't fight page scroll
  if (Math.abs(dx) < SWIPE_THRESHOLD || Math.abs(dx) < Math.abs(dy)) return
  if (dx < 0) nextImage()
  else prevImage()
}
</script>

<template>
  <!-- ===== PAGE HEADER ===== -->
  <section :class="[theme.bgAlt, theme.border]" class="border-b transition-colors duration-300">
    <div class="max-w-5xl mx-auto px-6 py-16 sm:py-20 text-center" v-reveal>
      <h1 class="text-3xl sm:text-4xl font-extrabold mb-3">{{ t.gallery_title ?? 'Gallery' }}</h1>
      <p class="max-w-lg mx-auto" :class="theme.textMuted">
        {{ t.gallery_subtitle ?? 'A look inside the gym.' }}
      </p>
    </div>
  </section>

  <!-- ===== 2. CATEGORY FILTER TABS ===== -->
  <section v-if="categories.length" class="max-w-6xl mx-auto px-6 pt-10">
    <div class="flex justify-center gap-2 flex-wrap" v-reveal>
      <button
        v-for="cat in ['all', ...categories]"
        :key="cat"
        type="button"
        @click="activeCategory = cat"
        class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 capitalize"
        :class="activeCategory === cat
          ? 'bg-emerald-500 text-slate-950 shadow shadow-emerald-500/25'
          : [theme.card, theme.textMuted, 'border hover:border-emerald-500/50']"
      >
        {{ categoryLabel(cat) }}
      </button>
    </div>
  </section>

  <!-- ===== GALLERY GRID ===== -->
  <section class="max-w-6xl mx-auto px-6 py-16 sm:py-20">
    <!-- 3. Few items (<4): centered, evenly-sized grid instead of lopsided masonry -->
    <div
      v-if="filteredGallery.length && filteredGallery.length < 4"
      class="flex flex-wrap justify-center gap-4"
    >
      <button
        v-for="(img, i) in filteredGallery"
        :key="img.id"
        v-reveal="{ delay: i * 80 }"
        type="button"
        class="relative w-full sm:w-72 h-72 overflow-hidden rounded-2xl group cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
        :class="theme.card"
        @click="openLightbox(i)"
      >
        <!-- skeleton shimmer -->
        <div v-if="!loaded[img.id]" class="absolute inset-0 animate-pulse bg-gradient-to-br from-slate-800 to-slate-900"></div>

        <video
          v-if="isVideo(img)"
          :src="mediaSrc(img)"
          muted loop playsinline autoplay
          class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
          :class="loaded[img.id] ? 'opacity-100' : 'opacity-0'"
          @loadeddata="onMediaLoaded(img.id)"
        />
        <img
          v-else
          :src="mediaSrc(img)"
          loading="lazy"
          class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
          :class="loaded[img.id] ? 'opacity-100' : 'opacity-0'"
          @load="onMediaLoaded(img.id)"
        />

        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="absolute inset-0 flex items-center justify-center">
          <svg v-if="isVideo(img)" class="w-10 h-10 text-white opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-300 drop-shadow" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          <svg v-else class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-300 drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        </div>

        <p v-if="img.caption" class="absolute bottom-0 inset-x-0 text-left text-xs text-white/90 bg-gradient-to-t from-slate-950/80 to-transparent px-3 pt-6 pb-2 truncate">
          {{ img.caption }}
        </p>
      </button>
    </div>

    <!-- Masonry grid for 4+ items -->
    <div v-else-if="filteredGallery.length" class="grid grid-cols-2 sm:grid-cols-4 auto-rows-[140px] sm:auto-rows-[180px] gap-3">
      <button
        v-for="(img, i) in filteredGallery"
        :key="img.id"
        v-reveal="{ delay: i * 60 }"
        type="button"
        class="overflow-hidden rounded-xl group cursor-zoom-in relative focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
        :class="[theme.card, i % 7 === 0 ? 'col-span-2 row-span-2' : i % 5 === 0 ? 'row-span-2' : '']"
        @click="openLightbox(i)"
      >
        <!-- skeleton shimmer -->
        <div v-if="!loaded[img.id]" class="absolute inset-0 animate-pulse bg-gradient-to-br from-slate-800 to-slate-900"></div>

        <video
          v-if="isVideo(img)"
          :src="mediaSrc(img)"
          muted loop playsinline autoplay
          class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
          :class="loaded[img.id] ? 'opacity-100' : 'opacity-0'"
          @loadeddata="onMediaLoaded(img.id)"
        />
        <img
          v-else
          :src="mediaSrc(img)"
          loading="lazy"
          class="w-full h-full object-cover transition-all duration-500 group-hover:scale-110"
          :class="loaded[img.id] ? 'opacity-100' : 'opacity-0'"
          @load="onMediaLoaded(img.id)"
        />

        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="absolute inset-0 flex items-center justify-center">
          <svg v-if="isVideo(img)" class="w-10 h-10 text-white opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-300 drop-shadow" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
          <svg v-else class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-300 drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        </div>

        <p v-if="img.caption" class="absolute bottom-0 inset-x-0 text-left text-xs text-white/90 bg-gradient-to-t from-slate-950/80 to-transparent px-3 pt-6 pb-2 truncate">
          {{ img.caption }}
        </p>
      </button>
    </div>

    <div v-else class="text-center py-20">
      <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center" :class="theme.bgAlt">
        <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <p :class="theme.textMuted">{{ t.gallery_empty ?? 'No photos yet.' }}</p>
    </div>
  </section>

  <!-- ===== LIGHTBOX ===== -->
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
      @touchstart="onTouchStart"
      @touchend="onTouchEnd"
      tabindex="0"
      role="dialog"
      aria-modal="true"
    >
      <!-- 1. Image counter -->
      <div v-if="filteredGallery.length > 1" class="absolute top-5 left-1/2 -translate-x-1/2 text-white/70 text-sm font-medium tracking-wide">
        {{ lightboxIndex + 1 }} / {{ filteredGallery.length }}
      </div>

      <button @click="closeLightbox" class="absolute top-5 right-5 text-white/70 hover:text-white transition-colors" aria-label="Close">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <button v-if="filteredGallery.length > 1" @click="prevImage" class="absolute left-4 sm:left-8 text-white/70 hover:text-white hover:scale-110 transition-all" aria-label="Previous">
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
        <!-- 1. Caption -->
        <p v-if="activeItem.caption" class="mt-4 text-white/80 text-sm text-center max-w-lg px-4">
          {{ activeItem.caption }}
        </p>
      </div>

      <button v-if="filteredGallery.length > 1" @click="nextImage" class="absolute right-4 sm:right-8 text-white/70 hover:text-white hover:scale-110 transition-all" aria-label="Next">
        <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </button>
    </div>
  </Transition>
</template>

<style>
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>