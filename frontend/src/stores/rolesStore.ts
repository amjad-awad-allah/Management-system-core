import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export const useRolesStore = defineStore('roles', () => {
  const roles = ref<any[]>([])
  const allPermissions = ref<any[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchRoles() {
    isLoading.value = true
    try {
      const response = await api.get('/roles')
      roles.value = response.data.data
    } catch (error: any) {
      toast.error('Failed to load roles')
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPermissions() {
    try {
      const response = await api.get('/permissions')
      allPermissions.value = response.data.data
    } catch (error: any) {
      console.error('Failed to load permissions')
    }
  }

  async function createRole(roleData: any) {
    try {
      await api.post('/roles', roleData)
      toast.success('Role created successfully')
      await fetchRoles()
      return true
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to create role')
      return false
    }
  }

  async function updateRole(id: string, roleData: any) {
    try {
      await api.put(`/roles/${id}`, roleData)
      toast.success('Role updated successfully')
      await fetchRoles()
      return true
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to update role')
      return false
    }
  }

  async function deleteRole(id: string) {
    try {
      await api.delete(`/roles/${id}`)
      toast.success('Role deleted successfully')
      await fetchRoles()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to delete role')
    }
  }

  return {
    roles,
    allPermissions,
    isLoading,
    fetchRoles,
    fetchPermissions,
    createRole,
    updateRole,
    deleteRole
  }
})
