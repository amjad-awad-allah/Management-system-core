<template>
  <div>
    <div class="space-y-6 h-full flex flex-col">
      <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Payrolls</h1>
    </div>

    <!-- Table -->
    <div class="glass-panel rounded-2xl flex-1 flex flex-col overflow-hidden relative">
      <div v-if="isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
          <thead class="bg-gray-50/50 dark:bg-gray-800/50 backdrop-blur-sm">
            <tr>
              <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">ID</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Teacher</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Month</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Amount</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
            <tr v-for="payroll in payrolls" :key="payroll.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6 text-gray-900 dark:text-gray-100">
                <span class="font-medium">{{ payroll.id.substring(0, 8) }}...</span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="font-medium text-gray-900 dark:text-gray-100">{{ payroll.teacher?.name }}</div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                {{ payroll.month }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                €{{ payroll.total_amount }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <span :class="[
                  payroll.status === 'Paid' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : 
                  'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400',
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset tracking-wide'
                ]">
                  {{ payroll.status }}
                </span>
              </td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-3">
                <button v-if="payroll.status !== 'Paid'" @click="markAsPaid(payroll)" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-400/30 dark:hover:bg-purple-900/50 transition-colors">
                  Mark Paid
                </button>
              </td>
            </tr>
            <tr v-if="payrolls.length === 0 && !isLoading">
              <td colspan="6" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                No payrolls found.
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination Placeholder -->
        <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-800" v-if="payrolls.length > 0">
           <span class="text-sm text-gray-500">Showing page {{ currentPage }} of {{ totalPages }}</span>
           <div class="space-x-2">
             <button :disabled="currentPage === 1" @click="fetchPayrolls(currentPage - 1)" class="px-3 py-1 text-sm border rounded text-gray-600 disabled:opacity-50">Previous</button>
             <button :disabled="currentPage === totalPages" @click="fetchPayrolls(currentPage + 1)" class="px-3 py-1 text-sm border rounded text-gray-600 disabled:opacity-50">Next</button>
           </div>
        </div>
      </div>
    </div>
  </div>
  
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useToastStore } from '@/stores/toastStore'

interface Teacher {
  id: string
  name: string
}

interface Payroll {
  id: string
  teacher_id: string
  month: string
  total_amount: number
  status: string
  teacher?: Teacher
}

const payrolls = ref<Payroll[]>([])
const isLoading = ref(true)
const toast = useToastStore()
const currentPage = ref(1)
const totalPages = ref(1)

const fetchPayrolls = async (page = 1) => {
  isLoading.value = true
  try {
    const response = await api.get(`/nachhilfe/payrolls?page=${page}`)
    payrolls.value = response.data.data
    currentPage.value = response.data.current_page
    totalPages.value = response.data.last_page
  } catch (error: any) {
    console.error(error)
    toast.error('Failed to load payrolls')
  } finally {
    isLoading.value = false
  }
}

const markAsPaid = async (payroll: Payroll) => {
  try {
    await api.patch(`/nachhilfe/payrolls/${payroll.id}/status`, { status: 'Paid' })
    toast.success('Payroll marked as paid')
    fetchPayrolls(currentPage.value)
  } catch (error: any) {
    console.error(error)
    toast.error('Failed to update payroll status')
  }
}

onMounted(() => {
  fetchPayrolls()
})
</script>
