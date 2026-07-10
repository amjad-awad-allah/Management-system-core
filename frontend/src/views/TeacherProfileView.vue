<template>
<div>
  <div v-if="isLoading" class="flex items-center justify-center h-full">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
  </div>
  
  <div v-else-if="teacher" class="space-y-6 h-full flex flex-col overflow-y-auto pb-8 custom-scrollbar">
    
    <!-- Header -->
    <div class="glass-panel p-6 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
      <div class="flex items-center gap-4">
        <div class="h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold text-2xl shadow-sm">
          {{ teacher.name[0] }}
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ teacher.name }}</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ teacher.qualification }} • €{{ teacher.hourly_rate }}/hr
          </p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span :class="[
          teacher.status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/10',
          'inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium ring-1 ring-inset'
        ]">
          {{ teacher.status === 'active' ? 'Active' : 'Inactive' }}
        </span>
        <button @click="openEditSlideOver" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors">
          Edit Profile
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 min-h-0">
      
      <!-- Left Column: Details & Subjects -->
      <div class="space-y-6 lg:col-span-1 overflow-y-auto custom-scrollbar pr-1">
        
        <!-- Contact Info -->
        <div class="glass-panel rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h2>
          <div class="space-y-4">
            <div>
              <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Email</p>
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ teacher.email || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Phone</p>
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ teacher.phone || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <!-- Subjects -->
        <div class="glass-panel rounded-2xl p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Assigned Subjects</h2>
          </div>
          
          <div v-if="!teacher.subjects || teacher.subjects.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
            No subjects assigned yet.
          </div>
          
          <div v-else class="flex flex-wrap gap-2">
            <span v-for="subject in teacher.subjects" :key="subject.id" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
              {{ subject.name }}
            </span>
          </div>
        </div>

      </div>

      <!-- Right Column: Students -->
      <div class="lg:col-span-2 glass-panel rounded-2xl flex flex-col overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 px-6 py-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">My Students ({{ teacher.students?.length || 0 }})</h2>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
          <div v-if="!teacher.students || teacher.students.length === 0" class="flex flex-col items-center justify-center h-full text-center py-12 text-gray-500 dark:text-gray-400">
            <div class="bg-gray-100 dark:bg-gray-800 h-16 w-16 rounded-full flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <p>No students have taken lessons with this teacher yet.</p>
          </div>
          
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="student in teacher.students" :key="student.id" @click="$router.push(`/students/${student.id}`)" class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-purple-200 dark:hover:border-purple-800 bg-white dark:bg-gray-800 hover:shadow-md transition-all cursor-pointer group">
              <div class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold text-sm shrink-0">
                {{ student.first_name[0] }}{{ student.last_name[0] }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                  {{ student.first_name }} {{ student.last_name }}
                </p>
              </div>
              <div class="text-gray-400 group-hover:text-purple-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
      
    </div>

    <!-- Calendar View for Teacher's Lessons -->
    <div class="glass-panel p-6 rounded-2xl flex-1 min-h-[500px] flex flex-col relative">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Teacher Schedule</h2>
      </div>
      
      <div v-if="lessonsStore.isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center rounded-2xl">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <Calendar 
        :lessons="lessonsStore.lessons"
        @lesson-click="handleLessonClick"
      />
    </div>

  </div>
  

  <div v-else class="flex flex-col items-center justify-center h-full text-center py-12">
    <div class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-4 rounded-full mb-4">
      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    </div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Teacher not found</h3>
    <button @click="$router.push('/teachers')" class="text-purple-600 hover:text-purple-700 font-medium">
      &larr; Back to Teachers
    </button>
  </div>
  
  <EditTeacherSlideOver ref="editSlideOver" @teacher-updated="fetchTeacher" />
  <LessonDetailSlideOver ref="lessonDetailSlideOver" />
</div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EditTeacherSlideOver from '@/components/teachers/EditTeacherSlideOver.vue'
import LessonDetailSlideOver from '@/components/lessons/LessonDetailSlideOver.vue'
import Calendar from '@/components/calendar/Calendar.vue'
import { useLessonsStore, type Lesson } from '@/stores/lessonsStore'
import api from '@/api'

const route = useRoute()
const router = useRouter()
const isLoading = ref(true)
const teacher = ref<any>(null)
const editSlideOver = ref<InstanceType<typeof EditTeacherSlideOver> | null>(null)
const lessonDetailSlideOver = ref<InstanceType<typeof LessonDetailSlideOver> | null>(null)
const lessonsStore = useLessonsStore()

function openEditSlideOver() {
  if (teacher.value) {
    editSlideOver.value?.open(teacher.value)
  }
}

async function fetchTeacher() {
  isLoading.value = true
  try {
    const res = await api.get(`/nachhilfe/teachers/${route.params.id}`)
    teacher.value = res.data.data
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchTeacher()
  lessonsStore.fetchLessons({ teacher_id: route.params.id })
})

function handleLessonClick(lesson: Lesson) {
  lessonDetailSlideOver.value?.open(lesson)
}
</script>
