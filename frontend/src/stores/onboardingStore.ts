import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'
import { useAuthStore } from '@/stores/authStore'
import { mainProductTourSteps, type TourStep, type UserRole } from '@/config/onboardingSteps'

export interface UserOnboardingState {
  id?: string
  user_id: string
  tour_key: string
  version: number
  status: 'not_started' | 'in_progress' | 'completed' | 'skipped'
  last_step_id: string | null
  started_at?: string | null
  completed_at?: string | null
  skipped_at?: string | null
}

export const useOnboardingStore = defineStore('onboarding', () => {
  const authStore = useAuthStore()

  const tourKey = ref('main_product_tour')
  const isTourActive = ref(false)
  const isPromptOpen = ref(false)
  const currentStepIndex = ref(0)
  const isLoading = ref(false)
  const tourState = ref<UserOnboardingState | null>(null)
  const isTourEnabled = ref(true)
  const currentAppVersion = ref(1)

  let stepDebounceTimer: any = null

  // Map raw user roles to standardized UserRole types
  const normalizedUserRoles = computed<UserRole[]>(() => {
    const rawRoles = authStore.user?.roles?.map((r: any) => (typeof r === 'string' ? r : r.name)) || []
    return rawRoles.map((r: string) => {
      const lower = r.toLowerCase().replace(/[\s-]+/g, '_')
      if (lower.includes('super') || lower.includes('admin')) return 'super_admin'
      if (lower.includes('manager')) return 'center_manager'
      if (lower.includes('teacher')) return 'teacher'
      if (lower.includes('reception')) return 'receptionist'
      if (lower.includes('parent')) return 'parent'
      return 'student'
    })
  })

  // Filter steps strictly for the current user's role and permissions
  const filteredSteps = computed<TourStep[]>(() => {
    const userRoles = normalizedUserRoles.value
    // If super_admin, show all steps
    if (userRoles.includes('super_admin')) {
      return mainProductTourSteps
    }

    return mainProductTourSteps.filter((step) => {
      // Check role constraint if present
      if (step.roles && step.roles.length > 0) {
        const hasMatchingRole = step.roles.some((r) => userRoles.includes(r))
        if (!hasMatchingRole) return false
      }
      return true
    })
  })

  const currentStep = computed<TourStep | null>(() => {
    if (filteredSteps.value.length === 0) return null
    return filteredSteps.value[currentStepIndex.value] || filteredSteps.value[0]
  })

  const currentStepId = computed<string>(() => currentStep.value?.id || '')

  const progressPercentage = computed<number>(() => {
    if (filteredSteps.value.length === 0) return 0
    return Math.round(((currentStepIndex.value + 1) / filteredSteps.value.length) * 100)
  })

  const totalSteps = computed<number>(() => filteredSteps.value.length)

  // ── Actions ─────────────────────────────────────────────────────────────

  async function fetchTourState() {
    if (!authStore.token) return
    isLoading.value = true
    try {
      const response = await api.get(`/onboarding/${tourKey.value}`)
      isTourEnabled.value = response.data.is_enabled ?? true
      currentAppVersion.value = response.data.current_version ?? 1
      tourState.value = response.data.state

      // If user hasn't started or finished the tour, show the welcome mini modal
      if (
        isTourEnabled.value &&
        tourState.value &&
        tourState.value.status === 'not_started'
      ) {
        isPromptOpen.value = true
      }
    } catch (error) {
      console.warn('Failed to fetch onboarding tour state:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function startTour() {
    isPromptOpen.value = false
    isTourActive.value = true

    // Resume logic: if last_step_id was saved, locate it in filteredSteps
    if (tourState.value?.last_step_id) {
      const idx = filteredSteps.value.findIndex(
        (s) => s.id === tourState.value?.last_step_id
      )
      currentStepIndex.value = idx !== -1 ? idx : 0
    } else {
      currentStepIndex.value = 0
    }

    try {
      const res = await api.post(`/onboarding/${tourKey.value}/start`)
      if (res.data.state) tourState.value = res.data.state
    } catch (err) {
      console.warn('Failed to register tour start:', err)
    }
  }

  function scheduleStepPersistence(stepId: string) {
    if (stepDebounceTimer) clearTimeout(stepDebounceTimer)
    stepDebounceTimer = setTimeout(async () => {
      try {
        await api.post(`/onboarding/${tourKey.value}/step`, { step_id: stepId })
      } catch (err) {
        console.warn('Failed to persist tour step:', err)
      }
    }, 400)
  }

  function nextStep() {
    if (currentStepIndex.value < filteredSteps.value.length - 1) {
      currentStepIndex.value++
      scheduleStepPersistence(currentStepId.value)
    } else {
      completeTour()
    }
  }

  function prevStep() {
    if (currentStepIndex.value > 0) {
      currentStepIndex.value--
      scheduleStepPersistence(currentStepId.value)
    }
  }

  async function skipTour() {
    if (stepDebounceTimer) clearTimeout(stepDebounceTimer)
    isTourActive.value = false
    isPromptOpen.value = false
    try {
      const res = await api.post(`/onboarding/${tourKey.value}/skip`)
      if (res.data.state) tourState.value = res.data.state
    } catch (err) {
      console.warn('Failed to skip tour:', err)
    }
  }

  async function completeTour() {
    if (stepDebounceTimer) clearTimeout(stepDebounceTimer)
    isTourActive.value = false
    isPromptOpen.value = false
    try {
      const res = await api.post(`/onboarding/${tourKey.value}/complete`)
      if (res.data.state) tourState.value = res.data.state
    } catch (err) {
      console.warn('Failed to complete tour:', err)
    }
  }

  async function restartTour() {
    if (stepDebounceTimer) clearTimeout(stepDebounceTimer)
    isLoading.value = true
    try {
      const res = await api.post(`/onboarding/${tourKey.value}/reset`)
      if (res.data.state) tourState.value = res.data.state
      currentStepIndex.value = 0
      isPromptOpen.value = false
      isTourActive.value = true
    } catch (err) {
      console.warn('Failed to reset tour:', err)
    } finally {
      isLoading.value = false
    }
  }

  return {
    tourKey,
    isTourActive,
    isPromptOpen,
    currentStepIndex,
    currentStepId,
    currentStep,
    filteredSteps,
    progressPercentage,
    totalSteps,
    isLoading,
    tourState,
    isTourEnabled,
    fetchTourState,
    startTour,
    nextStep,
    prevStep,
    skipTour,
    completeTour,
    restartTour,
  }
})
