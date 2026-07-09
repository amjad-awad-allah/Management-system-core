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
  return config
})

// Response Interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const toast = useToastStore()
    
    if (error.response?.status === 401) {
      toast.error('تسجيل الدخول', 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مجدداً.')
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    } else if (error.response?.status === 500) {
      toast.error('خطأ', 'حدث خطأ في الخادم (500)')
    } else if (error.response?.data?.message) {
      toast.error('تنبيه', error.response.data.message)
    } else {
      toast.error('خطأ', 'حدث خطأ غير متوقع')
    }
    
    return Promise.reject(error)
  }
)

export default api
