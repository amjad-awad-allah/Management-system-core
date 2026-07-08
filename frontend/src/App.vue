<template>
  <div class="min-h-full">
    <!-- Global Layout wrapping Router View -->
    <router-view />
    <!-- Global Notifications (Toasts) -->
    <ToastContainer />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import ToastContainer from '@/components/ui/ToastContainer.vue'

const uiStore = useUiStore()

onMounted(() => {
  uiStore.initTheme()
})
</script>
