import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export const useUsersStore = defineStore('users', () => {
  const users = ref<any[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchUsers() {
    isLoading.value = true
    try {
      const response = await api.get('/users')
      users.value = response.data.data
    } catch (error: any) {
      toast.error('Failed to load users')
    } finally {
      isLoading.value = false
    }
  }

  async function createUser(userData: any) {
    try {
      await api.post('/users', userData)
      toast.success('User created successfully')
      await fetchUsers()
      return true
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to create user')
      return false
    }
  }

  async function updateUser(id: string, userData: any) {
    try {
      await api.put(`/users/${id}`, userData)
      toast.success('User updated successfully')
      await fetchUsers()
      return true
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to update user')
      return false
    }
  }

  async function deleteUser(id: string) {
    try {
      await api.delete(`/users/${id}`)
      toast.success('User deleted successfully')
      await fetchUsers()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to delete user')
    }
  }

  return {
    users,
    isLoading,
    fetchUsers,
    createUser,
    updateUser,
    deleteUser
  }
})
