import axios from 'axios'
import { useToastStore } from '@/stores/toastStore'

const api = axios.create({
  baseURL: '/api/v1/nachhilfe',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const toast = useToastStore()
    
    if (error.response) {
      if (error.response.status === 409) {
        toast.error('Conflict Detected', error.response.data.message || 'There is a scheduling conflict.')
      } else if (error.response.status === 422) {
        toast.error('Validation Error', error.response.data.message || 'Please check your inputs.')
      } else {
        toast.error('Error', error.response.data.message || 'An unexpected error occurred.')
      }
    } else {
      toast.error('Network Error', 'Could not connect to the server.')
    }
    return Promise.reject(error)
  }
)

export default api
