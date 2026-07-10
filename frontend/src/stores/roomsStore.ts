import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'
import { useToastStore } from './toastStore'

export interface Room {
  id: string
  name: string
  capacity: number
}

export const useRoomsStore = defineStore('rooms', () => {
  const rooms = ref<Room[]>([])
  const isLoading = ref(false)
  const toast = useToastStore()

  async function fetchRooms() {
    isLoading.value = true
    try {
      const response = await api.get('/nachhilfe/rooms')
      rooms.value = response.data.data
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  async function createRoom(data: any) {
    try {
      const response = await api.post('/nachhilfe/rooms', data)
      rooms.value.unshift(response.data.data)
      toast.success('Room created successfully')
      return true
    } catch (e: any) {
      console.error(e.response?.data)
      toast.error('Validation Error', e.response?.data?.message || 'Failed to create room')
      return false
    }
  }

  async function updateRoom(id: string, data: any) {
    try {
      const response = await api.put(`/nachhilfe/rooms/${id}`, data)
      const index = rooms.value.findIndex(r => r.id === id)
      if (index !== -1) {
        rooms.value[index] = response.data.data
      }
      toast.success('Room updated successfully')
      return true
    } catch (e: any) {
      console.error(e.response?.data)
      toast.error('Validation Error', e.response?.data?.message || 'Failed to update room')
      return false
    }
  }

  async function deleteRoom(id: string) {
    try {
      await api.delete(`/nachhilfe/rooms/${id}`)
      rooms.value = rooms.value.filter(r => r.id !== id)
      toast.success('Room deleted successfully')
      return true
    } catch (e) {
      return false
    }
  }

  return {
    rooms,
    isLoading,
    fetchRooms,
    createRoom,
    updateRoom,
    deleteRoom
  }
})
