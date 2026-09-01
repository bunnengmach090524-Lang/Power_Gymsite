<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ClientLayout from '../../Layouts/ClientLayout.vue'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'
import { useCart } from '../../composables/useCart'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  tenant: Object,
  settings: Object,
  classes: Array,
  bookedClassIds: { type: Array, default: () => [] },
  isLoggedInMember: { type: Boolean, default: false },
})

const { theme } = useTheme()
const { t } = useLang()
const cart = useCart(props.tenant?.slug)

const dayOrder = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
const dayLabels = {
  mon: t.value?.day_mon ?? 'ចន្ទ',
  tue: t.value?.day_tue ?? 'អង្គារ',
  wed: t.value?.day_wed ?? 'ពុធ',
  thu: t.value?.day_thu ?? 'ព្រហស្បតិ៍',
  fri: t.value?.day_fri ?? 'សុក្រ',
  sat: t.value?.day_sat ?? 'សៅរ៍',
  sun: t.value?.day_sun ?? 'អាទិត្យ',
}
const dayLabelsShort = {
  mon: 'ច', tue: 'អ', wed: 'ព', thu: 'ព្រ', fri: 'សុ', sat: 'ស', sun: 'អា',
}

function todayCode() {
  return ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][new Date().getDay()]
}

const activeDay = ref(todayCode())

const classesByDay = computed(() => {
  const grouped = Object.fromEntries(dayOrder.map(d => [d, []]))
  for (const cls of props.classes ?? []) {
    grouped[cls.schedule_day]?.push(cls)
  }
  return grouped
})

const visibleClasses = computed(() => classesByDay.value[activeDay.value] ?? [])

const liveCountToday = computed(() => {
  if (activeDay.value !== todayCode()) return 0
  return visibleClasses.value.filter(isLiveNow).length
})

function formatTime(time) {
  return time ? time.slice(0, 5) : ''
}

function isLiveNow(cls) {
  if (activeDay.value !== todayCode()) return false
  const now = new Date()
  const nowMin = now.getHours() * 60 + now.getMinutes()
  const [sh, sm] = cls.start_time.split(':').map(Number)
  const [eh, em] = cls.end_time.split(':').map(Number)
  return nowMin >= sh * 60 + sm && nowMin < eh * 60 + em
}

function spotsBadge(cls) {
  const left = cls.spots_left ?? (cls.capacity - (cls.bookings_count ?? 0))
  if (left <= 0) return { text: t.value?.classes_full ?? 'ពេញ', class: 'bg-red-500/15 text-red-400 ring-1 ring-red-500/20' }
  if (left <= 3) return { text: `${t.value?.classes_spots_left ?? 'នៅសល់'} ${left}`, class: 'bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/20' }
  return { text: `${t.value?.classes_spots_left ?? 'នៅសល់'} ${left}`, class: 'bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/20' }
}

function isPaid(cls) {
  return Number(cls.price ?? 0) > 0
}

function isBooked(cls) {
  return props.bookedClassIds.includes(cls.id)
}

const bookingId = ref(null)

function bookFree(cls) {
  bookingId.value = cls.id
  router.post(
    `/gym/${props.tenant.slug}/account/classes/${cls.id}/book`,
    {},
    { preserveScroll: true, onFinish: () => { bookingId.value = null } }
  )
}
function unbookFree(cls) {
  bookingId.value = cls.id
  router.delete(
    `/gym/${props.tenant.slug}/account/classes/${cls.id}/unbook`,
    { preserveScroll: true, onFinish: () => { bookingId.value = null } }
  )
}

function addToCart(cls) {
  cart.add({
    id: cls.id,
    name: cls.name,
    price: cls.price,
    image_url: cls.image_url,
    schedule_day: cls.schedule_day,
    start_time: cls.start_time,
    end_time: cls.end_time,
    trainer_name: cls.trainer?.name,
  })
  cart.open()
}
</script>

<template>
  <!-- ===== PAGE HEADER ===== -->
  <section :class="[theme.bgAlt, theme.border]" class="relative border-b overflow-hidden transition-colors duration-300">
    <!-- decorative glow -->
    <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 w-[36rem] h-[36rem] rounded-full bg-emerald-500/10 blur-3xl"></div>

    <div class="relative max-w-5xl mx-auto px-6 py-16 sm:py-24 text-center" v-reveal>
      <span class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase text-emerald-400 bg-emerald-500/10 ring-1 ring-emerald-500/20 px-3 py-1 rounded-full mb-4">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
        {{ t.classes_badge ?? 'កាលវិភាគប្រចាំសប្តាហ៍' }}
      </span>

      <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-3">
        {{ t.classes_title ?? 'កាលវិភាគ Class' }}
      </h1>
      <p class="max-w-lg mx-auto text-base sm:text-lg" :class="theme.textMuted">
        {{ t.classes_subtitle ?? 'ជ្រើសរើសថ្ងៃ ដើម្បីមើល Class ដែលកំពុងបើក' }}
      </p>

      <!-- quick stats -->
      <div class="flex items-center justify-center gap-6 sm:gap-10 mt-10">
        <div>
          <p class="text-2xl sm:text-3xl font-extrabold text-emerald-400">{{ visibleClasses.length }}</p>
          <p class="text-xs mt-1" :class="theme.textMuted">{{ t.classes_stat_total ?? 'Class ថ្ងៃនេះ' }}</p>
        </div>
        <div class="w-px h-10 bg-current opacity-10"></div>
        <div>
          <p class="text-2xl sm:text-3xl font-extrabold flex items-center justify-center gap-1.5" :class="liveCountToday ? 'text-emerald-400' : ''">
            <span v-if="liveCountToday" class="w-2 h-2 rounded-full bg-emerald-400 animate-[pulse_1.5s_ease-in-out_infinite]"></span>
            {{ liveCountToday }}
          </p>
          <p class="text-xs mt-1" :class="theme.textMuted">{{ t.classes_stat_live ?? 'កំពុងផ្សាយផ្ទាល់' }}</p>
        </div>
        <div class="w-px h-10 bg-current opacity-10"></div>
        <div>
          <p class="text-2xl sm:text-3xl font-extrabold">{{ (classes ?? []).length }}</p>
          <p class="text-xs mt-1" :class="theme.textMuted">{{ t.classes_stat_week ?? 'សរុបសប្តាហ៍' }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== SCHEDULE ===== -->
  <section class="max-w-5xl mx-auto px-6 py-16 sm:py-20">
    <!-- Day tabs -->
    <div class="sticky top-0 z-10 -mx-6 px-6 py-3 mb-10 backdrop-blur-sm" :class="theme.bgAlt">
      <div class="flex justify-center gap-2 overflow-x-auto pb-1" v-reveal>
        <button
          v-for="day in dayOrder"
          :key="day"
          type="button"
          @click="activeDay = day"
          class="shrink-0 relative px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200"
          :class="activeDay === day
            ? 'bg-emerald-500 text-slate-950 shadow-lg shadow-emerald-500/25 scale-105'
            : [theme.card, theme.textMuted, 'border hover:border-emerald-500/40 hover:text-emerald-400']"
        >
          <span class="hidden sm:inline">{{ dayLabels[day] }}</span>
          <span class="sm:hidden">{{ dayLabelsShort[day] }}</span>
          <span
            v-if="day === todayCode()"
            class="absolute -top-1 -right-1 w-2 h-2 rounded-full"
            :class="activeDay === day ? 'bg-slate-950' : 'bg-emerald-400'"
          ></span>
        </button>
      </div>
    </div>

    <!-- Class grid for the selected day -->
    <div v-if="visibleClasses.length" class="grid sm:grid-cols-2 gap-5">
      <div
        v-for="(cls, i) in visibleClasses"
        :key="cls.id"
        v-reveal="{ delay: i * 60 }"
        :class="[theme.card, 'group border rounded-2xl overflow-hidden transition-all duration-300 hover:border-emerald-500/50 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/5']"
      >
        <!-- image banner -->
        <div class="relative h-36 overflow-hidden">
          <img
            v-if="cls.image_url"
            :src="cls.image_url"
            :alt="cls.name"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
          />
          <div v-else class="w-full h-full bg-gradient-to-br from-emerald-800 via-slate-900 to-slate-950 flex items-center justify-center">
            <svg class="w-10 h-10 text-emerald-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 12a2 2 0 100 4h1a2 2 0 100-4m-1 0a2 2 0 110-4h1a2 2 0 110 4m14 0a2 2 0 100 4h-1a2 2 0 100-4m1 0a2 2 0 110-4h-1a2 2 0 110 4" />
            </svg>
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>

          <!-- time pill -->
          <div class="absolute bottom-3 left-3 flex items-center gap-1.5 text-white">
            <span class="text-sm font-bold">{{ formatTime(cls.start_time) }}</span>
            <span class="opacity-60 text-xs">–</span>
            <span class="text-xs opacity-80">{{ formatTime(cls.end_time) }}</span>
          </div>

          <!-- top-right badges -->
          <div class="absolute top-3 right-3 flex flex-col items-end gap-1.5">
            <span
              v-if="isLiveNow(cls)"
              class="inline-flex items-center gap-1 text-[11px] font-semibold text-white bg-emerald-500/90 backdrop-blur px-2.5 py-1 rounded-full"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-white animate-[pulse_1.5s_ease-in-out_infinite]"></span>
              {{ t.classes_live_now ?? 'ផ្សាយផ្ទាល់' }}
            </span>
            <span
              v-if="isPaid(cls)"
              class="text-[11px] font-bold text-slate-950 bg-amber-400 px-2.5 py-1 rounded-full"
            >
              ${{ Number(cls.price).toFixed(2) }}
            </span>
          </div>
        </div>

        <!-- content -->
        <div class="p-5">
          <div class="flex items-start justify-between gap-3 mb-1.5">
            <h3 class="font-bold text-lg leading-snug">{{ cls.name }}</h3>
            <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full" :class="spotsBadge(cls).class">
              {{ spotsBadge(cls).text }}
            </span>
          </div>

          <p v-if="cls.description" class="text-sm mb-1 leading-relaxed line-clamp-2" :class="theme.textMuted">{{ cls.description }}</p>
          <p v-if="cls.trainer?.name" class="text-sm font-medium flex items-center gap-1.5" :class="theme.textMuted">
            <svg class="w-3.5 h-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ cls.trainer.name }}
          </p>

          <div class="mt-4 pt-4 border-t" :class="theme.border">
            <template v-if="!isLoggedInMember">
              <a :href="`/gym/${tenant.slug}/register`" class="block text-center text-sm font-semibold px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors">
                {{ t.classes_login_to_book ?? 'ចូលដើម្បីកក់' }}
              </a>
            </template>
            <template v-else-if="isBooked(cls)">
              <button
                @click="unbookFree(cls)"
                :disabled="bookingId === cls.id"
                class="w-full text-sm font-semibold px-4 py-2.5 rounded-xl bg-slate-500/10 text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-colors disabled:opacity-40"
              >
                {{ bookingId === cls.id ? (t.classes_unbooking ?? 'កំពុងលុប...') : (t.classes_already_booked ?? 'បានកក់ ✓ (ចុចដើម្បីលុប)') }}
              </button>
            </template>
            <template v-else-if="!isPaid(cls)">
              <button
                @click="bookFree(cls)"
                :disabled="bookingId === cls.id || spotsBadge(cls).text === (t.classes_full ?? 'ពេញ')"
                class="w-full text-sm font-semibold px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 text-slate-950 transition-colors"
              >
                {{ bookingId === cls.id ? (t.classes_booking ?? 'កំពុងកក់...') : (t.classes_book_now ?? 'កក់ឥឡូវនេះ') }}
              </button>
            </template>
            <template v-else-if="cart.has(cls.id)">
              <button @click="cart.open()" class="w-full text-sm font-semibold px-4 py-2.5 rounded-xl bg-amber-500/15 text-amber-400">
                {{ t.classes_in_cart ?? '✓ ក្នុងកន្ត្រក' }}
              </button>
            </template>
            <template v-else>
              <button
                @click="addToCart(cls)"
                class="w-full text-sm font-semibold px-4 py-2.5 rounded-xl border border-amber-400/40 text-amber-400 hover:bg-amber-500/10 transition-colors"
              >
                {{ t.classes_add_to_cart ?? '+ បន្ថែមកន្ត្រក' }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- empty state -->
    <div v-else class="text-center py-24" v-reveal>
      <div class="w-16 h-16 mx-auto mb-4 rounded-2xl flex items-center justify-center" :class="theme.card">
        <svg class="w-7 h-7 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <p class="text-lg font-semibold" :class="theme.textMuted">
        {{ t.classes_empty ?? 'គ្មាន Class សម្រាប់ថ្ងៃនេះទេ' }}
      </p>
      <p class="text-sm mt-1" :class="theme.textMuted">{{ t.classes_empty_hint ?? 'សូមសាកល្បងជ្រើសរើសថ្ងៃផ្សេង' }}</p>
    </div>
  </section>
</template>