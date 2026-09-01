<script setup>
import { ref } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useLang } from '@/composables/useLang'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  settings: Object,
  tenant: Object,
  logoImages: Array,
  heroImages: Array,
  galleryImages: Array,
})

const { t } = useLang()
const page = usePage()

const fieldClass = 'w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white transition-all duration-200 ease-in-out hover:border-slate-400 dark:hover:border-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/40'

const form = useForm({
  tagline: props.settings?.tagline ?? '',
  about_text: props.settings?.about_text ?? '',
  primary_color: props.settings?.primary_color ?? '#10b981',
  secondary_color: props.settings?.secondary_color ?? '#0f172a',
  contact_email: props.settings?.contact_email ?? '',
  contact_phone: props.settings?.contact_phone ?? '',
  logo_image_id: props.settings?.logo_image_id ?? null,
  hero_banner_image_id: props.settings?.hero_banner_image_id ?? null,
random_hero_banner: props.settings?.random_hero_banner ?? false,
  virtual_tour_url: props.settings?.virtual_tour_url ?? '',
  social_links: {
    facebook: props.settings?.social_links?.facebook ?? '',
    instagram: props.settings?.social_links?.instagram ?? '',
    tiktok: props.settings?.social_links?.tiktok ?? '',
    telegram: props.settings?.social_links?.telegram ?? '',
    youtube: props.settings?.social_links?.youtube ?? '',
    whatsapp: props.settings?.social_links?.whatsapp ?? '',
    twitter: props.settings?.social_links?.twitter ?? '',
    linkedin: props.settings?.social_links?.linkedin ?? '',
  },
  // Location — lives on the Tenant model, submitted together with website
  // settings since it's one form/one save button on this page.
  address: props.tenant?.address ?? '',
  latitude: props.tenant?.latitude ?? '',
  longitude: props.tenant?.longitude ?? '',
})

function submit() {
  form.patch('/dashboard/website-editor', { preserveScroll: true })
}

// Optional convenience: let the admin auto-fill lat/long from their current
// browser location instead of having to look up coordinates manually.
const locating = ref(false)
const locateError = ref('')
function useCurrentLocation() {
  if (!navigator.geolocation) {
    locateError.value = t.value.website_location_unsupported ?? 'ឧបករណ៍នេះមិនគាំទ្រ geolocation ទេ។'
    return
  }
  locating.value = true
  locateError.value = ''
  navigator.geolocation.getCurrentPosition(
    (position) => {
      form.latitude = position.coords.latitude.toFixed(6)
      form.longitude = position.coords.longitude.toFixed(6)
      locating.value = false
    },
    () => {
      locateError.value = t.value.website_location_denied ?? 'មិនអាចទាញយកទីតាំងបានទេ។ សូមបញ្ចូលដោយដៃ។'
      locating.value = false
    }
  )
}

// ===== Media uploads =====
// Logo / hero banner stay simple single-image uploads, unchanged.
// Gallery gains: media_kind toggle (image/video), category, caption, and
// multi-file selection (`images[]`) so several photos can go up in one go.
const uploadForms = {
  logo: useForm({ image: null, type: 'logo' }),
  hero_banner: useForm({ image: null, type: 'hero_banner' }),
  gallery: useForm({
    images: [],  // batch of File objects, image mode
    video: null, // single video file, video mode
    type: 'gallery',
    media_kind: 'image',
    category: '',
    caption: '',
  }),
}

function onFileChange(type, event) {
  const files = Array.from(event.target.files ?? [])
  if (type === 'gallery') {
    if (uploadForms.gallery.media_kind === 'video') {
      uploadForms.gallery.video = files[0] ?? null
    } else {
      uploadForms.gallery.images = files
    }
  } else {
    uploadForms[type].image = files[0] ?? null
  }
}

// Switches the gallery uploader between image and video mode. Clears
// whichever file selection no longer applies so nothing stale is submitted.
function setGalleryMediaKind(kind) {
  uploadForms.gallery.media_kind = kind
  uploadForms.gallery.images = []
  uploadForms.gallery.video = null
}

function galleryFileLabel() {
  if (uploadForms.gallery.media_kind === 'video') {
    return uploadForms.gallery.video ? uploadForms.gallery.video.name : t.value.website_choose_file
  }
  const n = uploadForms.gallery.images.length
  if (n === 0) return t.value.website_choose_file
  if (n === 1) return uploadForms.gallery.images[0].name
  return `${n} ${t.value.website_files_selected ?? 'files selected'}`
}

function upload(type) {
  uploadForms[type].post('/dashboard/media-images', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      if (type === 'gallery') {
        uploadForms.gallery.reset('images', 'video', 'category', 'caption', 'media_kind')
      } else {
        uploadForms[type].reset('image')
      }
    },
  })
}

function deleteImage(id) {
  if (confirm(t.value.website_confirm_delete_image)) {
    router.delete(`/dashboard/media-images/${id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <div class="w-full p-6 sm:p-8 animate-fade-in-up">
    <h1 class="text-xl font-semibold text-slate-900 dark:text-white mb-6">{{ t.website_editor_title }}</h1>

    <div
      v-if="page.props.flash?.success"
      class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-300 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-lg px-4 py-3 mb-4 max-w-3xl transition-opacity duration-300"
    >
      {{ page.props.flash.success }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- LEFT COLUMN: Basic settings -->
      <form
        @submit.prevent="submit"
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 sm:p-8 space-y-4 h-fit transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700"
      >
        <div>
          <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_tagline }}</label>
          <input v-model="form.tagline" type="text" :class="fieldClass" />
          <p v-if="form.errors.tagline" class="text-red-500 dark:text-red-400 text-xs mt-1.5">{{ form.errors.tagline }}</p>
        </div>

        <div>
          <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_about }}</label>
          <textarea v-model="form.about_text" rows="4" :class="[fieldClass, 'resize-y']"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_primary_color }}</label>
            <div class="flex items-center gap-2">
              <input v-model="form.primary_color" type="color" class="w-10 h-10 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer transition-transform duration-150 hover:scale-105" />
              <input v-model="form.primary_color" type="text" maxlength="7" :class="[fieldClass, 'flex-1 min-w-0']" />
            </div>
          </div>
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_secondary_color }}</label>
            <div class="flex items-center gap-2">
              <input v-model="form.secondary_color" type="color" class="w-10 h-10 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 cursor-pointer transition-transform duration-150 hover:scale-105" />
              <input v-model="form.secondary_color" type="text" maxlength="7" :class="[fieldClass, 'flex-1 min-w-0']" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_contact_email }}</label>
            <input v-model="form.contact_email" type="email" :class="fieldClass" />
            <p v-if="form.errors.contact_email" class="text-red-500 dark:text-red-400 text-xs mt-1.5">{{ form.errors.contact_email }}</p>
          </div>
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_contact_phone }}</label>
            <input v-model="form.contact_phone" type="tel" :class="fieldClass" />
          </div>
        </div>

        <!--
          NEW: Location section — address/latitude/longitude live on the Tenant
          model (see WebsiteSettingsController@update), but are edited here
          alongside the rest of the site content since it's one save action.
          These feed the public ClientLayout footer + Contact page map.
        -->
        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
          <p class="text-sm font-medium text-slate-900 dark:text-white mt-4 mb-1.5">{{ t.website_location ?? 'ទីតាំង Gym' }}</p>
          <p class="text-xs text-slate-400 mb-3">
            {{ t.website_location_hint ?? 'ប្រើសម្រាប់បង្ហាញ address និង map ក្នុង footer និងទំព័រ Contact' }}
          </p>

          <div class="mb-3">
            <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_address ?? 'អាសយដ្ឋាន' }}</label>
            <input v-model="form.address" type="text" :placeholder="t.website_address_placeholder ?? 'ឧ. St 271, Phnom Penh'" :class="fieldClass" />
            <p v-if="form.errors.address" class="text-red-500 dark:text-red-400 text-xs mt-1.5">{{ form.errors.address }}</p>
          </div>

          <div class="grid grid-cols-2 gap-3 mb-2">
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">Latitude</label>
              <input v-model="form.latitude" type="text" inputmode="decimal" placeholder="11.5564" :class="fieldClass" />
              <p v-if="form.errors.latitude" class="text-red-500 dark:text-red-400 text-xs mt-1.5">{{ form.errors.latitude }}</p>
            </div>
            <div>
              <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">Longitude</label>
              <input v-model="form.longitude" type="text" inputmode="decimal" placeholder="104.9282" :class="fieldClass" />
              <p v-if="form.errors.longitude" class="text-red-500 dark:text-red-400 text-xs mt-1.5">{{ form.errors.longitude }}</p>
            </div>
          </div>

          <button
            type="button"
            @click="useCurrentLocation"
            :disabled="locating"
            class="text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 disabled:opacity-50 font-medium inline-flex items-center gap-1.5"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ locating ? (t.website_locating ?? 'កំពុងស្វែងរក...') : (t.website_use_current_location ?? 'ប្រើទីតាំងបច្ចុប្បន្ន') }}
          </button>
          <p v-if="locateError" class="text-red-500 dark:text-red-400 text-xs mt-1.5">{{ locateError }}</p>
        </div>

        <div>
          <label class="block text-sm text-slate-600 dark:text-slate-300 mb-1.5">{{ t.website_virtual_tour_url }}</label>
          <input v-model="form.virtual_tour_url" type="url" placeholder="https://..." :class="fieldClass" />
          <p v-if="form.errors.virtual_tour_url" class="text-red-500 dark:text-red-400 text-xs mt-1.5">{{ form.errors.virtual_tour_url }}</p>
        </div>

        <div>
          <p class="text-sm text-slate-600 dark:text-slate-300 mb-2">{{ t.website_social_links }}</p>
          <div class="space-y-2">
            <input v-model="form.social_links.facebook" type="url" :placeholder="t.website_facebook" :class="[fieldClass, 'text-sm']" />
            <input v-model="form.social_links.instagram" type="url" :placeholder="t.website_instagram" :class="[fieldClass, 'text-sm']" />
            <input v-model="form.social_links.tiktok" type="url" :placeholder="t.website_tiktok" :class="[fieldClass, 'text-sm']" />
            <input v-model="form.social_links.telegram" type="url" :placeholder="t.website_telegram" :class="[fieldClass, 'text-sm']" />
            <input v-model="form.social_links.youtube" type="url" :placeholder="t.website_youtube ?? 'YouTube URL'" :class="[fieldClass, 'text-sm']" />
            <input v-model="form.social_links.whatsapp" type="url" :placeholder="t.website_whatsapp ?? 'WhatsApp link (wa.me/...)'" :class="[fieldClass, 'text-sm']" />
            <input v-model="form.social_links.twitter" type="url" :placeholder="t.website_twitter ?? 'Twitter / X URL'" :class="[fieldClass, 'text-sm']" />
            <input v-model="form.social_links.linkedin" type="url" :placeholder="t.website_linkedin ?? 'LinkedIn URL'" :class="[fieldClass, 'text-sm']" />
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:hover:bg-emerald-500 text-slate-950 font-medium rounded-lg py-2.5 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-[1.02] active:scale-[0.98]"
        >
          {{ form.processing ? t.website_saving : t.website_save }}
        </button>
      </form>

      <!-- RIGHT COLUMN: Images -->
      <div class="space-y-6">
        <div
          v-for="section in [
            { key: 'logo', images: logoImages, title: t.website_logo, aspect: 'aspect-square object-contain bg-slate-100 dark:bg-slate-800', selectable: true },
            { key: 'hero_banner', images: heroImages, title: t.website_hero_banner, aspect: 'aspect-video object-cover', selectable: true },
            { key: 'gallery', images: galleryImages, title: t.website_gallery_images, aspect: 'aspect-square object-cover', selectable: false },
          ]"
          :key="section.key"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 transition-colors duration-300 hover:border-slate-300 dark:hover:border-slate-700"
        >
          <p class="text-sm font-medium text-slate-900 dark:text-white mb-4">{{ section.title }}</p>

          <label
            v-if="section.key === 'hero_banner'"
            class="flex items-center justify-between gap-3 mb-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 cursor-pointer"
          >
            <span class="text-sm text-slate-600 dark:text-slate-300">
              បង្ហាញ Banner ចៃដន្យ (Random)
              <span class="block text-[11px] text-slate-400 mt-0.5">
                ជ្រើសរើសរូបភាព Hero ដោយចៃដន្យរាល់ពេលចូល Home page ជំនួសរូបតែមួយ
              </span>
            </span>
            <input
              v-model="form.random_hero_banner"
              type="checkbox"
              class="w-5 h-5 rounded accent-emerald-500 cursor-pointer shrink-0"
            />
          </label>
          <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-4">
            <div
              v-for="img in section.images"
              :key="img.id"
              class="relative group"
            >
              <div class="relative">
                <video
                  v-if="img.media_kind === 'video'"
                  :src="img.video_url"
                  muted
                  preload="metadata"
                  :class="[section.aspect, 'rounded-lg border-2 border-transparent w-full transition-all duration-200']"
                ></video>
                <img
                  v-else
                  :src="img.image_url"
                  :class="[section.aspect, 'rounded-lg border-2 transition-all duration-200']"
                  :style="section.selectable
                    ? { borderColor: (section.key === 'logo' ? form.logo_image_id : form.hero_banner_image_id) === img.id ? '#10b981' : undefined }
                    : {}"
                />
                <!-- Video badge + optional category tag, gallery-only -->
                <span
                  v-if="img.media_kind === 'video'"
                  class="absolute top-1.5 left-1.5 inline-flex items-center gap-1 text-[10px] font-semibold text-white bg-slate-950/70 backdrop-blur px-1.5 py-0.5 rounded"
                >
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                  {{ t.website_video ?? 'Video' }}
                </span>
                <span
                  v-if="img.category"
                  class="absolute top-1.5 right-1.5 text-[10px] font-medium text-emerald-300 bg-emerald-500/20 backdrop-blur px-1.5 py-0.5 rounded"
                >
                  {{ img.category }}
                </span>
              </div>
              <p v-if="img.caption" class="text-[11px] mt-1 truncate text-slate-500 dark:text-slate-400" :title="img.caption">{{ img.caption }}</p>
              <button
                v-if="section.selectable"
                type="button"
                @click="section.key === 'logo' ? (form.logo_image_id = img.id) : (form.hero_banner_image_id = img.id)"
                class="mt-1 w-full text-xs rounded py-1 transition-colors duration-150"
                :class="(section.key === 'logo' ? form.logo_image_id : form.hero_banner_image_id) === img.id
                  ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
              >
                {{ (section.key === 'logo' ? form.logo_image_id : form.hero_banner_image_id) === img.id ? t.website_active : t.website_select_active }}
              </button>
              <button
                type="button"
                @click="deleteImage(img.id)"
                class="mt-1 w-full text-xs text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 transition-colors duration-150"
              >{{ t.website_delete_image }}</button>
            </div>
          </div>

          <!-- Gallery-only: media type toggle + category/caption fields -->
          <div v-if="section.key === 'gallery'" class="space-y-2.5 mb-3">
            <div class="inline-flex rounded-lg border border-slate-300 dark:border-slate-700 overflow-hidden text-xs">
              <button
                type="button"
                @click="setGalleryMediaKind('image')"
                class="px-3 py-1.5 font-medium transition-colors"
                :class="uploadForms.gallery.media_kind === 'image'
                  ? 'bg-emerald-500 text-slate-950'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
              >
                🖼️ {{ t.website_image ?? 'រូបភាព' }}
              </button>
              <button
                type="button"
                @click="setGalleryMediaKind('video')"
                class="px-3 py-1.5 font-medium transition-colors"
                :class="uploadForms.gallery.media_kind === 'video'
                  ? 'bg-emerald-500 text-slate-950'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
              >
                🎬 {{ t.website_video ?? 'វីដេអូ' }}
              </button>
            </div>

            <input
              v-model="uploadForms.gallery.category"
              type="text"
              :placeholder="t.website_gallery_category ?? 'ប្រភេទ (equipment, classes, events...) — ស្រេចចិត្ត'"
              :class="[fieldClass, 'text-sm py-1.5']"
            />
            <p v-if="uploadForms.gallery.errors.category" class="text-red-500 dark:text-red-400 text-xs">{{ uploadForms.gallery.errors.category }}</p>

            <input
              v-model="uploadForms.gallery.caption"
              type="text"
              :placeholder="t.website_gallery_caption ?? 'ចំណារពន្យល់ខ្លី — ស្រេចចិត្ត'"
              :class="[fieldClass, 'text-sm py-1.5']"
            />
            <p v-if="uploadForms.gallery.errors.caption" class="text-red-500 dark:text-red-400 text-xs">{{ uploadForms.gallery.errors.caption }}</p>

            <p v-if="uploadForms.gallery.errors.video" class="text-red-500 dark:text-red-400 text-xs">{{ uploadForms.gallery.errors.video }}</p>
            <p v-if="uploadForms.gallery.errors.image" class="text-red-500 dark:text-red-400 text-xs">{{ uploadForms.gallery.errors.image }}</p>
            <p v-if="uploadForms.gallery.errors.images" class="text-red-500 dark:text-red-400 text-xs">{{ uploadForms.gallery.errors.images }}</p>
          </div>

          <div class="flex items-center gap-2">
            <label class="cursor-pointer text-xs bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded px-3 py-1.5 truncate max-w-[220px] transition-colors duration-150">
              {{ section.key === 'gallery' ? galleryFileLabel() : (uploadForms[section.key].image ? uploadForms[section.key].image.name : t.website_choose_file) }}
              <input
                type="file"
                :multiple="section.key === 'gallery' && uploadForms.gallery.media_kind === 'image'"
                :accept="section.key === 'gallery' && uploadForms.gallery.media_kind === 'video' ? 'video/mp4,video/quicktime,video/webm' : 'image/*'"
                class="hidden"
                @change="onFileChange(section.key, $event)"
              />
            </label>
            <button
              type="button"
              @click="upload(section.key)"
              :disabled="uploadForms[section.key].processing || (section.key === 'gallery'
                ? (uploadForms.gallery.media_kind === 'video' ? !uploadForms.gallery.video : uploadForms.gallery.images.length === 0)
                : !uploadForms[section.key].image)"
              class="text-xs bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 text-slate-950 font-medium rounded px-3 py-1.5 transition-all duration-200 hover:scale-105 active:scale-95"
            >
              {{ uploadForms[section.key].processing ? t.website_uploading : t.website_upload }}
            </button>
          </div>
          <p v-if="section.key === 'gallery' && uploadForms.gallery.media_kind === 'image'" class="text-[11px] text-slate-400 mt-1.5">
            {{ t.website_gallery_multi_hint ?? 'អាចជ្រើសរើសរូបភាពច្រើនក្នុងម្តងបាន (ជាប់គ្នា ២០ រូបអតិបរមា)' }}
          </p>
        </div>
      </div>
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