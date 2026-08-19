<template>
  <div class="space-y-4 h-full flex flex-col">
    <div class="flex items-center justify-between shrink-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Lessons & Calendar</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">Operational Scheduling Engine & Conflict Detector</p>
      </div>
      <button @click="openSlideOver()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
        <PlusIcon class="w-4 h-4" />
        Schedule Lesson
      </button>
    </div>
    
    <div class="glass-panel rounded-2xl p-4 flex-1 min-h-0 flex flex-col relative overflow-hidden">
      <Calendar 
        @lesson-click="handleLessonClick"
        @slot-click="handleSlotClick"
      />
    </div>

    <ScheduleLessonSlideOver ref="slideOver" />
    <AttendanceSlideOver ref="attendanceSlideOver" />
    <LessonDetailSlideOver ref="lessonDetailSlideOver" @edit-lesson="handleEditLesson" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { PlusIcon } from '@heroicons/vue/20/solid'
import ScheduleLessonSlideOver from '@/components/lessons/ScheduleLessonSlideOver.vue'
import AttendanceSlideOver from '@/components/lessons/AttendanceSlideOver.vue'
import LessonDetailSlideOver from '@/components/lessons/LessonDetailSlideOver.vue'
import Calendar from '@/components/calendar/Calendar.vue'

const slideOver = ref<InstanceType<typeof ScheduleLessonSlideOver> | null>(null)
const attendanceSlideOver = ref<InstanceType<typeof AttendanceSlideOver> | null>(null)
const lessonDetailSlideOver = ref<InstanceType<typeof LessonDetailSlideOver> | null>(null)

function openSlideOver(dateStr?: string) {
  slideOver.value?.open(dateStr)
}

function handleLessonClick(lesson: any) {
  lessonDetailSlideOver.value?.open(lesson)
}

function handleSlotClick(payload: { date: string; startTime: string }) {
  openSlideOver(payload.date)
}

function handleEditLesson(lesson: any) {
  slideOver.value?.open(lesson)
}
</script>
