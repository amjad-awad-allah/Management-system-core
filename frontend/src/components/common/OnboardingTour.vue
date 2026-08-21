<template>
  <div>
    <!-- ── 1. Mini Welcome Prompt Modal ──────────────────────────────────────── -->
    <Transition
      enter-active-class="transition ease-out duration-300 transform"
      enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
      enter-to-class="opacity-100 translate-y-0 sm:scale-100"
      leave-active-class="transition ease-in duration-200 transform"
      leave-from-class="opacity-100 translate-y-0 sm:scale-100"
      leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
      <div
        v-if="store.isPromptOpen"
        class="fixed bottom-6 right-6 z-50 max-w-md w-full p-5 rounded-3xl bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border border-purple-500/30 dark:border-purple-400/20 shadow-2xl shadow-purple-500/10 ring-1 ring-black/5"
        role="dialog"
        aria-labelledby="prompt-title"
        aria-modal="false"
      >
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white shrink-0 shadow-lg shadow-purple-500/25">
            <SparklesIcon class="w-6 h-6 animate-pulse" />
          </div>
          <div class="flex-1 min-w-0">
            <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-0.5">
              {{ $t('onboarding.welcomeTag') }}
            </span>
            <h3 id="prompt-title" class="text-base font-bold text-gray-900 dark:text-white">
              {{ $t('onboarding.promptTitle') }}
            </h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">
              {{ $t('onboarding.promptDesc') }}
            </p>
            <div class="flex items-center gap-2.5 mt-4">
              <button
                @click="handleStartTour"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 active:scale-95 text-white text-xs font-bold transition-all shadow-md shadow-purple-500/20 cursor-pointer flex items-center gap-1.5"
              >
                <span>{{ $t('onboarding.startTour') }}</span>
                <ArrowRightIcon class="w-3.5 h-3.5" />
              </button>
              <button
                @click="handleMaybeLater"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
              >
                {{ $t('onboarding.maybeLater') }}
              </button>
            </div>
          </div>
          <button
            @click="handleMaybeLater"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 -mr-1 -mt-1 rounded-lg"
            title="Dismiss"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </Transition>

    <!-- ── 2. Full Spotlight Backdrop & Floating Popover ────────────────────── -->
    <div
      v-if="store.isTourActive && store.currentStep"
      class="fixed inset-0 z-[100] pointer-events-none"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="'tour-title-' + store.currentStepId"
      ref="tourDialogRef"
      tabindex="-1"
    >
      <!-- Darkened Mask Overlay (Used only when in centered modal mode) -->
      <div
        v-if="!isTargetActive || !spotlightRect"
        class="fixed inset-0 bg-gray-950/65 backdrop-blur-[2px] transition-opacity duration-300 pointer-events-auto"
        @click="handleBackdropClick"
      />

      <!-- Target Element Spotlight Cutout & Glowing Ring -->
      <div
        v-if="spotlightRect && isTargetActive"
        class="fixed pointer-events-auto transition-all duration-300 ease-out z-[101] rounded-2xl ring-2 ring-purple-500"
        :style="{
          top: spotlightRect.top + 'px',
          left: spotlightRect.left + 'px',
          width: spotlightRect.width + 'px',
          height: spotlightRect.height + 'px',
          boxShadow: '0 0 0 9999px rgba(3, 7, 18, 0.65), 0 0 35px rgba(168, 85, 247, 0.45)',
        }"
        @click="handleBackdropClick"
      />

      <!-- Popover Card -->
      <div
        class="fixed pointer-events-auto transition-all duration-300 ease-out z-[102] max-w-sm sm:max-w-md w-[calc(100vw-2rem)]"
        :style="popoverStyle"
      >
        <div class="rounded-3xl bg-white dark:bg-gray-900 p-6 border border-purple-500/30 dark:border-purple-400/20 shadow-2xl shadow-purple-500/20 ring-1 ring-black/10 overflow-hidden relative">
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-28 h-28 bg-purple-500/15 rounded-full blur-2xl pointer-events-none" />

          <!-- Header -->
          <div class="flex items-center justify-between gap-3 relative z-10">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-xs shrink-0 shadow-inner">
                {{ store.currentStepIndex + 1 }}
              </div>
              <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                {{ $t('onboarding.stepCounter', { current: store.currentStepIndex + 1, total: store.totalSteps }) }}
              </span>
            </div>
            <button
              @click="store.skipTour"
              class="p-1.5 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
              title="Skip tour (Esc)"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Progress Bar Line -->
          <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full mt-3 overflow-hidden">
            <div
              class="bg-gradient-to-r from-purple-600 to-indigo-600 h-full rounded-full transition-all duration-300 ease-out"
              :style="{ width: store.progressPercentage + '%' }"
            />
          </div>

          <!-- Content Body -->
          <div class="mt-4 relative z-10">
            <h3
              :id="'tour-title-' + store.currentStepId"
              class="text-base font-bold text-gray-900 dark:text-white leading-snug"
            >
              {{ $t(store.currentStep.titleKey) }}
            </h3>
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mt-2 leading-relaxed">
              {{ $t(store.currentStep.descriptionKey) }}
            </p>
          </div>

          <!-- Controls Footer -->
          <div class="flex items-center justify-between gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 relative z-10">
            <button
              @click="store.skipTour"
              class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer transition-colors"
            >
              {{ $t('onboarding.skip') }}
            </button>

            <div class="flex items-center gap-2">
              <button
                v-if="store.currentStepIndex > 0"
                @click="store.prevStep"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer"
              >
                &larr; {{ $t('onboarding.back') }}
              </button>

              <button
                @click="handleNext"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 active:scale-95 text-white text-xs font-bold transition-all shadow-md shadow-purple-500/25 cursor-pointer flex items-center gap-1.5"
              >
                <span>{{ isLastStep ? $t('onboarding.finish') : ($t('onboarding.next') + ' →') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useOnboardingStore } from '@/stores/onboardingStore'
import {
  SparklesIcon,
  XMarkIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const store = useOnboardingStore()

const tourDialogRef = ref<HTMLElement | null>(null)
const spotlightRect = ref<{ top: number; left: number; width: number; height: number } | null>(null)
const popoverPosition = ref<{ top: number; left: number }>({ top: 0, left: 0 })
const isTargetActive = ref(false)

const isLastStep = computed(() => store.currentStepIndex >= store.totalSteps - 1)

// ── Visibility & Target Resolution Engine ───────────────────────────────

function isElementVisible(el: HTMLElement): boolean {
  if (!el || el.offsetParent === null) return false
  const style = window.getComputedStyle(el)
  if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
    return false
  }
  const rect = el.getBoundingClientRect()
  return rect.width > 0 && rect.height > 0
}

async function resolveTargetAndPosition() {
  if (!store.isTourActive || !store.currentStep) return

  const step = store.currentStep

  // 1. Route Synchronization: check if we need to navigate
  if (step.route && route.path !== step.route) {
    await router.push(step.route)
    await nextTick()
    // Give short delay for transitions and dynamic components to mount
    await new Promise((resolve) => setTimeout(resolve, 200))
  }

  await nextTick()

  // 2. Resolve Target Element
  let targetEl: HTMLElement | null = null
  if (step.target && step.targetBehavior === 'spotlight') {
    targetEl = document.querySelector(step.target) as HTMLElement
  }

  const isVisible = targetEl ? isElementVisible(targetEl) : false

  // 3. Fallback logic based on targetBehavior
  if (!targetEl || !isVisible) {
    if (step.targetBehavior === 'skip') {
      store.nextStep()
      return
    }
    // Fall back to center positioning
    isTargetActive.value = false
    spotlightRect.value = null
    centerPopover()
    return
  }

  // 4. Compute Spotlight Rect (with 8px padding)
  const rect = targetEl.getBoundingClientRect()
  const padding = 8
  spotlightRect.value = {
    top: Math.max(0, rect.top - padding),
    left: Math.max(0, rect.left - padding),
    width: rect.width + padding * 2,
    height: rect.height + padding * 2,
  }
  isTargetActive.value = true

  // 5. Compute Popover Position with Viewport Collision Flipping
  computePopoverPosition(rect, step.placement || 'bottom')
}

function centerPopover() {
  const popoverWidth = Math.min(window.innerWidth - 32, 420)
  const popoverHeight = 260
  popoverPosition.value = {
    top: Math.max(20, (window.innerHeight - popoverHeight) / 2),
    left: Math.max(16, (window.innerWidth - popoverWidth) / 2),
  }
}

function computePopoverPosition(targetRect: DOMRect, preferredPlacement: string) {
  const gap = 16
  const popoverWidth = Math.min(window.innerWidth - 32, 420)
  const popoverHeight = 240
  const vw = window.innerWidth
  const vh = window.innerHeight

  let placement = preferredPlacement
  let top = 0
  let left = 0

  // Check collision and auto-flip placement
  if (placement === 'bottom') {
    if (targetRect.bottom + gap + popoverHeight > vh && targetRect.top - gap - popoverHeight > 0) {
      placement = 'top'
    }
  } else if (placement === 'top') {
    if (targetRect.top - gap - popoverHeight < 0 && targetRect.bottom + gap + popoverHeight < vh) {
      placement = 'bottom'
    }
  } else if (placement === 'right') {
    if (targetRect.right + gap + popoverWidth > vw && targetRect.left - gap - popoverWidth > 0) {
      placement = 'left'
    }
  } else if (placement === 'left') {
    if (targetRect.left - gap - popoverWidth < 0 && targetRect.right + gap + popoverWidth < vw) {
      placement = 'right'
    }
  }

  // Apply coordinates based on resolved placement
  switch (placement) {
    case 'top':
      top = targetRect.top - popoverHeight - gap
      left = targetRect.left + (targetRect.width - popoverWidth) / 2
      break
    case 'bottom':
      top = targetRect.bottom + gap
      left = targetRect.left + (targetRect.width - popoverWidth) / 2
      break
    case 'left':
      top = targetRect.top + (targetRect.height - popoverHeight) / 2
      left = targetRect.left - popoverWidth - gap
      break
    case 'right':
      top = targetRect.top + (targetRect.height - popoverHeight) / 2
      left = targetRect.right + gap
      break
    case 'center':
    default:
      centerPopover()
      return
  }

  // Viewport containment bounding clamp
  left = Math.max(16, Math.min(left, vw - popoverWidth - 16))
  top = Math.max(16, Math.min(top, vh - popoverHeight - 16))

  popoverPosition.value = { top, left }
}

const popoverStyle = computed(() => {
  return {
    top: `${popoverPosition.value.top}px`,
    left: `${popoverPosition.value.left}px`,
  }
})

// ── Handlers ────────────────────────────────────────────────────────────

function handleStartTour() {
  store.startTour()
}

function handleMaybeLater() {
  store.skipTour()
}

function handleNext() {
  store.nextStep()
}

function handleBackdropClick() {
  // Clicking outside does not close tour prematurely, but advances or stays focused
}

// ── Keyboard & Accessibility Guard ──────────────────────────────────────

function handleKeydown(e: KeyboardEvent) {
  if (!store.isTourActive) return

  // Input Shield: do not capture arrows if user is focused inside an input/textarea
  const target = e.target as HTMLElement
  if (
    target &&
    (target.tagName === 'INPUT' ||
      target.tagName === 'TEXTAREA' ||
      target.tagName === 'SELECT' ||
      target.isContentEditable)
  ) {
    return
  }

  if (e.key === 'ArrowRight') {
    e.preventDefault()
    store.nextStep()
  } else if (e.key === 'ArrowLeft') {
    e.preventDefault()
    store.prevStep()
  } else if (e.key === 'Escape') {
    e.preventDefault()
    store.skipTour()
  }
}

function handleResizeOrScroll() {
  if (store.isTourActive) {
    window.requestAnimationFrame(() => {
      resolveTargetAndPosition()
    })
  }
}

// ── Watchers & Lifecycle ────────────────────────────────────────────────

watch(
  () => [store.isTourActive, store.currentStepIndex],
  async ([isActive]) => {
    if (isActive) {
      await resolveTargetAndPosition()
      nextTick(() => {
        tourDialogRef.value?.focus()
      })
    }
  },
  { immediate: true }
)

onMounted(async () => {
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('resize', handleResizeOrScroll, { passive: true })
  window.addEventListener('scroll', handleResizeOrScroll, { passive: true })

  // Initialize tour state from backend API on mount
  await store.fetchTourState()
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('resize', handleResizeOrScroll)
  window.removeEventListener('scroll', handleResizeOrScroll)
})
</script>
