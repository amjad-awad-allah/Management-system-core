<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Dashboard</h1>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="stat in stats" :key="stat.name" class="glass-panel rounded-2xl p-6">
        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ stat.name }}</dt>
        <dd class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ stat.value }}</dd>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const stats = [
  { name: 'Total Students', value: '120' },
  { name: 'Total Teachers', value: '15' },
  { name: 'Active Packages', value: '45' },
  { name: 'Lessons Today', value: '12' },
]
</script>
