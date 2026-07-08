import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const isDarkMode = ref(false)
  const isSidebarOpen = ref(false)

  function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value
    updateThemeClass()
    localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light')
  }

  function initTheme() {
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      isDarkMode.value = true
    } else {
      isDarkMode.value = false
    }
    updateThemeClass()
  }

  function updateThemeClass() {
    if (isDarkMode.value) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  function setSidebarOpen(value: boolean) {
    isSidebarOpen.value = value
  }

  return {
    isDarkMode,
    isSidebarOpen,
    toggleDarkMode,
    initTheme,
    setSidebarOpen
  }
})
