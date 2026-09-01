<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useTheme } from '../composables/useTheme'
import { useLang } from '../composables/useLang'
import { useCart } from '../composables/useCart'

const props = defineProps({
  tenant: Object,
})

const { theme } = useTheme()
const { t } = useLang()
const cart = useCart(props.tenant?.slug)

const checking = ref(false)

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

function scheduleLabel(item) {
  if (!item.schedule_day) return null
  const day = dayLabels[item.schedule_day] ?? item.schedule_day
  return `${day} • ${formatTime(item.start_time)}–${formatTime(item.end_time)}`
}

// ===== Undo delete (snackbar, 4s) =====
const undoItem = ref(null)
let undoTimer = null

function removeItem(item) {
  cart.remove(item.id)
  undoItem.value = item
  clearTimeout(undoTimer)
  undoTimer = setTimeout(() => { undoItem.value = null }, 4000)
}
function undoRemove() {
  if (!undoItem.value) return
  cart.add(undoItem.value)
  undoItem.value = null
  clearTimeout(undoTimer)
}

// ===== Close on ESC =====
function onKeydown(e) {
  if (e.key === 'Escape' && cart.state.isOpen) cart.close()
}
onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))

function checkout() {
  if (!cart.state.items.length || checking.value) return
  checking.value = true
  router.post(
    `/gym/${props.tenant.slug}/classes/checkout`,
    { class_ids: cart.state.items.map((i) => i.id) },
    {
      onFinish: () => {
        checking.value = false
      },
      onSuccess: () => {
        cart.clear()
        cart.close()
      },
    }
  )
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="cart.state.isOpen" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm" @click.self="cart.close()">
      <Transition
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
      >
        <div
          v-if="cart.state.isOpen"
          :class="[theme.bg, theme.text]"
          class="fixed top-0 right-0 h-full w-full max-w-sm shadow-2xl flex flex-col"
        >
          <!-- Header -->
          <div :class="theme.border" class="flex items-center justify-between px-5 py-4 border-b">
            <h2 class="font-semibold text-lg flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              {{ t.cart_title ?? 'ទំនិញក្នុងកន្ត្រក' }}
              <span
                v-if="cart.count.value"
                class="text-xs font-bold bg-emerald-500/15 text-emerald-400 px-2 py-0.5 rounded-full"
              >
                {{ cart.count.value }}
              </span>
            </h2>
            <button @click="cart.close()" class="p-1.5 rounded-full hover:bg-white/10 transition-colors">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Items / Empty state -->
          <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col">
            <div v-if="cart.state.items.length" class="space-y-3">
              <div
                v-for="item in cart.state.items"
                :key="item.id"
                :class="[theme.card, theme.border]"
                class="flex gap-3 rounded-xl border p-3"
              >
                <!-- thumbnail -->
                <div class="relative w-14 h-14 shrink-0 rounded-lg overflow-hidden">
                  <img
                    v-if="item.image_url"
                    :src="item.image_url"
                    :alt="item.name"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full bg-gradient-to-br from-emerald-800 via-slate-900 to-slate-950 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-400/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 12a2 2 0 100 4h1a2 2 0 100-4m-1 0a2 2 0 110-4h1a2 2 0 110 4m14 0a2 2 0 100 4h-1a2 2 0 100-4m1 0a2 2 0 110-4h-1a2 2 0 110 4" />
                    </svg>
                  </div>
                </div>

                <!-- details -->
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <p class="font-medium text-sm truncate">{{ item.name }}</p>
                    <button
                      @click="removeItem(item)"
                      class="shrink-0 text-red-400 hover:text-red-300 transition-colors"
                      :aria-label="t.cart_remove ?? 'Remove'"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>

                  <p v-if="scheduleLabel(item)" class="text-xs mt-0.5" :class="theme.textMuted">
                    {{ scheduleLabel(item) }}
                  </p>
                  <p v-if="item.trainer_name" class="text-xs mt-0.5 flex items-center gap-1" :class="theme.textMuted">
                    <svg class="w-3 h-3 opacity-60 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ item.trainer_name }}
                  </p>

                  <p class="text-emerald-400 text-sm font-semibold mt-1.5">${{ Number(item.price).toFixed(2) }}</p>
                </div>
              </div>

              <!-- undo snackbar -->
              <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <div
                  v-if="undoItem"
                  :class="[theme.card, theme.border]"
                  class="flex items-center justify-between gap-3 text-sm rounded-lg border px-3 py-2"
                >
                  <span :class="theme.textMuted">{{ t.cart_removed ?? 'បានលុបចេញពីកន្ត្រក' }}</span>
                  <button @click="undoRemove" class="text-emerald-400 font-semibold shrink-0 hover:text-emerald-300">
                    {{ t.cart_undo ?? 'ត្រឡប់វិញ' }}
                  </button>
                </div>
              </Transition>
            </div>

            <!-- empty state -->
            <div v-else class="flex-1 flex flex-col items-center justify-center text-center py-10">
              <div class="w-16 h-16 mb-4 rounded-2xl flex items-center justify-center" :class="theme.card">
                <svg class="w-7 h-7 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <p class="font-semibold" :class="theme.textMuted">{{ t.cart_empty ?? 'កន្ត្រកទទេ' }}</p>
              <p class="text-sm mt-1 mb-5 max-w-[220px]" :class="theme.textMuted">
                {{ t.cart_empty_hint ?? 'រកមើល Class ដែលអ្នកចូលចិត្ត ហើយបន្ថែមចូលទីនេះ' }}
              </p>
              <a
                :href="`/gym/${tenant.slug}/classes`"
                @click="cart.close()"
                class="text-sm font-semibold px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-colors"
              >
                {{ t.cart_browse ?? 'រកមើលថ្នាក់' }}
              </a>
            </div>
          </div>

          <!-- Footer -->
          <div v-if="cart.state.items.length" :class="theme.border" class="border-t px-5 py-4 space-y-3">
            <div class="flex items-center justify-between text-sm">
              <span class="opacity-70">{{ t.cart_total ?? 'សរុប' }}</span>
              <span class="font-bold text-lg text-emerald-400">${{ cart.total.value.toFixed(2) }}</span>
            </div>
            <button
              @click="checkout"
              :disabled="checking"
              class="w-full bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 text-slate-950 font-semibold rounded-lg py-3 transition-all"
            >
              {{ checking ? (t.cart_processing ?? 'កំពុងដំណើរការ...') : (t.cart_checkout ?? 'ទូទាត់ប្រាក់') }}
            </button>
            <p class="text-[11px] text-center flex items-center justify-center gap-1" :class="theme.textMuted">
              🔒 {{ t.cart_trust ?? 'គ្មានកម្រៃបន្ថែម • ទូទាត់សុវត្ថិភាព' }}
            </p>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>