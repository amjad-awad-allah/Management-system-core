<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Dashboard</h1>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 relative">
      <div v-if="store.isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center rounded-2xl">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <div class="glass-panel rounded-2xl p-6">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Students</dt>
        <dd class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ store.stats.total_students }}</dd>
      </div>
      <div class="glass-panel rounded-2xl p-6">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Teachers</dt>
        <dd class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ store.stats.total_teachers }}</dd>
      </div>
      <div class="glass-panel rounded-2xl p-6">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Active Packages</dt>
        <dd class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ store.stats.active_packages }}</dd>
      </div>
      <div class="glass-panel rounded-2xl p-6">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Lessons Today</dt>
        <dd class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ store.stats.lessons_today }}</dd>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboardStore'

const store = useDashboardStore()

onMounted(() => {
  store.fetchStats()
})
</script>
