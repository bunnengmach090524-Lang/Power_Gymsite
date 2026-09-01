import { ref, computed, watch } from 'vue'

const isDark = ref(localStorage.getItem('gymsite_theme') !== 'light')

function applyThemeClass(val) {
  if (val) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

applyThemeClass(isDark.value)

watch(isDark, (val) => {
  localStorage.setItem('gymsite_theme', val ? 'dark' : 'light')
  applyThemeClass(val)
})

export function useTheme() {
  function toggleTheme() {
    isDark.value = !isDark.value
  }

  const theme = computed(() => isDark.value ? {
    bg: 'bg-slate-950', bgAlt: 'bg-slate-900', text: 'text-white', textMuted: 'text-slate-400',
    border: 'border-slate-800', card: 'bg-slate-900 border-slate-800',
    input: 'bg-slate-800 border-slate-700 text-white placeholder-slate-500',
    navBg: 'bg-slate-950/80',
  } : {
    bg: 'bg-slate-50', bgAlt: 'bg-white', text: 'text-slate-900', textMuted: 'text-slate-500',
    border: 'border-slate-200', card: 'bg-white border-slate-200 shadow-sm',
    input: 'bg-white border-slate-300 text-slate-900 placeholder-slate-400',
    navBg: 'bg-slate-50/80',
  })

  return { isDark, theme, toggleTheme }
}