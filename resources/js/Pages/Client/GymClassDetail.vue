<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ClientLayout from '../../Layouts/ClientLayout.vue'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'
import { useCart } from '../../composables/useCart'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  tenant: Object,
  settings: Object,
  gymClass: Object,
  bookedClassIds: { type: Array, default: () => [] },
  isLoggedInMember: { type: Boolean, default: false },
})

const { theme } = useTheme()
const { t } = useLang()
const cart = useCart(props.tenant?.slug)

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

function todayCode() {
  return ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][new Date().getDay()]
}

function isLiveNow(cls) {
  if (cls.schedule_day !== todayCode()) return false
  const now = new Date()
  const nowMin = now.getHours() * 60 + now.getMinutes()
  const [sh, sm] = cls.start_time.split(':').map(Number)
  const [eh, em] = cls.end_time.split(':').map(Number)
  return nowMin >= sh * 60 + sm && nowMin < eh * 60 + em
}

const spotsBadge = computed(() => {
  const cls = props.gymClass
  const left = cls.spots_left ?? (cls.capacity - (cls.bookings_count ?? 0))
  if (left <= 0) return { text: t.value?.classes_full ?? 'ពេញ', class: 'bg-red-500/15 text-red-400 ring-1 ring-red-500/20' }
  if (left <= 3) return { text: `${t.value?.classes_spots_left ?? 'នៅសល់'} ${left}`, class: 'bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/20' }
  return { text: `${t.value?.classes_spots_left ?? 'នៅសល់'} ${left}`, class: 'bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/20' }
})

const isPaid = computed(() => Number(props.gymClass.price ?? 0) > 0)
const isBooked = computed(() => props.bookedClassIds.includes(props.gymClass.id))
const isFull = computed(() => spotsBadge.value.text === (t.value?.classes_full ?? 'ពេញ'))

const booking = ref(false)

function bookFree() {
  booking.value = true
  router.post(
    `/gym/${props.tenant.slug}/account/classes/${props.gymClass.id}/book`,
    {},
    { preserveScroll: true, onFinish: () => { booking.value = false } }
  )
}
function unbookFree() {
  booking.value = true
  router.delete(
    `/gym/${props.tenant.slug}/account/classes/${props.gymClass.id}/unbook`,
    { preserveScroll: true, onFinish: () => { booking.value = false } }
  )
}
function addToCart() {
  cart.add({ id: props.gymClass.id, name: props.gymClass.name, price: props.gymClass.price })
  cart.open()
}
</script>

<template>
  <!-- ===== HEADER / BREADCRUMB ===== -->
  <section :class="[theme.bgAlt, theme.border]" class="border-b transition-colors duration-300">
    <div class="max-w-4xl mx-auto px-6 py-6">
      <Link
        :href="`/gym/${tenant.slug}/classes`"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-400 hover:text-emerald-300 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ t.classes_back_to_all ?? 'ត្រឡប់ទៅកាលវិភាគទាំងអស់' }}
      </Link>
    </div>
  </section>

  <!-- ===== CLASS DETAIL ===== -->
  <section class="max-w-4xl mx-auto px-6 py-12 sm:py-16">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
      <!-- Image + description -->
      <div class="lg:col-span-3">
        <div class="relative rounded-2xl overflow-hidden aspect-video shadow-xl mb-6">
          <img
            v-if="gymClass.image_url"
            :src="gymClass.image_url"
            :alt="gymClass.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full bg-gradient-to-br from-emerald-800 via-slate-900 to-slate-950"></div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

          <span
            v-if="isLiveNow(gymClass)"
            class="absolute top-4 left-4 inline-flex items-center gap-1 text-xs font-semibold text-white bg-emerald-500/90 backdrop-blur px-3 py-1 rounded-full"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-white animate-[pulse_1.5s_ease-in-out_infinite]"></span>
            {{ t.classes_live_now ?? 'ផ្សាយផ្ទាល់' }}
          </span>
          <span
            v-if="isPaid"
            class="absolute top-4 right-4 text-sm font-bold text-slate-950 bg-amber-400 px-3 py-1 rounded-full"
          >
            ${{ Number(gymClass.price).toFixed(2) }}
          </span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold mb-3">{{ gymClass.name }}</h1>
        <p v-if="gymClass.description" class="leading-relaxed text-base" :class="theme.textMuted">
          {{ gymClass.description }}
        </p>
        <p v-else class="text-base italic" :class="theme.textMuted">
          {{ t.classes_no_description ?? 'មិនទាន់មានការពិពណ៌នាបន្ថែមទេ' }}
        </p>
      </div>

      <!-- Info card + booking -->
      <div class="lg:col-span-2">
        <div :class="[theme.card, theme.border]" class="rounded-2xl border p-6 space-y-5 lg:sticky lg:top-6">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium" :class="theme.textMuted">{{ t.classes_schedule ?? 'កាលវិភាគ' }}</span>
            <span class="font-semibold">{{ dayLabels[gymClass.schedule_day] }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium" :class="theme.textMuted">{{ t.classes_time ?? 'ម៉ោង' }}</span>
            <span class="font-semibold">{{ formatTime(gymClass.start_time) }} – {{ formatTime(gymClass.end_time) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium" :class="theme.textMuted">{{ t.classes_availability ?? 'កន្លែងទំនេរ' }}</span>
            <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="spotsBadge.class">{{ spotsBadge.text }}</span>
          </div>

          <div v-if="gymClass.trainer?.name" class="flex items-center gap-3 pt-4 border-t" :class="theme.border">
            <div class="w-11 h-11 rounded-xl overflow-hidden shrink-0" :class="theme.card">
              <img
                v-if="gymClass.trainer.photo_url"
                :src="gymClass.trainer.photo_url"
                :alt="gymClass.trainer.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-emerald-400 font-bold">
                {{ gymClass.trainer.name?.charAt(0) }}
              </div>
            </div>
            <div>
              <p class="text-sm font-semibold">{{ gymClass.trainer.name }}</p>
              <p v-if="gymClass.trainer.specialty" class="text-xs uppercase tracking-wide text-emerald-400">{{ gymClass.trainer.specialty }}</p>
            </div>
          </div>

          <div class="pt-4 border-t" :class="theme.border">
            <template v-if="!isLoggedInMember">
              <a :href="`/gym/${tenant.slug}/register`" class="block text-center text-sm font-semibold px-4 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors">
                {{ t.classes_login_to_book ?? 'ចូលដើម្បីកក់' }}
              </a>
            </template>
            <template v-else-if="isBooked">
              <button
                @click="unbookFree"
                :disabled="booking"
                class="w-full text-sm font-semibold px-4 py-3 rounded-xl bg-slate-500/10 text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-colors disabled:opacity-40"
              >
                {{ booking ? (t.classes_unbooking ?? 'កំពុងលុប...') : (t.classes_already_booked ?? 'បានកក់ ✓ (ចុចដើម្បីលុប)') }}
              </button>
            </template>
            <template v-else-if="!isPaid">
              <button
                @click="bookFree"
                :disabled="booking || isFull"
                class="w-full text-sm font-semibold px-4 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 text-slate-950 transition-colors"
              >
                {{ booking ? (t.classes_booking ?? 'កំពុងកក់...') : (t.classes_book_now ?? 'កក់ឥឡូវនេះ') }}
              </button>
            </template>
            <template v-else-if="cart.has(gymClass.id)">
              <button @click="cart.open()" class="w-full text-sm font-semibold px-4 py-3 rounded-xl bg-amber-500/15 text-amber-400">
                {{ t.classes_in_cart ?? '✓ ក្នុងកន្ត្រក' }}
              </button>
            </template>
            <template v-else>
              <button
                @click="addToCart"
                class="w-full text-sm font-semibold px-4 py-3 rounded-xl border border-amber-400/40 text-amber-400 hover:bg-amber-500/10 transition-colors"
              >
                {{ t.classes_add_to_cart ?? '+ បន្ថែមកន្ត្រក' }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>