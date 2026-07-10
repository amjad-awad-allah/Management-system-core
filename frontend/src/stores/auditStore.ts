import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export const useAuditStore = defineStore('audit', () => {
  const logs = ref<any[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchLogs() {
    isLoading.value = true
    try {
      const response = await api.get('/audit-logs')
      logs.value = response.data.data
    } catch (error: any) {
      toast.error('Failed to load audit logs')
    } finally {
      isLoading.value = false
    }
  }

  return {
    logs,
    isLoading,
    fetchLogs
  }
})
