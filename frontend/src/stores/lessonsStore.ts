import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export interface Lesson {
  id: string
  teacher_id: string
  room_id: string
  subject_id: string
  type: 'individual' | 'group'
  date: string
  start_time: string
  end_time: string
  status: string
  notes?: string
  students: any[]
  teacher?: { id: string; name: string }
  room?: { id: string; name: string }
  subject?: { id: string; name: string }
  schedule_template_id?: string | null
}

export const useLessonsStore = defineStore('lessons', () => {
  const lessons = ref<Lesson[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchLessons(params: Record<string, any> = {}) {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/lessons', { params })
      lessons.value = response.data.data
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function bookLesson(data: any) {
    // Optimistic UI could be implemented here by pushing to `lessons.value`
    // but due to IDs and relations, it's safer to re-fetch on success or push the returned data
    try {
      const response = await api.post('/nachhilfe/lessons', data)
      lessons.value.push(response.data.data)
      toast.success('Lesson booked successfully')
      return true
    } catch (e: any) {
      if (e.response?.status === 409) {
        toast.error(e.response.data.message || 'Scheduling conflict detected')
      } else {
        toast.error('Failed to schedule lesson')
      }
      return false
    }
  }

  async function updateLesson(id: string, data: any, updateSeries = false) {
    try {
      const response = await api.put(`/nachhilfe/lessons/${id}`, { ...data, update_series: updateSeries })
      const updatedLesson = response.data.data
      
      if (updateSeries) {
        // Since multiple lessons are updated, refetch them all to update calendar
        await fetchLessons()
      } else {
        const index = lessons.value.findIndex(l => l.id === id)
        if (index !== -1) {
          lessons.value[index] = updatedLesson
        }
      }
      toast.success('Lesson updated successfully')
      return true
    } catch (e: any) {
      if (e.response?.status === 409) {
        toast.error(e.response.data.message || 'Scheduling conflict detected')
      } else {
        toast.error('Failed to update lesson')
      }
      return false
    }
  }
  
  async function markAttendance(lessonStudentId: string, data: { status: string, note?: string }) {
    try {
      await api.post(`/nachhilfe/lesson-students/${lessonStudentId}/attendance`, data)
      toast.success('Attendance marked successfully')
      return true
    } catch (e) {
      return false
    }
  }

  return {
    lessons,
    isLoading,
    fetchLessons,
    bookLesson,
    updateLesson,
    markAttendance
  }
})
