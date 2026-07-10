<template>
  <SlideOver v-model="isOpen" title="Lesson Details">
    <div v-if="lesson" class="space-y-6">
      
      <!-- Lesson Info -->
      <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Time & Date</h3>
        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {{ lesson.date }} ({{ lesson.start_time.substring(0,5) }} - {{ lesson.end_time.substring(0,5) }})
        </p>
      </div>

      <!-- Teacher Info -->
      <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Teacher</h3>
        <div class="flex items-center gap-3 mt-2">
          <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold text-sm">
            {{ lesson.teacher?.name?.[0] || 'T' }}
          </div>
          <span class="font-medium text-gray-900 dark:text-gray-100">{{ lesson.teacher?.name || 'Unassigned' }}</span>
        </div>
      </div>

      <!-- Students List -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Students ({{ lesson.students?.length || 0 }})</h3>
        </div>
        
        <div v-if="!lesson.students || lesson.students.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
          No students assigned to this lesson.
        </div>
        
        <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
          <li v-for="student in lesson.students" :key="student.id" class="py-4 flex flex-col gap-3">
            <div class="flex items-center gap-3">
              <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold text-sm">
                {{ student.name[0] }}
              </div>
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ student.name }}</span>
              <button @click="$router.push(`/students/${student.id}`)" class="ml-auto text-xs font-medium text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/20 dark:hover:bg-purple-900/40 px-2.5 py-1.5 rounded-lg transition-colors">
                View Profile
              </button>
            </div>
            
            <!-- Attendance Controls -->
            <div class="flex gap-2 justify-end transition-all duration-200">
              <button @click="markAttendance(student, 'present')" :class="[student.attendance === 'present' ? 'ring-2 ring-green-500 bg-green-100 dark:bg-green-900/60 font-semibold' : 'bg-green-50 hover:bg-green-100 dark:bg-green-900/30 opacity-70 hover:opacity-100']" class="text-xs px-2 py-1 rounded text-green-700 dark:text-green-400 transition-all">Present</button>
              <button @click="markAttendance(student, 'late')" :class="[student.attendance === 'late' ? 'ring-2 ring-yellow-500 bg-yellow-100 dark:bg-yellow-900/60 font-semibold' : 'bg-yellow-50 hover:bg-yellow-100 dark:bg-yellow-900/30 opacity-70 hover:opacity-100']" class="text-xs px-2 py-1 rounded text-yellow-700 dark:text-yellow-400 transition-all">Late</button>
              <button @click="markAttendance(student, 'absent_excused')" :class="[student.attendance === 'absent_excused' ? 'ring-2 ring-gray-500 bg-gray-200 dark:bg-gray-700 font-semibold' : 'bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 opacity-70 hover:opacity-100']" class="text-xs px-2 py-1 rounded text-gray-700 dark:text-gray-300 transition-all">Excused</button>
              <button @click="markAttendance(student, 'absent_unexcused')" :class="[student.attendance === 'absent_unexcused' ? 'ring-2 ring-red-500 bg-red-100 dark:bg-red-900/60 font-semibold' : 'bg-red-50 hover:bg-red-100 dark:bg-red-900/30 opacity-70 hover:opacity-100']" class="text-xs px-2 py-1 rounded text-red-700 dark:text-red-400 transition-all">Absent</button>
            </div>
          </li>
        </ul>
      </div>

    </div>
    
    <template #footer>
      <button @click="close" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors w-full">
        Close
      </button>
    </template>
  </SlideOver>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import { useLessonsStore } from '@/stores/lessonsStore'
import type { Lesson } from '@/stores/lessonsStore'

const isOpen = ref(false)
const lesson = ref<Lesson | null>(null)
const lessonsStore = useLessonsStore()

function open(l: Lesson) {
  lesson.value = l
  isOpen.value = true
}

function close() {
  isOpen.value = false
}

async function markAttendance(student: any, status: string) {
  if (!student.pivot_id) return
  const success = await lessonsStore.markAttendance(student.pivot_id, { status })
  if (success) {
    student.attendance = status
  }
}

defineExpose({ open, close })
</script>
