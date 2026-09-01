import { reactive, computed, watch } from 'vue'

// One reactive cart instance per tenant slug, cached at module scope so every
// component that calls useCart(slug) shares the same state (no prop drilling
// needed, and it survives navigation between pages within the same gym).
const cartsBySlug = new Map()

function loadFromStorage(slug) {
  try {
    const raw = localStorage.getItem(`gymsite_cart_${slug}`)
    return raw ? JSON.parse(raw) : []
  } catch (e) {
    return []
  }
}

function createCart(slug) {
  const state = reactive({
    items: loadFromStorage(slug),
    isOpen: false,
  })

  watch(
    () => state.items,
    (items) => {
      try {
        localStorage.setItem(`gymsite_cart_${slug}`, JSON.stringify(items))
      } catch (e) {
        // Storage full/unavailable (e.g. private browsing) — cart still
        // works in-memory for this session, just won't persist on refresh.
      }
    },
    { deep: true }
  )

  const count = computed(() => state.items.length)
  const total = computed(() => state.items.reduce((sum, i) => sum + Number(i.price || 0), 0))

  function add(cls) {
    if (state.items.some((i) => i.id === cls.id)) return
    state.items.push({
      id: cls.id,
      name: cls.name,
      price: cls.price,
      image_url: cls.image_url ?? null,
      schedule_day: cls.schedule_day ?? null,
      start_time: cls.start_time ?? null,
      end_time: cls.end_time ?? null,
      trainer_name: cls.trainer_name ?? null,
    })
  }
  function remove(classId) {
    state.items = state.items.filter((i) => i.id !== classId)
  }
  function has(classId) {
    return state.items.some((i) => i.id === classId)
  }
  function clear() {
    state.items = []
  }
  function open() {
    state.isOpen = true
  }
  function close() {
    state.isOpen = false
  }
  function toggle() {
    state.isOpen = !state.isOpen
  }

  return { state, count, total, add, remove, has, clear, open, close, toggle }
}

export function useCart(tenantSlug) {
  const slug = tenantSlug || 'default'
  if (!cartsBySlug.has(slug)) {
    cartsBySlug.set(slug, createCart(slug))
  }
  return cartsBySlug.get(slug)
}