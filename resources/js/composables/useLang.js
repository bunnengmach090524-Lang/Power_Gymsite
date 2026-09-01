import { ref, computed, watch } from 'vue'
import km from '../lang/km'
import en from '../lang/en'

const dict = { km, en }

const lang = ref(localStorage.getItem('gymsite_lang') || 'km')

watch(lang, (val) => {
  localStorage.setItem('gymsite_lang', val)
})

export function useLang() {
  function toggleLang() {
    lang.value = lang.value === 'km' ? 'en' : 'km'
  }

  const t = computed(() => dict[lang.value])

  return { lang, t, toggleLang }
}