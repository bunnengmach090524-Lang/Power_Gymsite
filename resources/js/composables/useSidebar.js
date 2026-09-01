import { ref, watch } from 'vue'

const collapsed = ref(localStorage.getItem('gymsite_sidebar_collapsed') === '1')

watch(collapsed, (val) => {
  localStorage.setItem('gymsite_sidebar_collapsed', val ? '1' : '0')
})

export function useSidebar() {
  function toggleSidebar() {
    collapsed.value = !collapsed.value
  }

  return { collapsed, toggleSidebar }
}