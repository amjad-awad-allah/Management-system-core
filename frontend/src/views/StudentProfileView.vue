<template>
<div>
  <div v-if="store.currentStudent" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-x-6">
      <div class="h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-2xl font-bold text-purple-700 dark:text-purple-300">
        {{ store.currentStudent.first_name[0] }}{{ store.currentStudent.last_name[0] }}
      </div>
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
          {{ store.currentStudent.first_name }} {{ store.currentStudent.last_name }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Parent: {{ store.currentStudent.parent_name }} | Phone: {{ store.currentStudent.parent_phone_1 }}
        </p>
      </div>
      <div class="ml-auto flex gap-3">
        <button @click="openEditSlideOver" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors">
          Edit Profile
        </button>
        <button @click="confirmDeleteStudent" class="bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 dark:border-red-900 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 px-4 py-2 rounded-xl text-sm font-medium shadow-sm transition-colors">
          Delete
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Details Card -->
      <div class="glass-panel rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Details</h3>
        <dl class="divide-y divide-gray-100 dark:divide-gray-800">
          <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-300">Billing Type</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-400 sm:col-span-2 sm:mt-0">{{ store.currentStudent.billing_type }}</dd>
          </div>
          <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-300">Status</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-400 sm:col-span-2 sm:mt-0">
              <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                {{ store.currentStudent.status }}
              </span>
            </dd>
          </div>
          <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
            <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-300">DOB</dt>
            <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-400 sm:col-span-2 sm:mt-0">{{ store.currentStudent.date_of_birth }}</dd>
          </div>
        </dl>
      </div>

      <!-- Invoices -->
      <div class="glass-panel rounded-2xl p-6 lg:col-span-2 space-y-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Invoices</h3>
          <div v-if="!invoices.length" class="text-center text-gray-500 py-8">
            No invoices found.
          </div>
          <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
            <li v-for="invoice in invoices" :key="invoice.id" class="flex justify-between gap-x-6 py-4">
              <div class="flex min-w-0 gap-x-4">
                <div class="min-w-0 flex-auto">
                  <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">{{ invoice.month }}</p>
                  <p class="mt-1 truncate text-xs leading-5 text-gray-500">Status: {{ invoice.status }}</p>
                </div>
              </div>
              <div class="hidden sm:flex sm:flex-col sm:items-end">
                <p class="text-sm leading-6 text-gray-900 dark:text-white font-medium">€{{ invoice.total_amount }}</p>
                <button v-if="invoice.status === 'draft' || invoice.status === 'unpaid'" @click="markAsPaid(invoice)" class="mt-1 inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 hover:bg-purple-100 transition-colors">
                  Mark Paid
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
      
      <!-- Subjects & Teachers -->
      <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Subjects -->
        <div class="glass-panel rounded-2xl p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enrolled Subjects</h3>
          <div v-if="!store.currentStudent.subjects || store.currentStudent.subjects.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
            No subjects assigned.
          </div>
          <div v-else class="flex flex-wrap gap-2">
            <span v-for="subject in store.currentStudent.subjects" :key="subject.id" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
              {{ subject.name }}
            </span>
          </div>
        </div>

        <!-- Teachers -->
        <div class="glass-panel rounded-2xl p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">My Teachers</h3>
          <div v-if="!store.currentStudent.teachers || store.currentStudent.teachers.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
            No teachers assigned.
          </div>
          <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
            <li v-for="teacher in store.currentStudent.teachers" :key="teacher.id" class="py-3 flex items-center justify-between group cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-lg px-2 -mx-2 transition-colors" @click="$router.push(`/teachers/${teacher.id}`)">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold text-sm">
                  {{ teacher.name[0] }}
                </div>
                <span class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-purple-600 transition-colors">{{ teacher.name }}</span>
              </div>
              <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Calendar View for Student's Lessons -->
    <div class="glass-panel p-6 rounded-2xl flex-1 min-h-[500px] flex flex-col relative mt-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Student Schedule</h2>
      </div>
      
      <div v-if="lessonsStore.isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center rounded-2xl">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <Calendar 
        :lessons="lessonsStore.lessons"
        @lesson-click="handleLessonClick"
      />
    </div>

    <LessonDetailSlideOver ref="lessonDetailSlideOver" />
    <EditStudentSlideOver ref="editSlideOver" />
    <ConfirmModal ref="confirmModal" />
  </div>
  
  <div v-else-if="store.isLoading" class="flex justify-center items-center h-64">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
  </div>
</div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStudentsStore } from '@/stores/studentsStore'
import { useLessonsStore, type Lesson } from '@/stores/lessonsStore'
import EditStudentSlideOver from '@/components/students/EditStudentSlideOver.vue'
import LessonDetailSlideOver from '@/components/lessons/LessonDetailSlideOver.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import Calendar from '@/components/calendar/Calendar.vue'
import api from '@/api'

const route = useRoute()
const router = useRouter()
const store = useStudentsStore()
const lessonsStore = useLessonsStore()
const editSlideOver = ref<InstanceType<typeof EditStudentSlideOver> | null>(null)
const lessonDetailSlideOver = ref<InstanceType<typeof LessonDetailSlideOver> | null>(null)
const confirmModal = ref<InstanceType<typeof ConfirmModal> | null>(null)

const statement = ref<any[]>([])
const invoices = ref<any[]>([])

async function fetchStatement(id: string) {
  try {
    const res = await api.get(`/nachhilfe/students/${id}/statement`)
    statement.value = res.data
  } catch (err) {
    console.error('Failed to load statement', err)
  }
}

async function fetchInvoices(id: string) {
  try {
    const res = await api.get(`/nachhilfe/invoices?student_id=${id}`)
    invoices.value = res.data.data || res.data // depending on pagination
  } catch (err) {
    console.error('Failed to load invoices', err)
  }
}

async function markAsPaid(invoice: any) {
  try {
    await api.patch(`/nachhilfe/invoices/${invoice.id}/status`, { status: 'paid' })
    if (store.currentStudent) {
      fetchInvoices(store.currentStudent.id)
    }
  } catch (err) {
    console.error('Failed to mark as paid', err)
  }
}

onMounted(() => {
  if (route.params.id) {
    store.fetchStudent(route.params.id as string)
    fetchStatement(route.params.id as string)
    fetchInvoices(route.params.id as string)
    lessonsStore.fetchLessons({ student_id: route.params.id })
  }
})

watch(() => route.params.id, (newId) => {
  if (newId) {
    store.fetchStudent(newId as string)
    fetchStatement(newId as string)
    fetchInvoices(newId as string)
    lessonsStore.fetchLessons({ student_id: newId as string })
  }
})

function handleLessonClick(lesson: Lesson) {
  lessonDetailSlideOver.value?.open(lesson)
}

function openEditSlideOver() {
  if (store.currentStudent) {
    editSlideOver.value?.open(store.currentStudent)
  }
}

function confirmDeleteStudent() {
  const student = store.currentStudent
  if (!student) return
  
  confirmModal.value?.open(
    'Delete Student',
    `Are you sure you want to delete ${student.first_name} ${student.last_name}? This action cannot be undone.`,
    'Delete',
    'Cancel',
    async () => {
      const success = await store.deleteStudent(student.id)
      if (success) {
        router.push('/students')
      }
    }
  )
}
</script>
