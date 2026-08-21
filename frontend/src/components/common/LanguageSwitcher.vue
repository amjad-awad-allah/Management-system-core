<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="isOpen = !isOpen"
      type="button"
      class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200/80 dark:border-gray-700/80 bg-white/70 dark:bg-gray-800/70 hover:bg-white dark:hover:bg-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-200 backdrop-blur-md shadow-xs transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500/40"
      :aria-expanded="isOpen"
      aria-haspopup="true"
    >
      <span class="text-sm leading-none">{{ currentLocaleConfig?.flag }}</span>
      <span class="hidden sm:inline font-bold tracking-tight">{{ currentLocaleConfig?.nativeName }}</span>
      <ChevronDownIcon
        class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition ease-out duration-150 transform"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition ease-in duration-100 transform"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute end-0 mt-2 w-36 rounded-2xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border border-gray-200/80 dark:border-gray-700/80 shadow-xl ring-1 ring-black/5 p-1 z-50 overflow-hidden"
        role="menu"
      >
        <button
          v-for="loc in supportedLocales"
          :key="loc.code"
          @click="selectLocale(loc.code)"
          type="button"
          class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer text-start"
          :class="[
            currentLocale === loc.code
              ? 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 font-bold'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
          ]"
          role="menuitem"
        >
          <div class="flex items-center gap-2">
            <span class="text-sm">{{ loc.flag }}</span>
            <span>{{ loc.nativeName }}</span>
          </div>
          <CheckIcon v-if="currentLocale === loc.code" class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400 shrink-0" />
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { supportedLocales } from '@/config/locales'
import { setAppLocale } from '@/i18n'
import api from '@/api'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/20/solid'

const { locale } = useI18n()
const isOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const currentLocale = computed(() => locale.value)
const currentLocaleConfig = computed(() =>
  supportedLocales.find((l) => l.code === currentLocale.value) || supportedLocales[0]
)

async function selectLocale(code: string) {
  if (code === currentLocale.value) {
    isOpen.value = false
    return
  }

  // 1. Optimistic instant UI update
  setAppLocale(code)
  isOpen.value = false

  // 2. Synchronize to authenticated user profile in background
  try {
    const token = localStorage.getItem('auth_token')
    if (token) {
      await api.patch('/user/preferences', { preferred_locale: code })
    }
  } catch (e) {
    // Non-blocking background sync failure
    console.warn('Could not sync preferred locale with server:', e)
  }
}

function handleClickOutside(event: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
