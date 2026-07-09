import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref({
    total_students: 0,
    total_teachers: 0,
    active_packages: 0,
    lessons_today: 0,
  })
  
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchStats() {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/dashboard/stats')
      stats.value = response.data.data
    } catch (error: any) {
      console.error('Failed to fetch dashboard stats', error)
      toast.error('Failed to load dashboard statistics')
    } finally {
      isLoading.value = false
    }
  }

  return {
    stats,
    isLoading,
    fetchStats
  }
})
