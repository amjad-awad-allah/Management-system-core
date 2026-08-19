import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'
import {
  startOfWeek,
  endOfWeek,
  format,
  addWeeks,
  subWeeks,
  addDays,
  subDays,
  addMonths,
  subMonths,
  startOfMonth,
  endOfMonth
} from 'date-fns'

export interface CalendarLesson {
  id: string
  teacher_id: string
  room_id: string
  subject_id: string
  type: 'individual' | 'group' | string
  date: string
  start_time: string
  end_time: string
  duration_minutes: number
  status: 'scheduled' | 'completed' | 'cancelled'
  is_in_progress: boolean
  has_conflict: boolean
  conflict_types: string[]
  conflict_details: Array<{ related_lesson_id: string; type: string; message: string }>
  notes?: string
  teacher?: { id: string; name: string }
  room?: { id: string; name: string }
  subject?: { id: string; name: string; code?: string }
  students?: Array<{ id: string; first_name: string; last_name: string }>
}

export interface CalendarHoliday {
  id: string
  source: 'external' | 'custom' | 'override'
  external_id?: string
  type: 'public' | 'school' | 'center'
  name: string
  start_date: string
  end_date: string
  state?: string
  is_active: boolean
}

export interface CalendarSummary {
  total: number
  scheduled: number
  in_progress: number
  completed: number
  cancelled: number
  conflicts: number
}

export interface CalendarMeta {
  timezone: string
  week_starts_on: string
  state: string
}

export const useCalendarStore = defineStore('calendar', () => {
  const viewMode = ref<'week' | 'day' | 'month' | 'resource'>('week')
  const selectedDate = ref<Date>(new Date())
  const state = ref<string>('NW')

  const lessons = ref<CalendarLesson[]>([])
  const holidays = ref<CalendarHoliday[]>([])
  const summary = ref<CalendarSummary>({
    total: 0,
    scheduled: 0,
    in_progress: 0,
    completed: 0,
    cancelled: 0,
    conflicts: 0
  })
  const meta = ref<CalendarMeta>({
    timezone: 'Europe/Berlin',
    week_starts_on: 'monday',
    state: 'NW'
  })

  const isLoading = ref<boolean>(false)
  const error = ref<string | null>(null)

  // Filters
  const filterTeacherId = ref<string>('')
  const filterRoomId = ref<string>('')
  const filterSubjectId = ref<string>('')
  const filterStatus = ref<string>('')

  // Date Range Calculation based on View Mode
  const dateRange = computed(() => {
    if (viewMode.value === 'week' || viewMode.value === 'resource') {
      const start = startOfWeek(selectedDate.value, { weekStartsOn: 1 }) // Monday
      const end = endOfWeek(selectedDate.value, { weekStartsOn: 1 })
      return {
        start: format(start, 'yyyy-MM-dd'),
        end: format(end, 'yyyy-MM-dd')
      }
    } else if (viewMode.value === 'day') {
      const formatted = format(selectedDate.value, 'yyyy-MM-dd')
      return { start: formatted, end: formatted }
    } else {
      // Month
      const start = startOfWeek(startOfMonth(selectedDate.value), { weekStartsOn: 1 })
      const end = endOfWeek(endOfMonth(selectedDate.value), { weekStartsOn: 1 })
      return {
        start: format(start, 'yyyy-MM-dd'),
        end: format(end, 'yyyy-MM-dd')
      }
    }
  })

  async function fetchCalendar() {
    isLoading.value = true
    error.value = null

    try {
      const params: Record<string, string> = {
        start_date: dateRange.value.start,
        end_date: dateRange.value.end,
        state: state.value
      }

      if (filterTeacherId.value) params.teacher_id = filterTeacherId.value
      if (filterRoomId.value) params.room_id = filterRoomId.value
      if (filterSubjectId.value) params.subject_id = filterSubjectId.value
      if (filterStatus.value) params.status = filterStatus.value

      const res = await api.get('/nachhilfe/calendar', { params })

      lessons.value = res.data.lessons || []
      holidays.value = res.data.holidays || []
      if (res.data.summary) summary.value = res.data.summary
      if (res.data.meta) meta.value = res.data.meta
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch calendar data'
    } finally {
      isLoading.value = false
    }
  }

  function nextPeriod() {
    if (viewMode.value === 'week' || viewMode.value === 'resource') {
      selectedDate.value = addWeeks(selectedDate.value, 1)
    } else if (viewMode.value === 'day') {
      selectedDate.value = addDays(selectedDate.value, 1)
    } else {
      selectedDate.value = addMonths(selectedDate.value, 1)
    }
    fetchCalendar()
  }

  function previousPeriod() {
    if (viewMode.value === 'week' || viewMode.value === 'resource') {
      selectedDate.value = subWeeks(selectedDate.value, 1)
    } else if (viewMode.value === 'day') {
      selectedDate.value = subDays(selectedDate.value, 1)
    } else {
      selectedDate.value = subMonths(selectedDate.value, 1)
    }
    fetchCalendar()
  }

  function jumpToToday() {
    selectedDate.value = new Date()
    fetchCalendar()
  }

  /**
   * Reschedule lesson with Optimistic UI update + 422 Conflict Rollback
   */
  async function rescheduleLessonOptimistic(
    lessonId: string,
    newDate: string,
    newStartTime: string,
    newEndTime: string
  ): Promise<{ success: boolean; message?: string }> {
    const lessonIndex = lessons.value.findIndex(l => l.id === lessonId)
    if (lessonIndex === -1) return { success: false }

    const originalLesson = { ...lessons.value[lessonIndex] }

    // 1. Optimistic UI update
    lessons.value[lessonIndex].date = newDate
    lessons.value[lessonIndex].start_time = newStartTime
    lessons.value[lessonIndex].end_time = newEndTime

    try {
      await api.put(`/nachhilfe/lessons/${lessonId}`, {
        teacher_id: originalLesson.teacher_id,
        room_id: originalLesson.room_id,
        subject_id: originalLesson.subject_id,
        type: originalLesson.type,
        date: newDate,
        start_time: newStartTime,
        end_time: newEndTime,
        students: originalLesson.students?.map(s => ({ student_id: s.id })) || []
      })

      // Refresh calendar to sync server conflict states
      fetchCalendar()
      return { success: true }

    } catch (err: any) {
      // 2. Rollback visual position on 422 Conflict or error
      lessons.value[lessonIndex] = originalLesson
      const conflictMsg = err.response?.data?.message || 'Schedule conflict: Reschedule failed.'
      return { success: false, message: conflictMsg }
    }
  }

  return {
    viewMode,
    selectedDate,
    state,
    lessons,
    holidays,
    summary,
    meta,
    isLoading,
    error,
    filterTeacherId,
    filterRoomId,
    filterSubjectId,
    filterStatus,
    dateRange,
    fetchCalendar,
    nextPeriod,
    previousPeriod,
    jumpToToday,
    rescheduleLessonOptimistic
  }
})
