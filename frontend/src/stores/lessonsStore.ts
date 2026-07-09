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
}

export const useLessonsStore = defineStore('lessons', () => {
  const lessons = ref<Lesson[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchLessons() {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/lessons')
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
    } catch (e) {
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
    markAttendance
  }
})
