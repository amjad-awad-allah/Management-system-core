import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const user = ref<any>(null)
  
  const setToken = (newToken: string) => {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
  }
  
  const setUser = (userData: any) => {
    user.value = userData
  }
  
  const logout = () => {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
  }

  const login = async (credentials: any) => {
    try {
      const response = await api.post('/login', credentials)
      if (response.data.access_token) {
        setToken(response.data.access_token)
        setUser(response.data.user)
        return true
      }
      return false
    } catch (error) {
      console.error('Login failed', error)
      throw error
    }
  }

  const fetchUser = async () => {
    if (!token.value) return false
    
    try {
      const response = await api.get('/user')
      setUser(response.data)
      return true
    } catch (error) {
      logout()
      return false
    }
  }

  const updateProfile = async (data: { name: string; email: string }) => {
    const response = await api.patch('/user/profile', data)
    if (response.data.user) {
      setUser(response.data.user)
    }
    return response.data
  }

  const updatePassword = async (data: { current_password: string; new_password: string; new_password_confirmation: string }) => {
    const response = await api.patch('/user/password', data)
    return response.data
  }

  return {
    token,
    user,
    login,
    logout,
    fetchUser,
    updateProfile,
    updatePassword,
  }
})
