import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export interface Student {
  id: string
  first_name: string
  last_name: string
  date_of_birth?: string
  birth_date?: string
  gender: string
  school?: string
  grade?: number
  parent_name: string
  parent_email?: string
  parent_phone_1?: string
  parent_phone_2?: string
  status: string
  billing_type: string
  packages?: any[]
  lessons?: any[]
  subjects?: any[]
  teachers?: any[]
}

export const useStudentsStore = defineStore('students', () => {
  const students = ref<Student[]>([])
  const currentStudent = ref<Student | null>(null)
  const meta = ref<any>(null)
  const searchQuery = ref('')
  const currentPage = ref(1)
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchStudents(page = 1, search = '') {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/students', {
        params: { page, search }
      })
      students.value = response.data.data
      meta.value = response.data.meta
      currentPage.value = page
      searchQuery.value = search
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchStudent(id: string) {
    isLoading.value = true
    try {
      const response = await api.get(`/nachhilfe/students/${id}`)
      currentStudent.value = response.data.data
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function createStudent(data: any) {
    try {
      const response = await api.post('/nachhilfe/students', data)
      students.value.unshift(response.data.data)
      toast.success('Student created successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function updateStudent(id: string, data: any) {
    try {
      const response = await api.put(`/nachhilfe/students/${id}`, data)
      const index = students.value.findIndex(s => s.id === id)
      if (index !== -1) {
        students.value[index] = response.data.data
      }
      if (currentStudent.value?.id === id) {
        currentStudent.value = response.data.data
      }
      toast.success('Student updated successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function deleteStudent(id: string) {
    try {
      await api.delete(`/nachhilfe/students/${id}`)
      students.value = students.value.filter(s => s.id !== id)
      if (currentStudent.value?.id === id) {
        currentStudent.value = null
      }
      toast.success('Student deleted successfully')
      return true
    } catch (e) {
      toast.error('Failed to delete student')
      return false
    }
  }

  return {
    students,
    currentStudent,
    meta,
    searchQuery,
    currentPage,
    isLoading,
    fetchStudents,
    fetchStudent,
    createStudent,
    updateStudent,
    deleteStudent
  }
})
