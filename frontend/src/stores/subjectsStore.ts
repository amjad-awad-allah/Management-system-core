import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export interface Subject {
  id: string
  name: string
  description?: string
  is_active: boolean
}

export const useSubjectsStore = defineStore('subjects', () => {
  const subjects = ref<Subject[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchSubjects() {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/subjects')
      subjects.value = response.data.data
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function createSubject(data: any) {
    try {
      const response = await api.post('/nachhilfe/subjects', data)
      subjects.value.unshift(response.data.data)
      toast.success('Subject created successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function updateSubject(id: string, data: any) {
    try {
      const response = await api.put(`/nachhilfe/subjects/${id}`, data)
      const index = subjects.value.findIndex(s => s.id === id)
      if (index !== -1) {
        subjects.value[index] = response.data.data
      }
      toast.success('Subject updated successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function deleteSubject(id: string) {
    try {
      await api.delete(`/nachhilfe/subjects/${id}`)
      subjects.value = subjects.value.filter(s => s.id !== id)
      toast.success('Subject deleted successfully')
      return true
    } catch (e) {
      return false
    }
  }

  return {
    subjects,
    isLoading,
    fetchSubjects,
    createSubject,
    updateSubject,
    deleteSubject
  }
})
