<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ClientLayout from '../../Layouts/ClientLayout.vue'
import { useTheme } from '../../composables/useTheme'
import { useLang } from '../../composables/useLang'

defineOptions({ layout: ClientLayout })

const props = defineProps({
  tenant: Object,
  settings: Object,
  plans: Array,
  activePromotions: Array,
})

const { theme } = useTheme()
const { t } = useLang()

const homeHref = computed(() => `/gym/${props.tenant.slug}`)
const bannerImage = computed(() => props.settings?.hero_banner_image?.image_url ?? null)

// ===== Auth-aware "Choose plan" link =====
const page = usePage()
const authUser = computed(() => page.props.auth?.user ?? null)
const isMemberHere = computed(() =>
  authUser.value?.role === 'member' && authUser.value?.member_tenant_slug === props.tenant?.slug
)

function purchaseHref(planId) {
  const purchasePath = `/gym/${props.tenant.slug}/plans/${planId}/purchase`

  if (isMemberHere.value) {
    return purchasePath
  }

  return `/auth/google/redirect?tenant=${props.tenant.slug}&redirect_to=${encodeURIComponent(purchasePath)}`
}

function promotionFor(planId) {
  return props.activePromotions?.find(p => !p.applicable_plan_id || p.applicable_plan_id === planId)
}

function isFeatured(i) {
  return (props.plans?.length ?? 0) === 3 && i === 1
}

// Icon + accent per tier position, purely visual — cycles if there are more than 3 plans
const tierIcons = [
  'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M12 6a9 9 0 100 18 9 9 0 000-18z', // basic: coin-ish/dollar-in-circle
  'M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7-6.2-3.9-6.2 3.9 1.6-7L2 9.2l7.1-.6L12 2z', // standard: star
  'M5 16l-2-9 6 3 3-6 3 6 6-3-2 9H5zm0 0v2a1 1 0 001 1h12a1 1 0 001-1v-2', // premium: crown
]

// Splits a "X + Y + Z" description into a checklist; falls back to a
// single bullet with the whole description when there's no "+" to split on.
function featuresFor(plan) {
  const text = plan.description?.trim()
  if (!text) return []
  return text.split('+').map(f => f.trim()).filter(Boolean)
}
</script>

<template>
  <!-- ===== PAGE BANNER ===== -->
  <section class="relative h-56 sm:h-72 flex items-center justify-center text-center px-6 overflow-hidden">
    <div
      v-if="bannerImage"
      class="absolute inset-0 bg-cover bg-center scale-105"
      :style="`background-image: url(${bannerImage})`"
    ></div>
    <div v-else class="absolute inset-0 bg-gradient-to-br from-emerald-900 via-slate-950 to-teal-900"></div>
    <div class="absolute inset-0 bg-slate-950/70"></div>
    <div class="relative animate-[fadeUp_0.7s_ease-out]">
      <p class="text-emerald-400 text-xs font-semibold tracking-[0.25em] uppercase mb-3">{{ tenant.name }}</p>
      <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">{{ t.pricing_page_title ?? 'Membership Plans' }}</h1>
    </div>
  </section>

    <!-- ===== PLANS GRID ===== -->
  <section class="max-w-6xl mx-auto px-6 py-20">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 items-start pt-4">
      <div
        v-for="(plan, i) in plans"
        :key="plan.id"
        v-reveal="{ delay: i * 90 }"
        class="relative group"
        :class="isFeatured(i) ? 'lg:scale-105 mt-4 lg:mt-0' : ''"
      >
        <!-- Ambient halo glow, sits behind the card. Featured = permanent pulsing gold glow;
             others = soft emerald glow that fades in on hover. -->
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
            'relative rounded-2xl p-8 pt-9 border overflow-hidden transition-all duration-300 hover:-translate-y-2 flex flex-col',
            isFeatured(i)
              ? 'border-amber-400/60 shadow-2xl shadow-amber-500/20'
              : 'hover:border-emerald-500/50 hover:shadow-2xl hover:shadow-emerald-500/20',
          ]"
        >
          <!-- Gold accent bar on the featured plan, matching the premium-rate feel from the reference site -->
          <div
            v-if="isFeatured(i)"
            class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-300 via-yellow-400 to-amber-300"
          ></div>
          <!-- subtle shimmer sweep across the accent bar -->
          <div
            v-if="isFeatured(i)"
            class="absolute top-0 left-0 right-0 h-1.5 overflow-hidden"
          >
            <div class="w-1/3 h-full bg-white/60 blur-sm animate-[shimmerSweep_2.4s_ease-in-out_infinite]"></div>
          </div>
          <span
            v-if="promotionFor(plan.id)"
            class="absolute top-4 right-4 bg-amber-400 text-slate-950 text-xs font-bold px-2.5 py-1 rounded-full shadow animate-[pulse_2s_ease-in-out_infinite]"
          >
            -{{ promotionFor(plan.id).discount_value }}{{ promotionFor(plan.id).discount_type === 'percentage' ? '%' : '$' }}
          </span>

          <!-- Tier icon, with a soft glowing ring for the featured plan -->
          <div class="relative w-14 h-14 mb-5">
            <div
              v-if="isFeatured(i)"
              class="absolute -inset-1.5 rounded-2xl bg-amber-400/40 blur-md animate-[pulse_2.5s_ease-in-out_infinite]"
            ></div>
            <div
              class="relative w-14 h-14 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110"
              :class="isFeatured(i) ? 'bg-gradient-to-br from-amber-300 to-yellow-400' : 'bg-emerald-500/10 group-hover:bg-emerald-500/20'"
            >
              <svg class="w-7 h-7" :class="isFeatured(i) ? 'text-slate-950' : 'text-emerald-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" :d="tierIcons[i % tierIcons.length]" />
              </svg>
            </div>
          </div>

          <span
            v-if="isFeatured(i)"
            class="inline-block text-[10px] font-semibold uppercase tracking-[0.15em] text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full mb-3 w-fit"
          >
            {{ t.pricing_exclusive_rate ?? 'Exclusive Rate' }}
          </span>

          <h3 class="font-semibold text-xl mb-1">{{ plan.name }}</h3>
          <p
            class="text-4xl font-extrabold mb-1 group-hover:scale-105 transition-transform origin-left inline-block w-fit"
            :class="isFeatured(i) ? 'bg-gradient-to-r from-amber-300 via-yellow-300 to-amber-400 bg-clip-text text-transparent' : 'text-emerald-400'"
          >
            ${{ plan.price }}
          </p>
          <p class="inline-block text-xs font-medium px-2.5 py-1 rounded-full mb-5 w-fit" :class="[theme.bgAlt, theme.textMuted]">
            {{ plan.duration_days }} {{ t.pricing_days ?? 'days' }} {{ t.pricing_min_purchase ?? 'access' }}
          </p>

          <!-- Feature checklist, parsed from the plan description -->
          <ul v-if="featuresFor(plan).length" class="space-y-2.5 mb-8 flex-1">
            <li v-for="(feature, fi) in featuresFor(plan)" :key="fi" class="flex items-start gap-2.5 text-sm">
              <svg
                class="w-4 h-4 mt-0.5 shrink-0"
                :class="isFeatured(i) ? 'text-amber-400' : 'text-emerald-400'"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span :class="theme.textMuted">{{ feature }}</span>
            </li>
          </ul>
          <p v-else class="text-sm mb-8 flex-1 leading-relaxed" :class="theme.textMuted">
            {{ t.pricing_no_description ?? 'Full access to all facilities and equipment.' }}
          </p>

          <a
            :href="purchaseHref(plan.id)"
            class="flex items-center justify-center gap-1.5 text-center font-semibold rounded-lg py-3 transition-all hover:scale-[1.02]"
            :class="isFeatured(i)
              ? 'bg-gradient-to-r from-amber-300 to-yellow-400 hover:from-amber-200 hover:to-yellow-300 text-slate-950 shadow-lg shadow-amber-500/30 hover:shadow-amber-400/50'
              : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 hover:shadow-lg hover:shadow-emerald-500/30'"
          >
            {{ t.pricing_choose ?? 'Choose plan' }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>

      <p v-if="!plans?.length" class="col-span-full text-center py-16" :class="theme.textMuted">
        {{ t.pricing_empty ?? 'Plans coming soon.' }}
      </p>
    </div>
  </section>
  <!-- ===== CTA STRIP ===== -->
  <section :class="[theme.bgAlt, theme.border]" class="py-16 border-t transition-colors duration-300">
    <div class="max-w-3xl mx-auto px-6 text-center" v-reveal>
      <h2 class="text-2xl sm:text-3xl font-bold mb-4">{{ t.pricing_cta_title ?? 'Not sure which plan fits you?' }}</h2>
      <p class="mb-8" :class="theme.textMuted">{{ t.pricing_cta_body ?? 'Reach out and our team will help you find the right membership.' }}</p>
      <a
        :href="`${homeHref}/contact`"
        class="inline-block bg-emerald-500 hover:bg-emerald-400 hover:scale-105 text-slate-950 font-semibold rounded-full px-8 py-3.5 transition-all duration-200 shadow-xl shadow-emerald-500/25"
      >
        {{ t.pricing_cta_button ?? 'Contact us' }}
      </a>
    </div>
  </section>
</template>

<style>
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.35; transform: scale(1); }
  50%      { opacity: 0.55; transform: scale(1.03); }
}

@keyframes shimmerSweep {
  0%   { transform: translateX(-120%); }
  100% { transform: translateX(320%); }
}
</style>