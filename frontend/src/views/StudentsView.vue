<template>
  <div class="space-y-6 h-full flex flex-col">
    <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
        {{ $t('students.title') }}
      </h1>
      <div class="flex items-center gap-4">
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
            <MagnifyingGlassIcon class="w-4 h-4" />
          </div>
          <input
            v-model="searchInput"
            @input="onSearchInput"
            type="text"
            :placeholder="$t('students.searchPlaceholder')"
            class="block w-64 rounded-xl border border-gray-300 dark:border-gray-700 py-2 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-purple-600 sm:text-sm dark:bg-gray-800 dark:text-white shadow-sm"
            style="padding-left: 2.5rem !important;"
          />
        </div>
        <button @click="openCreateSlideOver" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-medium shadow-sm transition-colors flex items-center gap-2 cursor-pointer">
          <PlusIcon class="w-5 h-5" />
          {{ $t('students.addStudent') }}
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
              <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">
                {{ $t('students.name') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('students.parent') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('students.billingType') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('common.status') }}
              </th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                <span class="sr-only">{{ $t('common.actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
            <tr v-for="student in store.students" :key="student.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer" @click="$router.push(`/students/${student.id}`)">
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold">
                    {{ student.first_name ? student.first_name[0] : '' }}{{ student.last_name ? student.last_name[0] : '' }}
                  </div>
                  <div class="ml-4">
                    <div class="font-medium text-gray-900 dark:text-white">{{ student.first_name }} {{ student.last_name }}</div>
                    <div class="text-gray-500 dark:text-gray-400">{{ student.gender }}</div>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="text-gray-900 dark:text-gray-100">{{ student.parent_name }}</div>
                <div class="text-xs">{{ student.parent_phone_1 }}</div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300">
                  {{ student.billing_type }}
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <span :class="[
                  student.status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-900/30 dark:text-red-400',
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
                ]">
                  {{ student.status }}
                </span>
              </td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                <RouterLink :to="`/students/${student.id}`" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300">
                  {{ $t('students.viewDetails') }}
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="border-t border-gray-200 dark:border-gray-800 px-4 py-3 sm:px-6 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
        <div class="flex-1 flex justify-between sm:hidden">
          <button @click="changePage(store.currentPage - 1)" :disabled="store.currentPage === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 cursor-pointer">
            {{ $t('common.previous') }}
          </button>
          <button @click="changePage(store.currentPage + 1)" :disabled="!store.meta || store.currentPage === store.meta.last_page" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 cursor-pointer">
            {{ $t('common.next') }}
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              {{ $t('common.pagination', { from: store.meta?.from || 0, to: store.meta?.to || 0, total: store.meta?.total || 0 }) }}
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button @click="changePage(store.currentPage - 1)" :disabled="store.currentPage === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 cursor-pointer">
                <span class="sr-only">{{ $t('common.previous') }}</span>
                &laquo;
              </button>
              <button @click="changePage(store.currentPage + 1)" :disabled="!store.meta || store.currentPage === store.meta.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 cursor-pointer">
                <span class="sr-only">{{ $t('common.next') }}</span>
                &raquo;
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <CreateStudentSlideOver ref="createSlideOver" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/20/solid'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { useStudentsStore } from '@/stores/studentsStore'
import CreateStudentSlideOver from '@/components/students/CreateStudentSlideOver.vue'

const store = useStudentsStore()
const createSlideOver = ref<InstanceType<typeof CreateStudentSlideOver> | null>(null)

const searchInput = ref('')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

onMounted(() => {
  store.fetchStudents()
})

function onSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    store.fetchStudents(1, searchInput.value)
  }, 300)
}

function changePage(page: number) {
  store.fetchStudents(page, searchInput.value)
}

function openCreateSlideOver() {
  createSlideOver.value?.open()
}
</script>
