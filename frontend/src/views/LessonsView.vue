<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Lessons</h1>
      <button @click="openSlideOver" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-medium shadow-sm transition-colors">
        Schedule Lesson
      </button>
    </div>
    <div class="glass-panel rounded-2xl p-6 min-h-[400px] flex items-center justify-center">
      <p class="text-gray-500">Calendar Component Goes Here</p>
    </div>

    <ScheduleLessonSlideOver ref="slideOver" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import ScheduleLessonSlideOver from '@/components/lessons/ScheduleLessonSlideOver.vue'

const slideOver = ref<InstanceType<typeof ScheduleLessonSlideOver> | null>(null)

function openSlideOver() {
  slideOver.value?.open()
}
</script>
