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

  return {
    token,
    user,
    login,
    logout,
    fetchUser
  }
})
