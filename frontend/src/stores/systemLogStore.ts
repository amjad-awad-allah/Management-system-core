import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export interface LogEntry {
  id: string
  timestamp: string
  level: string
  message: string
  channel?: string
  exception_class?: string | null
  file?: string | null
  line?: number | null
  trace?: string | null
  has_trace: boolean
  request_id?: string | null
  correlation_id?: string | null
  job_id?: string | null
  job_class?: string | null
  command?: string | null
  user_id?: string | null
  user_name?: string | null
  ip?: string | null
  method?: string | null
  url?: string | null
  environment?: string
  context?: Record<string, any>
}

export interface LogStatistics {
  critical_24h: number
  errors_only_24h: number
  total_errors_24h: number
  warnings_24h: number
  info_24h: number
  storage_bytes: number
  storage_formatted: string
}

export const useSystemLogStore = defineStore('systemLog', () => {
  const logs = ref<LogEntry[]>([])
  const meta = ref({
    current_page: 1,
    per_page: 50,
    total: 0,
    last_page: 1
  })

  const statistics = ref<LogStatistics>({
    critical_24h: 0,
    errors_only_24h: 0,
    total_errors_24h: 0,
    warnings_24h: 0,
    info_24h: 0,
    storage_bytes: 0,
    storage_formatted: '0 B'
  })

  const period = ref<'24h' | '7d' | '30d' | 'all'>('24h')
  const level = ref<string>('all')
  const search = ref<string>('')
  const isLoading = ref(false)
  const isExporting = ref(false)
  const isClearing = ref(false)
  const toast = useToastStore()

  async function fetchLogs(page = 1) {
    isLoading.value = true
    try {
      const params: Record<string, any> = {
        period: period.value,
        level: level.value,
        page,
        per_page: meta.value.per_page
      }

      if (search.value.trim()) {
        params.search = search.value.trim()
      }

      const response = await api.get('/system-logs', { params })
      logs.value = response.data.data || []
      if (response.data.meta) {
        meta.value = response.data.meta
      }
      if (response.data.statistics) {
        statistics.value = response.data.statistics
      }
    } catch (error: any) {
      console.error('Failed to fetch system logs', error)
      toast.error('Fehler beim Laden der System-Logs.')
    } finally {
      isLoading.value = false
    }
  }

  async function exportLogs(format: 'txt' | 'json' = 'txt') {
    isExporting.value = true
    try {
      const params: Record<string, any> = {
        period: period.value,
        level: level.value,
        format
      }

      if (search.value.trim()) {
        params.search = search.value.trim()
      }

      const response = await api.get('/system-logs/export', {
        params,
        responseType: 'blob'
      })

      const blob = new Blob([response.data], {
        type: format === 'json' ? 'application/json' : 'text/plain'
      })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19)
      link.setAttribute('download', `system_logs_${period.value}_${timestamp}.${format === 'json' ? 'json' : 'log'}`)
      document.body.appendChild(link)
      link.click()
      link.remove()

      toast.success('Log-Export erfolgreich heruntergeladen.')
    } catch (error: any) {
      console.error('Failed to export system logs', error)
      toast.error('Fehler beim Exportieren der Logs.')
    } finally {
      isExporting.value = false
    }
  }

  async function clearLogs(scope = 'all') {
    isClearing.value = true
    try {
      const response = await api.post('/system-logs/clear', { scope })
      toast.success('System-Logs erfolgreich geleert.')
      await fetchLogs(1)
      return response.data
    } catch (error: any) {
      console.error('Failed to clear system logs', error)
      toast.error(error.response?.data?.message || 'Fehler beim Leeren der Logs.')
      return false
    } finally {
      isClearing.value = false
    }
  }

  return {
    logs,
    meta,
    statistics,
    period,
    level,
    search,
    isLoading,
    isExporting,
    isClearing,
    fetchLogs,
    exportLogs,
    clearLogs
  }
})
