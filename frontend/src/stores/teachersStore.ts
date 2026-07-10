import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export interface Teacher {
  id: string
  name: string
  email: string
  phone: string
  status: string
  subjects?: any[]
}

export const useTeachersStore = defineStore('teachers', () => {
  const teachers = ref<Teacher[]>([])
  const meta = ref<any>(null)
  const searchQuery = ref('')
  const currentPage = ref(1)
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchTeachers(page = 1, search = '') {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/teachers', {
        params: { page, search }
      })
      teachers.value = response.data.data
      meta.value = response.data.meta
      currentPage.value = page
      searchQuery.value = search
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function createTeacher(data: any) {
    try {
      const response = await api.post('/nachhilfe/teachers', data)
      teachers.value.unshift(response.data.data)
      toast.success('Teacher created successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function updateTeacher(id: string, data: any) {
    try {
      const response = await api.put(`/nachhilfe/teachers/${id}`, data)
      const index = teachers.value.findIndex(t => t.id === id)
      if (index !== -1) {
        teachers.value[index] = response.data.data
      }
      toast.success('Teacher updated successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function deleteTeacher(id: string) {
    try {
      await api.delete(`/nachhilfe/teachers/${id}`)
      teachers.value = teachers.value.filter(t => t.id !== id)
      toast.success('Teacher deleted successfully')
      return true
    } catch (e: any) {
      console.error(e.response?.data || e)
      toast.error('Delete Error', e.response?.data?.message || 'Failed to delete teacher')
      return false
    }
  }

  return {
    teachers,
    meta,
    searchQuery,
    currentPage,
    isLoading,
    fetchTeachers,
    createTeacher,
    updateTeacher,
    deleteTeacher
  }
})
