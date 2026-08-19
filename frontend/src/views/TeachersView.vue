<template>
  <div class="space-y-6 h-full flex flex-col">
    <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Teachers</h1>
      <div class="flex items-center gap-4">
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
            <MagnifyingGlassIcon class="w-4 h-4" />
          </div>
          <input
            v-model="searchInput"
            @input="onSearchInput"
            type="text"
            placeholder="Search teachers..."
            class="block w-64 rounded-xl border border-gray-300 dark:border-gray-700 py-2 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-purple-600 sm:text-sm dark:bg-gray-800 dark:text-white shadow-sm"
            style="padding-left: 2.5rem !important;"
          />
        </div>
        <button @click="openCreateSlideOver" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-medium shadow-sm transition-colors flex items-center gap-2">
          <PlusIcon class="w-5 h-5" />
          Add Teacher
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="glass-panel rounded-2xl flex-1 flex flex-col overflow-hidden relative">
      <div v-if="store.isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
          <thead class="bg-gray-50/50 dark:bg-gray-800/50 backdrop-blur-sm">
            <tr>
              <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">Name</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Contact</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
            <tr v-for="teacher in store.teachers" :key="teacher.id" @click="$router.push(`/teachers/${teacher.id}`)" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer">
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold">
                    {{ teacher.name ? teacher.name[0] : 'T' }}
                  </div>
                  <div class="ml-4">
                    <div class="font-medium text-gray-900 dark:text-white">{{ teacher.name }}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="text-gray-900 dark:text-gray-100">{{ teacher.email }}</div>
                <div class="text-xs">{{ teacher.phone }}</div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <span :class="[
                  teacher.status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-900/30 dark:text-red-400',
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
                ]">
                  {{ teacher.status }}
                </span>
              </td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                <div class="flex items-center justify-end space-x-4">
                  <button @click.stop="openEditSlideOver(teacher)" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300">Edit</button>
                  <button @click.stop="confirmDeleteTeacher(teacher)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="border-t border-gray-200 dark:border-gray-800 px-4 py-3 sm:px-6 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
        <div class="flex-1 flex justify-between sm:hidden">
          <button @click="changePage(store.currentPage - 1)" :disabled="store.currentPage === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">Previous</button>
          <button @click="changePage(store.currentPage + 1)" :disabled="!store.meta || store.currentPage === store.meta.last_page" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">Next</button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Showing
              <span class="font-medium">{{ store.meta?.from || 0 }}</span>
              to
              <span class="font-medium">{{ store.meta?.to || 0 }}</span>
              of
              <span class="font-medium">{{ store.meta?.total || 0 }}</span>
              results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button @click="changePage(store.currentPage - 1)" :disabled="store.currentPage === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                <span class="sr-only">Previous</span>
                &laquo;
              </button>
              <button @click="changePage(store.currentPage + 1)" :disabled="!store.meta || store.currentPage === store.meta.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                <span class="sr-only">Next</span>
                &raquo;
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <CreateTeacherSlideOver ref="createSlideOver" />
    <EditTeacherSlideOver ref="editSlideOver" />
    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/20/solid'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { useTeachersStore } from '@/stores/teachersStore'
import type { Teacher } from '@/stores/teachersStore'
import CreateTeacherSlideOver from '@/components/teachers/CreateTeacherSlideOver.vue'
import EditTeacherSlideOver from '@/components/teachers/EditTeacherSlideOver.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'

const store = useTeachersStore()
const createSlideOver = ref<InstanceType<typeof CreateTeacherSlideOver> | null>(null)
const editSlideOver = ref<InstanceType<typeof EditTeacherSlideOver> | null>(null)
const confirmModal = ref<InstanceType<typeof ConfirmModal> | null>(null)

const searchInput = ref('')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

onMounted(() => {
  store.fetchTeachers()
})

function onSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    store.fetchTeachers(1, searchInput.value)
  }, 300)
}

function changePage(page: number) {
  store.fetchTeachers(page, searchInput.value)
}

function openCreateSlideOver() {
  createSlideOver.value?.open()
}

function openEditSlideOver(teacher: Teacher) {
  editSlideOver.value?.open(teacher)
}

function confirmDeleteTeacher(teacher: Teacher) {
  confirmModal.value?.open(
    'Delete Teacher',
    `Are you sure you want to delete ${teacher.name}? This action cannot be undone.`,
    'Delete',
    'Cancel',
    async () => {
      await store.deleteTeacher(teacher.id)
    }
  )
}
</script>
