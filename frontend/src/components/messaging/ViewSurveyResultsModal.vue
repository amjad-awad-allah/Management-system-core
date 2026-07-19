<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')" />

    <div class="relative z-10 w-full max-w-2xl max-h-[90vh] flex flex-col bg-gray-900 rounded-2xl shadow-2xl border border-white/10 overflow-hidden">
      
      <!-- Header -->
      <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between flex-shrink-0">
        <div>
          <h3 class="text-lg font-bold text-white">Survey Results</h3>
          <p v-if="survey" class="text-xs text-gray-400 mt-0.5">{{ survey.title }}</p>
        </div>
        <button @click="$emit('close')" class="text-gray-500 hover:text-white transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 overflow-y-auto space-y-6 flex-1 min-h-0">
        <div v-if="isLoading" class="flex justify-center py-12">
          <div class="w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <div v-else-if="error" class="text-center py-8 text-red-400 text-sm">
          {{ error }}
        </div>

        <template v-else-if="survey">
          <!-- Description -->
          <div v-if="survey.description" class="p-4 bg-gray-800/40 rounded-xl border border-white/5">
            <p class="text-sm text-gray-300">{{ survey.description }}</p>
          </div>

          <!-- Total respondents banner -->
          <div class="flex items-center justify-between bg-gradient-to-r from-purple-900/30 to-blue-900/30 px-5 py-4 rounded-xl border border-white/10">
            <div>
              <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Total Responses</p>
              <h4 class="text-2xl font-black text-white mt-1">{{ survey.responses?.length ?? 0 }} voters</h4>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Status</p>
              <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold mt-1.5', survey.status === 'active' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-gray-800 text-gray-400']">
                {{ survey.status }}
              </span>
            </div>
          </div>

          <!-- Questions Statistics -->
          <div class="space-y-6">
            <div
              v-for="(q, idx) in stats"
              :key="q.id"
              class="p-5 bg-gray-800/25 border border-white/5 rounded-2xl space-y-4"
            >
              <div class="flex items-start justify-between gap-3">
                <h4 class="text-sm font-bold text-white">
                  {{ idx + 1 }}. {{ q.question }}
                </h4>
                <span class="text-[10px] bg-gray-800 px-2 py-0.5 rounded text-gray-400 font-medium whitespace-nowrap">
                  {{ q.totalVotes }} responses
                </span>
              </div>

              <!-- Choice type statistics -->
              <div v-if="['single_choice', 'multiple_choice'].includes(q.type)" class="space-y-3">
                <div v-for="opt in q.options" :key="opt.option" class="space-y-1.5">
                  <div class="flex items-center justify-between text-xs font-medium">
                    <span class="text-gray-300">{{ opt.option }}</span>
                    <span class="text-gray-400">{{ opt.count }} votes ({{ opt.percentage }}%)</span>
                  </div>
                  <!-- Progress bar -->
                  <div class="h-2 w-full bg-gray-800 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-500"
                      :style="{ width: opt.percentage + '%' }"
                    />
                  </div>
                </div>
              </div>

              <!-- Rating type statistics -->
              <div v-else-if="q.type === 'rating'" class="space-y-4">
                <div class="flex items-center gap-3">
                  <div class="bg-amber-500/10 text-amber-400 px-4 py-2.5 rounded-xl border border-amber-500/20 text-center min-w-[70px]">
                    <span class="block text-xl font-black">{{ q.average }}</span>
                    <span class="text-[9px] text-amber-400/70 font-semibold uppercase">Average</span>
                  </div>
                  <div class="text-xs text-gray-400">
                    Based on {{ q.totalVotes }} ratings scale (1-5)
                  </div>
                </div>

                <div class="space-y-2">
                  <div v-for="rating in q.ratings.slice().reverse()" :key="rating.star" class="flex items-center gap-3 text-xs font-medium">
                    <span class="w-8 text-gray-400 flex items-center gap-1 justify-end">
                      {{ rating.star }} ★
                    </span>
                    <div class="h-2 flex-1 bg-gray-800 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-amber-500 rounded-full transition-all duration-500"
                        :style="{ width: rating.percentage + '%' }"
                      />
                    </div>
                    <span class="w-16 text-right text-gray-500">{{ rating.count }} votes</span>
                  </div>
                </div>
              </div>

              <!-- Text type responses -->
              <div v-else class="space-y-2">
                <div v-if="q.responses.length === 0" class="text-xs text-gray-600 italic">
                  No text feedback provided.
                </div>
                <div v-else class="max-h-40 overflow-y-auto space-y-2 pr-1">
                  <div
                    v-for="resp in q.responses"
                    :key="resp.userName"
                    class="p-2.5 bg-gray-900/40 border border-white/5 rounded-xl text-xs space-y-1"
                  >
                    <p class="font-bold text-purple-400">{{ resp.userName }}</p>
                    <p class="text-gray-300 italic">"{{ resp.text }}"</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-white/10 flex justify-end flex-shrink-0 bg-gray-950/20">
        <button
          @click="$emit('close')"
          class="px-5 py-2 rounded-xl bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold transition-colors"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api'

const props = defineProps<{
  surveyId: string
}>()

defineEmits<{
  close: []
}>()

const survey = ref<any>(null)
const isLoading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const res = await api.get(`/nachhilfe/chat/surveys/${props.surveyId}`)
    survey.value = res.data
  } catch (e) {
    console.error(e)
    error.value = 'Failed to load survey results.'
  } finally {
    isLoading.value = false
  }
})

const stats = computed(() => {
  if (!survey.value) return []

  const totalResponses = survey.value.responses?.length ?? 0

  return survey.value.questions.map((q: any) => {
    const questionId = q.id
    const type = q.type
    const options = q.options ?? []

    if (type === 'single_choice' || type === 'multiple_choice') {
      const counts: Record<string, number> = {}
      options.forEach((opt: string) => {
        counts[opt] = 0
      })

      survey.value.responses.forEach((resp: any) => {
        const answer = resp.answers?.[questionId]
        if (Array.isArray(answer)) {
          answer.forEach((val: string) => {
            if (counts[val] !== undefined) {
              counts[val]++
            }
          })
        } else if (answer) {
          if (counts[answer] !== undefined) {
            counts[answer]++
          }
        }
      })

      const optionStats = options.map((opt: string) => {
        const count = counts[opt]
        const pct = totalResponses > 0 ? Math.round((count / totalResponses) * 100) : 0
        return { option: opt, count, percentage: pct }
      })

      return {
        id: q.id,
        question: q.question,
        type,
        totalVotes: totalResponses,
        options: optionStats
      }
    } else if (type === 'rating') {
      const counts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 } as Record<number, number>
      let sum = 0
      let count = 0

      survey.value.responses.forEach((resp: any) => {
        const answer = Number(resp.answers?.[questionId])
        if (answer >= 1 && answer <= 5) {
          counts[answer]++
          sum += answer
          count++
        }
      })

      const average = count > 0 ? (sum / count).toFixed(1) : '0.0'

      const ratingStats = [1, 2, 3, 4, 5].map((star) => {
        const voteCount = counts[star]
        const pct = count > 0 ? Math.round((voteCount / count) * 100) : 0
        return { star, count: voteCount, percentage: pct }
      })

      return {
        id: q.id,
        question: q.question,
        type,
        totalVotes: count,
        average,
        ratings: ratingStats
      }
    } else {
      const responsesList = survey.value.responses
        ?.map((resp: any) => ({
          userName: resp.user?.name ?? 'Anonymous',
          text: resp.answers?.[questionId] ?? ''
        }))
        ?.filter((r: any) => r.text?.trim()?.length > 0) ?? []

      return {
        id: q.id,
        question: q.question,
        type,
        totalVotes: responsesList.length,
        responses: responsesList
      }
    }
  })
})
</script>
