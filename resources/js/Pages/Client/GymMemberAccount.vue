<script setup>
import { computed, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'
import ClientLayout from '../../Layouts/ClientLayout.vue'

const props = defineProps({
  tenant: Object,
  settings: Object,
  member: Object,
  activeSubscription: Object,
  recentCheckIns: Array,
  upcomingBookings: Array,
  availableClasses: { type: Array, default: () => [] },
})

const { theme } = useTheme()
const { t } = useLang()

const qrUrl = computed(() => `/gym/${props.tenant.slug}/account/qr`)
const homeHref = computed(() => `/gym/${props.tenant.slug}`)

const daysLeft = computed(() => {
  if (!props.activeSubscription) return null
  const end = new Date(props.activeSubscription.end_date)
  const today = new Date()
  return Math.max(0, Math.ceil((end - today) / (1000 * 60 * 60 * 24)))
})

const progressPct = computed(() => {
  if (daysLeft.value === null) return 0
  return Math.min(100, (daysLeft.value / 30) * 100)
})

const telegramConnected = computed(() => !!props.member.telegram_chat_id)
const telegramBotUsername = computed(() => props.settings?.telegram_bot_username || 'YourGymBot')
const telegramConnectUrl = computed(
  () => `https://t.me/${telegramBotUsername.value}?start=member_${props.member.id}`
)

function formatCheckIn(dateStr) {
  return new Date(dateStr).toLocaleString('km-KH', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}
function formatUpcoming(iso) {
  return new Date(iso).toLocaleString('km-KH', { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

// ===== Edit Profile (photo + name/phone/email) =====
const profileForm = useForm({
  name: props.member.name ?? '',
  phone: props.member.phone ?? '',
  email: props.member.email ?? '',
  photo: null,
})

const photoPreview = ref(props.member.photo_url ?? null)
const fileInputRef = ref(null)
// ===== Class self-enrollment =====
const bookingLoadingId = ref(null)

function onPhotoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  profileForm.photo = file
  photoPreview.value = URL.createObjectURL(file)
}

function triggerFilePicker() {
  fileInputRef.value?.click()
}

function toggleBooking(cls) {
  bookingLoadingId.value = cls.id
  const url = `/gym/${props.tenant.slug}/account/classes/${cls.id}/${cls.is_booked ? 'unbook' : 'book'}`
  const method = cls.is_booked ? 'delete' : 'post'

  router[method](url, {}, {
    preserveScroll: true,
    onFinish: () => { bookingLoadingId.value = null },
  })
}
function submitProfile() {
  profileForm.post(`/gym/${props.tenant.slug}/account`, {
    forceFormData: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <ClientLayout :tenant="tenant" :settings="settings">
    <div class="max-w-4xl mx-auto px-4 sm:px-5 py-10 space-y-5">

      <!-- Header -->
      <div class="flex items-center gap-4 group">
        <img
          v-if="member.photo_url"
          :src="member.photo_url"
          class="w-16 h-16 rounded-full object-cover ring-2 ring-emerald-400/40 transition-all duration-300 group-hover:ring-emerald-400/80 group-hover:scale-105"
        />
        <div
          v-else
          :class="theme.card"
          class="w-16 h-16 rounded-full flex items-center justify-center font-bold text-xl ring-2 ring-emerald-400/40 transition-all duration-300 group-hover:ring-emerald-400/80 group-hover:scale-105"
        >
          {{ member.name?.charAt(0)?.toUpperCase() }}
        </div>
        <div>
          <h1 class="text-xl font-bold">{{ member.name }}</h1>
          <p class="text-sm" :class="theme.textMuted">{{ member.phone || member.email }}</p>
        </div>
      </div>

      <!-- ===== EDIT PROFILE CARD ===== -->
      <div
        :class="[theme.card, theme.border]"
        class="rounded-2xl p-6 border transition-all duration-300 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10"
      >
        <h2 class="text-sm font-semibold mb-1" :class="theme.textMuted">{{ t.account_edit_profile_title ?? 'កែប្រែព័ត៌មានផ្ទាល់ខ្លួន' }}</h2>
        <p class="text-xs mb-5" :class="theme.textMuted">{{ t.account_edit_profile_desc ?? 'ព័ត៌មាននេះនឹងបង្ហាញនៅលើគណនីរបស់អ្នក' }}</p>

        <form @submit.prevent="submitProfile" class="space-y-5">
          <!-- Avatar upload -->
          <div class="flex items-center gap-4">
            <img
              v-if="photoPreview"
              :src="photoPreview"
              class="w-16 h-16 rounded-full object-cover ring-2 ring-emerald-400/30"
            />
            <div
              v-else
              :class="theme.card"
              class="w-16 h-16 rounded-full flex items-center justify-center font-bold text-lg ring-2 ring-emerald-400/30"
            >
              {{ member.name?.charAt(0)?.toUpperCase() }}
            </div>
            <div>
              <button
                type="button"
                @click="triggerFilePicker"
                :class="theme.border"
                class="inline-flex items-center gap-2 text-xs font-medium px-4 py-2 rounded-full border transition-all duration-200 hover:border-emerald-400 hover:text-emerald-400"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                {{ t.account_upload_photo ?? 'ប្តូររូបភាព' }}
              </button>
              <input ref="fileInputRef" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPhotoChange" />
              <p class="text-[11px] mt-1.5" :class="theme.textMuted">{{ t.account_photo_hint ?? 'JPG, PNG ឬ WEBP។ ទំហំអតិបរមា 3MB' }}</p>
            </div>
          </div>

          <!-- Name + Phone -->
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium mb-1.5" :class="theme.textMuted">{{ t.account_field_name ?? 'ឈ្មោះ' }}</label>
              <input
                v-model="profileForm.name"
                type="text"
                :class="theme.input"
                class="w-full rounded-lg px-3.5 py-2.5 border focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
              />
              <p v-if="profileForm.errors.name" class="text-xs text-red-400 mt-1">{{ profileForm.errors.name }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium mb-1.5" :class="theme.textMuted">{{ t.account_field_phone ?? 'លេខទូរស័ព្ទ' }}</label>
              <input
                v-model="profileForm.phone"
                type="tel"
                :class="theme.input"
                class="w-full rounded-lg px-3.5 py-2.5 border focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
              />
              <p v-if="profileForm.errors.phone" class="text-xs text-red-400 mt-1">{{ profileForm.errors.phone }}</p>
            </div>
          </div>

          <!-- Email (read-only note like reference) -->
          <div>
            <label class="block text-xs font-medium mb-1.5" :class="theme.textMuted">{{ t.account_field_email ?? 'អ៊ីមែល' }}</label>
            <input
              v-model="profileForm.email"
              type="email"
              disabled
              :class="theme.input"
              class="w-full rounded-lg px-3.5 py-2.5 border opacity-60 cursor-not-allowed"
            />
            <p class="text-[11px] mt-1" :class="theme.textMuted">{{ t.account_email_locked_hint ?? 'អ៊ីមែលមិនអាចផ្លាស់ប្តូរដោយខ្លួនឯងបានទេ សូមទាក់ទង admin' }}</p>
          </div>

          <button
            type="submit"
            :disabled="profileForm.processing"
            class="text-xs font-medium px-5 py-2.5 rounded-full bg-emerald-500 text-slate-950 transition-all duration-200 hover:bg-emerald-400 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:hover:scale-100"
          >
            {{ profileForm.processing ? (t.account_saving ?? 'កំពុងរក្សាទុក...') : (t.account_save_changes ?? 'រក្សាទុកការផ្លាស់ប្តូរ') }}
          </button>
          <p v-if="profileForm.recentlySuccessful" class="text-emerald-400 text-xs">{{ t.account_saved_success ?? 'បានរក្សាទុកជោគជ័យ!' }}</p>
        </form>
      </div>

      <!-- ===== Row 1: Subscription + Class Enrollment ===== -->
      <div class="grid md:grid-cols-2 gap-5">
        <!-- Subscription card -->
        <div
          :class="[theme.card, theme.border]"
          class="rounded-2xl p-6 border transition-all duration-300 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10 hover:-translate-y-0.5"
        >
          <h2 class="text-sm font-semibold mb-3" :class="theme.textMuted">{{ t.account_membership_title ?? 'គម្រោងសមាជិកភាព' }}</h2>
          <template v-if="activeSubscription">
            <p class="text-lg font-bold text-emerald-400">{{ activeSubscription.plan?.name }}</p>
            <p class="text-sm mt-1" :class="theme.textMuted">
              {{ t.account_membership_expires ?? 'ផុតកំណត់នៅ' }} {{ new Date(activeSubscription.end_date).toLocaleDateString('km-KH') }}
              · {{ t.account_membership_days_left ?? 'នៅសល់' }} {{ daysLeft }} {{ t.account_days ?? 'ថ្ងៃ' }}
            </p>
            <div class="mt-3 h-1.5 rounded-full bg-gray-800 overflow-hidden">
              <div
                class="h-full bg-emerald-400 transition-all duration-700 ease-out"
                :style="{ width: progressPct + '%' }"
              ></div>
            </div>
          </template>
          <template v-else>
            <p class="text-sm" :class="theme.textMuted">{{ t.account_membership_none ?? 'អ្នកមិនទាន់មានគម្រោងសកម្មទេ' }}</p>
            <a
              :href="`${homeHref}#pricing`"
              class="inline-block mt-3 text-xs font-medium px-4 py-2 rounded-full bg-emerald-500 text-slate-950 transition-all duration-200 hover:bg-emerald-400 hover:scale-105 active:scale-95"
            >
              {{ t.account_membership_choose_plan ?? 'ជ្រើសរើសគម្រោង' }}
            </a>
          </template>
        </div>

        <!-- Class enrollment card -->
        <div
          :class="[theme.card, theme.border]"
          class="rounded-2xl p-6 border transition-all duration-300 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10"
        >
          <h2 class="text-sm font-semibold mb-1" :class="theme.textMuted">{{ t.account_classes_title ?? 'ចុះឈ្មោះចូលរួម Class' }}</h2>
          <p class="text-xs mb-4" :class="theme.textMuted">{{ t.account_classes_desc ?? 'ជ្រើសរើស class ដែលអ្នកចង់ចូលរួម' }}</p>

          <div v-if="availableClasses.length" class="space-y-2">
            <div
              v-for="cls in availableClasses"
              :key="cls.id"
              class="flex items-center justify-between gap-3 py-2.5 px-3 -mx-1 rounded-lg transition-colors duration-200"
              :class="cls.is_booked ? 'bg-emerald-500/5' : 'hover:bg-white/5'"
            >
              <div class="min-w-0">
                <p class="font-medium text-sm truncate">{{ cls.name }}</p>
                <p :class="theme.textMuted" class="text-xs">
                  {{ t['day_' + cls.schedule_day] }} · {{ cls.start_time?.slice(0,5) }} - {{ cls.end_time?.slice(0,5) }}
                  <span :class="cls.spots_left === 0 ? 'text-amber-400' : ''">
                    · {{ cls.spots_left }}/{{ cls.capacity }} {{ t.account_classes_spots ?? 'កន្លែងទំនេរ' }}
                  </span>
                </p>
              </div>
              <button
                @click="toggleBooking(cls)"
                :disabled="bookingLoadingId === cls.id"
                class="shrink-0 text-xs font-medium px-3.5 py-1.5 rounded-full transition-all duration-200 disabled:opacity-50"
                :class="cls.is_booked
                  ? 'border text-red-400 hover:bg-red-500/10 hover:border-red-400/40'
                  : 'bg-emerald-500 text-slate-950 hover:bg-emerald-400 hover:scale-105'"
              >
                {{ cls.is_booked
                  ? (t.account_classes_unbook ?? 'ចាកចេញ')
                  : (t.account_classes_book ?? 'ចុះឈ្មោះ') }}
              </button>
            </div>
          </div>
          <p v-else class="text-sm" :class="theme.textMuted">{{ t.account_classes_empty ?? 'មិនទាន់មាន class ទេ' }}</p>
        </div>
      </div>

      <!-- ===== Row 2: QR Check-in + Telegram (ជួរដេកតែមួយ) ===== -->
      <div class="grid md:grid-cols-2 gap-5">
        <!-- QR card -->
        <div
          :class="[theme.card, theme.border]"
          class="rounded-2xl p-6 border flex flex-col items-center justify-center text-center transition-all duration-300 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10 hover:-translate-y-0.5"
        >
          <h2 class="text-sm font-semibold mb-3" :class="theme.textMuted">{{ t.account_qr_title ?? 'QR Code ចូល Gym' }}</h2>
          <div class="bg-white rounded-xl p-3 transition-transform duration-300 hover:scale-105">
            <img :src="qrUrl" alt="Member QR" class="w-36 h-36" />
          </div>
          <p class="text-xs mt-2" :class="theme.textMuted">{{ t.account_qr_hint ?? 'បង្ហាញ QR នេះនៅ reception ពេលចូល Gym' }}</p>
        </div>

        <!-- Telegram card -->
        <div
          :class="[theme.card, theme.border]"
          class="rounded-2xl p-6 border flex flex-col items-center justify-center text-center gap-3 transition-all duration-300 hover:border-sky-400/40 hover:shadow-lg hover:shadow-sky-500/10 hover:-translate-y-0.5"
        >
          <div class="w-11 h-11 rounded-full bg-sky-500/10 flex items-center justify-center shrink-0 transition-transform duration-300 hover:scale-110">
            <svg viewBox="0 0 24 24" class="w-6 h-6 text-sky-400" fill="currentColor">
              <path d="M21.94 3.6a1.5 1.5 0 0 0-1.53-.26L2.7 10.4a1.4 1.4 0 0 0 .06 2.63l4.55 1.5 1.76 5.6c.2.62.98.82 1.46.37l2.55-2.4 4.4 3.24c.7.51 1.7.13 1.9-.72l3.5-15.7a1.5 1.5 0 0 0-.94-1.72Z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-sm font-semibold" :class="theme.textMuted">{{ t.account_telegram_title ?? 'ការជូនដំណឹងតាម Telegram' }}</h2>
            <p class="text-xs mt-0.5" :class="theme.textMuted">{{ t.account_telegram_desc ?? 'ទទួលការរំលឹកអំពីគម្រោងផុតកំណត់ និងការកក់ថ្នាក់' }}</p>
            <p class="text-xs mt-1">
              <span
                class="inline-flex items-center gap-1.5"
                :class="telegramConnected ? 'text-emerald-400' : theme.textMuted"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full transition-colors duration-300"
                  :class="telegramConnected ? 'bg-emerald-400 animate-pulse' : 'bg-gray-500'"
                ></span>
                {{ telegramConnected
                  ? `${t.account_telegram_connected ?? 'ភ្ជាប់រួច'} @${member.telegram_username}`
                  : (t.account_telegram_not_connected ?? 'មិនទាន់ភ្ជាប់') }}
              </span>
            </p>
          </div>

          <a
            v-if="!telegramConnected"
            :href="telegramConnectUrl"
            target="_blank"
            rel="noopener"
            class="text-xs font-medium px-4 py-2 rounded-full bg-sky-500 text-white transition-all duration-200 hover:bg-sky-400 hover:scale-105 active:scale-95 shrink-0"
          >
            {{ t.account_telegram_connect_btn ?? 'ភ្ជាប់ Telegram' }}
          </a>
          <button
            v-else
            class="text-xs font-medium px-4 py-2 rounded-full border transition-all duration-200 hover:bg-red-500/10 hover:border-red-400/40 hover:text-red-400 active:scale-95 shrink-0"
            :class="theme.border"
          >
            {{ t.account_telegram_disconnect_btn ?? 'ផ្តាច់ Telegram' }}
          </button>
        </div>
      </div>

      <!-- Upcoming bookings -->
      <div
        :class="[theme.card, theme.border]"
        class="rounded-2xl p-6 border transition-all duration-300 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10"
      >
        <h2 class="text-sm font-semibold mb-4" :class="theme.textMuted">{{ t.account_bookings_title ?? 'ការកក់ថ្នាក់ខាងមុខ' }}</h2>
        <div v-if="upcomingBookings.length" class="space-y-1">
          <div
            v-for="b in upcomingBookings"
            :key="b.booking_id"
            class="flex items-center justify-between text-sm py-2.5 px-2 -mx-2 rounded-lg border-b last:border-0 transition-colors duration-200 hover:bg-white/5"
            :class="theme.border"
          >
            <div>
              <p class="font-medium">{{ b.class_name }}</p>
              <p :class="theme.textMuted" class="text-xs">{{ t['day_' + b.schedule_day] }} · {{ b.start_time }} - {{ b.end_time }}</p>
            </div>
            <span class="text-xs text-emerald-400">{{ formatUpcoming(b.next_occurrence) }}</span>
          </div>
        </div>
        <p v-else class="text-sm" :class="theme.textMuted">{{ t.account_bookings_none ?? 'គ្មានការកក់ថ្នាក់ខាងមុខទេ' }}</p>
      </div>

      <!-- Check-in history -->
      <div
        :class="[theme.card, theme.border]"
        class="rounded-2xl p-6 border transition-all duration-300 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10"
      >
        <h2 class="text-sm font-semibold mb-4" :class="theme.textMuted">{{ t.account_checkins_title ?? 'ប្រវត្តិចូល Gym' }}</h2>
        <div v-if="recentCheckIns.length" class="space-y-1">
          <div
            v-for="c in recentCheckIns"
            :key="c.id"
            class="flex items-center gap-2 text-sm py-1.5 px-2 -mx-2 rounded-lg transition-colors duration-200 hover:bg-white/5"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            {{ formatCheckIn(c.checked_in_at) }}
          </div>
        </div>
        <p v-else class="text-sm" :class="theme.textMuted">{{ t.account_checkins_none ?? 'មិនទាន់មានប្រវត្តិចូល Gym ទេ' }}</p>
      </div>
    </div>
  </ClientLayout>
</template>