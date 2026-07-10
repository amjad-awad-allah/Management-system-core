<template>
  <div>
    <div class="space-y-6 h-full flex flex-col">
      <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Invoices</h1>
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
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Reference</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Amount</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Balance</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Due Date</th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
            <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6 text-gray-900 dark:text-gray-100">
                {{ invoice.id.substring(0, 8) }}...
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="font-medium text-gray-900 dark:text-gray-100">{{ invoice.reference_type }}</div>
                <div class="text-xs">ID: {{ invoice.reference_id.substring(0, 8) }}</div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                €{{ invoice.amount }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-medium" :class="invoice.balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                €{{ invoice.balance ?? invoice.amount }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <span :class="[
                  invoice.status === 'paid' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : 
                  invoice.status === 'partially_paid' ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400' :
                  'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400',
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
                ]">
                  {{ invoice.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                {{ new Date(invoice.due_date).toLocaleDateString() }}
              </td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-3">
                <a :href="`http://localhost:8000/api/v1/billing/invoices/${invoice.id}/pdf`" target="_blank" class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:ring-indigo-400/30 dark:hover:bg-indigo-900/50 transition-colors">
                  PDF
                </a>
                <button v-if="invoice.status !== 'paid'" @click="openPayModal(invoice)" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-400/30 dark:hover:bg-purple-900/50 transition-colors">
                  Pay
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <PayInvoiceModal 
    :is-open="isPayModalOpen"
    :invoice="selectedInvoice"
    @close="isPayModalOpen = false"
    @payment-recorded="fetchInvoices"
  />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useToastStore } from '@/stores/toastStore'
import PayInvoiceModal from '@/components/billing/PayInvoiceModal.vue'

interface Invoice {
  id: string
  reference_type: string
  reference_id: string
  amount: number
  balance: number
  status: string
  due_date: string
  items?: any[]
  payments?: any[]
}

const invoices = ref<Invoice[]>([])
const isLoading = ref(true)
const toast = useToastStore()

const isPayModalOpen = ref(false)
const selectedInvoice = ref<Invoice | null>(null)

const openPayModal = (invoice: Invoice) => {
  selectedInvoice.value = invoice
  isPayModalOpen.value = true
}

const fetchInvoices = async () => {
  isLoading.value = true
  try {
    const response = await api.get('/billing/invoices')
    invoices.value = response.data
  } catch (error: any) {
    console.error(error)
    toast.showToast('Failed to load invoices', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchInvoices()
})
</script>
