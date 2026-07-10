import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export interface Package {
  id: string
  name: string
  description?: string
  hours: number
  price: number
  is_active: boolean
}

export const usePackagesStore = defineStore('packages', () => {
  const packages = ref<Package[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchPackages() {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/packages')
      packages.value = response.data
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function createPackage(data: any) {
    try {
      const response = await api.post('/nachhilfe/packages', data)
      packages.value.unshift(response.data)
      toast.success('Package created successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function updatePackage(id: string, data: any) {
    try {
      const response = await api.put(`/nachhilfe/packages/${id}`, data)
      const index = packages.value.findIndex(p => p.id === id)
      if (index !== -1) {
        packages.value[index] = response.data
      }
      toast.success('Package updated successfully')
      return true
    } catch (e) {
      return false
    }
  }

  async function deletePackage(id: string) {
    try {
      await api.delete(`/nachhilfe/packages/${id}`)
      packages.value = packages.value.filter(p => p.id !== id)
      toast.success('Package deleted successfully')
      return true
    } catch (e) {
      toast.error('Failed to delete package')
      return false
    }
  }

  async function assignPackageToStudent(studentId: string, data: any) {
    try {
      const response = await api.post('/nachhilfe/student-packages', {
        student_id: studentId,
        ...data
      })
      toast.success('Package assigned to student successfully')
      return response.data
    } catch (e) {
      return false
    }
  }

  return {
    packages,
    isLoading,
    fetchPackages,
    createPackage,
    updatePackage,
    deletePackage,
    assignPackageToStudent
  }
})
