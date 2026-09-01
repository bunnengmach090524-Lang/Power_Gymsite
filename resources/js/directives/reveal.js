// v-reveal directive: fades/slides an element in once it scrolls into view.
// Usage: <section v-reveal> ... </section>  (optionally v-reveal="{ delay: 150 }")
export default {
  mounted(el, binding) {
    const delay = binding.value?.delay ?? 0
    el.style.opacity = '0'
    el.style.transform = 'translateY(24px)'
    el.style.transition = `opacity 0.7s ease ${delay}ms, transform 0.7s ease ${delay}ms`

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          el.style.opacity = '1'
          el.style.transform = 'translateY(0)'
          observer.unobserve(el)
        }
      },
      { threshold: 0.15 }
    )
    observer.observe(el)
  },
}