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
        <button @click="openAddPackageSlideOver" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-sm font-medium shadow-sm transition-colors">
          Add Package
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

      <!-- Packages & Balances -->
      <div class="glass-panel rounded-2xl p-6 lg:col-span-2 space-y-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Active Packages</h3>
          <div v-if="!store.currentStudent.packages?.length" class="text-center text-gray-500 py-8">
            No active packages found.
          </div>
          <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
            <li v-for="pkg in store.currentStudent.packages" :key="pkg.id" class="flex justify-between gap-x-6 py-4">
              <div class="flex min-w-0 gap-x-4">
                <div class="min-w-0 flex-auto">
                  <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">{{ pkg.package?.name }}</p>
                  <p class="mt-1 truncate text-xs leading-5 text-gray-500">{{ pkg.status }}</p>
                </div>
              </div>
              <div class="hidden sm:flex sm:flex-col sm:items-end">
                <p class="text-sm leading-6 text-gray-900 dark:text-white">{{ pkg.remaining_hours }} hours remaining</p>
              </div>
            </li>
          </ul>
        </div>

        <!-- Statement Section -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-t border-gray-100 dark:border-gray-800 pt-6">Lesson Statement</h3>
          <div v-if="!statement.length" class="text-center text-gray-500 py-8">
            No lesson history found.
          </div>
          <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
            <li v-for="item in statement" :key="item.id" class="flex justify-between gap-x-6 py-4">
              <div class="flex min-w-0 gap-x-4">
                <div class="min-w-0 flex-auto">
                  <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">
                    {{ item.lesson?.subject?.name || 'Unknown Subject' }} w/ {{ item.lesson?.teacher?.name || 'Unknown Teacher' }}
                  </p>
                  <p class="mt-1 truncate text-xs leading-5 text-gray-500">
                    {{ new Date(item.created_at).toLocaleString() }}
                  </p>
                </div>
              </div>
              <div class="hidden sm:flex sm:flex-col sm:items-end text-red-600 dark:text-red-400 font-medium">
                -{{ item.hours_deducted }} hr
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

    <EditStudentSlideOver ref="editSlideOver" />
    <AddPackageSlideOver ref="addPackageSlideOver" />
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
import EditStudentSlideOver from '@/components/students/EditStudentSlideOver.vue'
import AddPackageSlideOver from '@/components/students/AddPackageSlideOver.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import api from '@/api'

const route = useRoute()
const router = useRouter()
const store = useStudentsStore()
const editSlideOver = ref<InstanceType<typeof EditStudentSlideOver> | null>(null)
const addPackageSlideOver = ref<InstanceType<typeof AddPackageSlideOver> | null>(null)
const confirmModal = ref<InstanceType<typeof ConfirmModal> | null>(null)

const statement = ref<any[]>([])

async function fetchStatement(id: string) {
  try {
    const res = await api.get(`/nachhilfe/students/${id}/statement`)
    statement.value = res.data
  } catch (err) {
    console.error('Failed to load statement', err)
  }
}

onMounted(() => {
  if (route.params.id) {
    store.fetchStudent(route.params.id as string)
    fetchStatement(route.params.id as string)
  }
})

watch(() => route.params.id, (newId) => {
  if (newId) {
    store.fetchStudent(newId as string)
    fetchStatement(newId as string)
  }
})

function openEditSlideOver() {
  if (store.currentStudent) {
    editSlideOver.value?.open(store.currentStudent)
  }
}

function openAddPackageSlideOver() {
  if (store.currentStudent) {
    addPackageSlideOver.value?.open(store.currentStudent)
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
