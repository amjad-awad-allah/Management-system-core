import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'info' | 'warning'

export interface ToastMessage {
  id: string
  type: ToastType
  title: string
  message?: string
  duration?: number
}

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<ToastMessage[]>([])

  function addToast(toast: Omit<ToastMessage, 'id'>) {
    const id = Math.random().toString(36).substring(2, 9)
    const newToast = { ...toast, id, duration: toast.duration || 3000 }
    toasts.value.push(newToast)

    if (newToast.duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, newToast.duration)
    }
  }

  function removeToast(id: string) {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  function success(title: string, message?: string) {
    addToast({ type: 'success', title, message })
  }

  function error(title: string, message?: string) {
    addToast({ type: 'error', title, message, duration: 5000 })
  }

  return { toasts, addToast, removeToast, success, error }
})
