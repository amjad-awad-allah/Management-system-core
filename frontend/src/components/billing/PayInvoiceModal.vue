<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" v-if="isOpen">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="close">
        <div class="absolute inset-0 bg-gray-500 opacity-75 dark:bg-gray-900 dark:opacity-90"></div>
      </div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
      
      <!-- Modal panel -->
      <div class="relative z-10 inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="submitForm">
          <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4" id="modal-title">
              Record Payment
            </h3>
            
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Invoice #{{ invoice?.id.substring(0,8) }}</p>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-300">Total Amount:</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">€{{ invoice?.amount }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-300">Balance Due:</span>
                  <span class="font-medium text-red-600 dark:text-red-400">€{{ invoice?.balance ?? invoice?.amount }}</span>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Amount (€)</label>
                <input type="number" step="0.01" v-model="form.amount" required min="0.01" :max="invoice?.balance ?? invoice?.amount"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                <select v-model="form.method" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                  <option value="cash">Cash</option>
                  <option value="card">Card</option>
                  <option value="bank_transfer">Bank Transfer</option>
                </select>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="submit" :disabled="isSubmitting"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
              <span v-if="isSubmitting">Processing...</span>
              <span v-else>Record Payment</span>
            </button>
            <button type="button" @click="close"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import api from '@/api'
import { useToastStore } from '@/stores/toastStore'

const props = defineProps<{
  isOpen: boolean
  invoice: any | null
}>()

const emit = defineEmits(['close', 'payment-recorded'])
const toast = useToastStore()

const form = ref({
  amount: 0,
  method: 'cash'
})
const isSubmitting = ref(false)

watch(() => props.invoice, (newVal) => {
  if (newVal) {
    form.value.amount = newVal.balance ?? newVal.amount
    form.value.method = 'cash'
  }
})

const close = () => {
  emit('close')
}

const submitForm = async () => {
  if (!props.invoice) return
  isSubmitting.value = true
  
  try {
    await api.post(`/billing/invoices/${props.invoice.id}/pay`, form.value)
    toast.showToast('Payment recorded successfully', 'success')
    emit('payment-recorded')
    close()
  } catch (error: any) {
    toast.showToast(error.response?.data?.message || 'Failed to record payment', 'error')
  } finally {
    isSubmitting.value = false
  }
}
</script>
