<template>
  <div class="h-full flex flex-col">
    <!-- Calendar Header -->
    <div class="flex items-center justify-between pb-4">
      <h2 class="text-xl font-semibold leading-6 text-gray-900 dark:text-gray-100">
        {{ format(currentMonth, 'MMMM yyyy') }}
      </h2>
      <div class="flex items-center space-x-2">
        <button
          @click="previousMonth"
          class="flex items-center justify-center rounded-md p-1.5 text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        >
          <span class="sr-only">Previous month</span>
          <ChevronLeftIcon class="h-5 w-5" aria-hidden="true" />
        </button>
        <button
          @click="nextMonth"
          class="flex items-center justify-center rounded-md p-1.5 text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        >
          <span class="sr-only">Next month</span>
          <ChevronRightIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
    </div>

    <!-- Days of week -->
    <div class="overflow-x-auto rounded-b-xl border border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="min-w-[700px]">
        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
          <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="py-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ day }}
          </div>
        </div>

        <!-- Calendar Grid -->
        <div class="flex-1 grid grid-cols-7 grid-rows-5 gap-px bg-gray-200 dark:bg-gray-700">
      <div
        v-for="(day, dayIdx) in calendarDays"
        :key="dayIdx"
        @click="emit('day-click', format(day, 'yyyy-MM-dd'))"
        :class="[
          !isSameMonth(day, currentMonth) ? 'bg-gray-50/50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-600' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100',
          isToday(day) ? 'font-semibold' : '',
          'relative px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors cursor-pointer min-h-[120px]'
        ]"
      >
        <time
          :datetime="format(day, 'yyyy-MM-dd')"
          :class="[
            isToday(day) ? 'flex h-6 w-6 items-center justify-center rounded-full bg-purple-600 font-semibold text-white' : '',
            'ml-auto text-sm mb-2 block w-max'
          ]"
        >
          {{ format(day, 'd') }}
        </time>
        
        <!-- Lessons for this day -->
        <div class="flex flex-col gap-1 overflow-y-auto max-h-[80px] custom-scrollbar">
          <div
            v-for="lesson in getLessonsForDay(day)"
            :key="lesson.id"
            @click.stop="emit('lesson-click', lesson)"
            class="group flex flex-col px-1.5 py-1 text-xs leading-tight rounded bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/50 cursor-pointer transition-colors border border-purple-200 dark:border-purple-800"
          >
            <div class="flex items-center justify-between">
              <span class="font-medium truncate">{{ lesson.start_time.substring(0,5) }}</span>
              <span class="truncate opacity-75 group-hover:opacity-100 font-bold ml-1 text-[10px]">{{ lesson.students?.length || 0 }} 🎓</span>
            </div>
            <div class="truncate text-[10px] mt-0.5 opacity-80">{{ lesson.teacher?.name || 'TBD' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  format,
  addMonths,
  subMonths,
  startOfMonth,
  endOfMonth,
  startOfWeek,
  endOfWeek,
  eachDayOfInterval,
  isSameMonth,
  isToday,
  parseISO
} from 'date-fns'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid'
import type { Lesson } from '@/stores/lessonsStore'

const props = defineProps<{
  lessons: Lesson[]
}>()

const emit = defineEmits<{
  (e: 'lesson-click', lesson: Lesson): void
  (e: 'day-click', date: string): void
}>()

const currentMonth = ref(startOfMonth(new Date()))

const calendarDays = computed(() => {
  const start = startOfWeek(currentMonth.value)
  const end = endOfWeek(endOfMonth(currentMonth.value))
  return eachDayOfInterval({ start, end })
})

function previousMonth() {
  currentMonth.value = subMonths(currentMonth.value, 1)
}

function nextMonth() {
  currentMonth.value = addMonths(currentMonth.value, 1)
}

function getLessonsForDay(day: Date) {
  const formattedDay = format(day, 'yyyy-MM-dd')
  return props.lessons
    .filter(l => l.date === formattedDay)
    .sort((a, b) => a.start_time.localeCompare(b.start_time))
}
</script>

<style scoped>
@reference "tailwindcss";

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600;
  border-radius: 4px;
}
</style>
