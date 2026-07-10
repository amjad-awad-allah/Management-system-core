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
    revenue_this_month: 0,
    pending_balances: 0
  })
  
  const upcomingLessons = ref<any[]>([])
  const depletedPackages = ref<any[]>([])
  const recentInvoices = ref<any[]>([])
  const revenueChart = ref<any[]>([])
  const liveStatus = ref({ rooms: [] as any[], teachers: [] as any[] })

  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchStats() {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/dashboard/stats')
      const data = response.data.data
      stats.value = data.stats
      upcomingLessons.value = data.upcoming_lessons
      depletedPackages.value = data.depleted_packages
      recentInvoices.value = data.recent_invoices
      revenueChart.value = data.revenue_chart
      liveStatus.value = data.live_status
    } catch (error: any) {
      console.error('Failed to fetch dashboard stats', error)
      toast.error('Failed to load dashboard statistics')
    } finally {
      isLoading.value = false
    }
  }

  return {
    stats,
    upcomingLessons,
    depletedPackages,
    recentInvoices,
    revenueChart,
    liveStatus,
    isLoading,
    fetchStats
  }
})
