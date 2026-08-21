import axios from 'axios'
import { useToastStore } from '../stores/toastStore'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request Interceptor
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  const currentLocale = localStorage.getItem('app_locale') || 'de'
  config.headers['Accept-Language'] = currentLocale === 'de' ? 'de,de-DE;q=0.9,en;q=0.8' : 'en,en-US;q=0.9,de;q=0.8'
  return config
})

// Response Interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const toast = useToastStore()
    const isLoginPage = window.location.pathname === '/login' || window.location.hash.includes('/login')
    
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      if (!isLoginPage) {
        toast.error('Session Expired', 'Your session has expired. Please log in again.')
        window.location.href = '/login'
      }
    } else if (error.response?.status === 500) {
      toast.error('Error', 'Internal Server Error (500)')
    } else if (error.response?.data?.message && !isLoginPage) {
      toast.error('Alert', error.response.data.message)
    } else if (!isLoginPage) {
      toast.error('Error', 'An unexpected error occurred')
    }
    
    return Promise.reject(error)
  }
)

export default api
