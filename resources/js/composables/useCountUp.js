import { ref, onMounted } from 'vue'

// Animates a number counting up from 0 to `target` once it's called.
// Used for the stats bar (members / plans / photos counts).
export function useCountUp(target, duration = 1200) {
  const value = ref(0)

  onMounted(() => {
    const start = performance.now()
    function tick(now) {
      const progress = Math.min((now - start) / duration, 1)
      // ease-out cubic - fast start, gentle finish, feels more "alive" than linear
      const eased = 1 - Math.pow(1 - progress, 3)
      value.value = Math.round(eased * target)
      if (progress < 1) requestAnimationFrame(tick)
    }
    requestAnimationFrame(tick)
  })

  return value
}