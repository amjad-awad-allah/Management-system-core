<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')" />

    <div class="relative z-10 w-full max-w-lg bg-gray-900 rounded-2xl shadow-2xl border border-white/10 overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between flex-shrink-0">
        <div>
          <h3 class="text-lg font-bold text-white">{{ survey?.title ?? 'Survey' }}</h3>
          <p v-if="survey?.description" class="text-xs text-gray-400 mt-1">{{ survey.description }}</p>
        </div>
        <button @click="$emit('close')" class="text-gray-500 hover:text-white transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Scrollable Questions -->
      <div class="p-6 overflow-y-auto space-y-6 flex-1">
        <div v-if="isLoading" class="flex justify-center py-8">
          <div class="w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <template v-else-if="survey">
          <div
            v-for="(q, idx) in survey.questions"
            :key="q.id"
            class="space-y-3"
          >
            <label class="block text-sm font-semibold text-white">
              {{ idx + 1 }}. {{ q.question }}
            </label>

            <!-- Text question -->
            <textarea
              v-if="q.type === 'text'"
              v-model="answers[q.id]"
              placeholder="Your answer here..."
              rows="2"
              class="w-full rounded-xl bg-gray-800/60 border border-white/10 focus:border-purple-500 focus:outline-none px-4 py-3 text-sm text-white placeholder-gray-600 transition-colors resize-none"
            />

            <!-- Single Choice -->
            <div v-else-if="q.type === 'single_choice'" class="space-y-2">
              <label
                v-for="opt in q.options"
                :key="opt"
                class="flex items-center gap-3 px-4 py-3 rounded-xl border border-white/5 bg-gray-800/20 cursor-pointer hover:bg-gray-800/40 transition-all"
              >
                <input
                  type="radio"
                  :name="'q_' + q.id"
                  :value="opt"
                  v-model="answers[q.id]"
                  class="text-purple-600 focus:ring-purple-500 bg-gray-900 border-white/10"
                />
                <span class="text-sm text-gray-200">{{ opt }}</span>
              </label>
            </div>

            <!-- Multiple Choice -->
            <div v-else-if="q.type === 'multiple_choice'" class="space-y-2">
              <label
                v-for="opt in q.options"
                :key="opt"
                class="flex items-center gap-3 px-4 py-3 rounded-xl border border-white/5 bg-gray-800/20 cursor-pointer hover:bg-gray-800/40 transition-all"
              >
                <input
                  type="checkbox"
                  :value="opt"
                  :checked="isCheckboxChecked(q.id, opt)"
                  @change="toggleCheckbox(q.id, opt)"
                  class="text-purple-600 focus:ring-purple-500 bg-gray-900 border-white/10 rounded"
                />
                <span class="text-sm text-gray-200">{{ opt }}</span>
              </label>
            </div>

            <!-- Rating scale (1-5 stars or numbers) -->
            <div v-else-if="q.type === 'rating'" class="flex items-center gap-2">
              <button
                v-for="star in 5"
                :key="star"
                @click="answers[q.id] = star"
                type="button"
                :class="[
                  'w-10 h-10 rounded-xl flex items-center justify-center text-sm font-semibold transition-all border',
                  answers[q.id] === star
                    ? 'bg-amber-500 border-amber-400 text-white shadow'
                    : 'bg-gray-800 border-white/5 text-gray-400 hover:text-white'
                ]"
              >
                {{ star }}
              </button>
            </div>
          </div>
        </template>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-white/10 flex justify-end gap-3 flex-shrink-0 bg-gray-950/20">
        <button
          @click="$emit('close')"
          class="px-4 py-2 rounded-xl text-sm text-gray-400 hover:text-white transition-colors"
        >
          Cancel
        </button>
        <button
          @click="handleSubmit"
          :disabled="isSubmitting || !isComplete"
          class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium transition-all shadow-lg hover:shadow-purple-500/25"
        >
          <span v-if="isSubmitting">Submitting...</span>
          <span v-else>Submit Answers</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import api from '@/api'

const props = defineProps<{
  surveyId: string
}>()

const emit = defineEmits<{
  close: []
  submitted: []
}>()

const survey = ref<any>(null)
const isLoading = ref(true)
const isSubmitting = ref(false)
const answers = reactive<Record<string, any>>({})

onMounted(async () => {
  try {
    // Fetch survey detail
    // We can fetch from `/mobile/student/chat/surveys` list or `/chat/surveys/{id}` details
    // Let's call details endpoint or find it from the list
    const res = await api.get('/mobile/student/chat/surveys')
    const list = res.data.data ?? res.data
    survey.value = list.find((s: any) => s.id === props.surveyId)

    if (survey.value) {
      // Initialize answer states
      survey.value.questions.forEach((q: any) => {
        if (q.type === 'multiple_choice') {
          answers[q.id] = []
        } else {
          answers[q.id] = ''
        }
      });
    }
  } catch (e) {
    console.error('Failed to load survey', e)
  } finally {
    isLoading.value = false
  }
})

const isComplete = computed(() => {
  if (!survey.value) return false
  return survey.value.questions.every((q: any) => {
    const val = answers[q.id]
    if (q.type === 'multiple_choice') {
      return Array.isArray(val) && val.length > 0
    }
    return val !== undefined && val !== null && val !== ''
  })
})

function isCheckboxChecked(qId: string, option: string): boolean {
  return Array.isArray(answers[qId]) && answers[qId].includes(option)
}

function toggleCheckbox(qId: string, option: string) {
  if (!Array.isArray(answers[qId])) {
    answers[qId] = []
  }
  const idx = answers[qId].indexOf(option)
  if (idx === -1) {
    answers[qId].push(option)
  } else {
    answers[qId].splice(idx, 1)
  }
}

async function handleSubmit() {
  if (!isComplete.value) return

  isSubmitting.value = true
  try {
    // Submit response to `/mobile/student/chat/surveys/{id}/respond`
    await api.post(`/mobile/student/chat/surveys/${props.surveyId}/respond`, {
      answers: answers
    })
    emit('submitted')
  } catch (e) {
    console.error('Failed to submit response', e)
  } finally {
    isSubmitting.value = false
  }
}
</script>
