<template>
  <div class="space-y-6 h-full flex flex-col">
    <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Lessons</h1>
      <button @click="openSlideOver()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-medium shadow-sm transition-colors flex items-center gap-2">
        <PlusIcon class="w-5 h-5" />
        Schedule Lesson
      </button>
    </div>
    
    <div class="glass-panel rounded-2xl p-6 flex-1 min-h-0 flex flex-col relative">
      <!-- Loading Overlay -->
      <div v-if="store.isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center rounded-2xl">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <Calendar 
        :lessons="store.lessons"
        @lesson-click="handleLessonClick"
        @day-click="handleDayClick"
      />
    </div>

    <ScheduleLessonSlideOver ref="slideOver" />
    <AttendanceSlideOver ref="attendanceSlideOver" />
    <LessonDetailSlideOver ref="lessonDetailSlideOver" @edit-lesson="handleEditLesson" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/20/solid'
import ScheduleLessonSlideOver from '@/components/lessons/ScheduleLessonSlideOver.vue'
import AttendanceSlideOver from '@/components/lessons/AttendanceSlideOver.vue'
import LessonDetailSlideOver from '@/components/lessons/LessonDetailSlideOver.vue'
import Calendar from '@/components/calendar/Calendar.vue'
import { useLessonsStore, type Lesson } from '@/stores/lessonsStore'

const store = useLessonsStore()
const slideOver = ref<InstanceType<typeof ScheduleLessonSlideOver> | null>(null)
const attendanceSlideOver = ref<InstanceType<typeof AttendanceSlideOver> | null>(null)
const lessonDetailSlideOver = ref<InstanceType<typeof LessonDetailSlideOver> | null>(null)

onMounted(() => {
  store.fetchLessons()
})

function openSlideOver(dateStr?: string) {
  // If we had a mechanism to pass initial date to ScheduleLessonSlideOver, we'd do it here.
  // For now, we just open it. We will modify the slideover to accept a date if needed.
  slideOver.value?.open(dateStr)
}

function handleLessonClick(lesson: Lesson) {
  lessonDetailSlideOver.value?.open(lesson)
}

function handleDayClick(date: string) {
  openSlideOver(date)
}

function handleEditLesson(lesson: Lesson) {
  slideOver.value?.open(lesson)
}
</script>
