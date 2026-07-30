<template>
  <div :class="[
    'max-w-[75%] group',
    isMine ? 'items-end' : 'items-start'
  ]">
    <!-- System message (centered) -->
    <div v-if="message.type === 'system'" class="text-center w-full">
      <span class="text-xs text-gray-600 italic">{{ message.body }}</span>
    </div>

    <!-- Normal bubble -->
    <div v-else :class="['flex gap-2', isMine ? 'flex-row-reverse' : 'flex-row']">
      <!-- Avatar -->
      <div class="w-7 h-7 rounded-lg flex-shrink-0 bg-gradient-to-br from-purple-600 to-blue-600 flex items-center justify-center text-white text-xs font-bold mt-1">
        {{ senderInitial }}
      </div>

      <!-- Bubble content -->
      <div :class="[
        'rounded-2xl px-4 py-3 max-w-full shadow-md',
        isMine
          ? 'bg-purple-600 text-white rounded-tr-sm'
          : 'bg-gray-800/80 text-gray-100 rounded-tl-sm border border-white/10'
      ]">
        <!-- Sender name (for group, not mine) -->
        <p v-if="!isMine" class="text-xs font-medium mb-1 opacity-60">{{ message.sender?.name ?? 'Unknown' }}</p>

        <!-- Text body -->
        <p v-if="message.body" class="text-sm whitespace-pre-wrap break-words">{{ message.body }}</p>

        <!-- Survey Card inside Chat -->
        <div v-if="message.type === 'survey_response' && message.metadata?.survey_id" class="mt-2 p-3 bg-gray-900/60 rounded-xl border border-white/5 space-y-2 max-w-[280px]">
          <div class="flex items-center gap-1.5">
            <span class="text-amber-400 text-base">📝</span>
            <span class="text-xs font-bold text-gray-200 truncate">{{ message.metadata.survey_title ?? 'New Survey' }}</span>
          </div>
          <p class="text-[10px] text-gray-400">Please answer this brief feedback survey.</p>
          <div class="space-y-1.5">
            <button
              @click="showSurvey = true"
              class="w-full py-1.5 px-3 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition-colors text-center shadow-md hover:shadow-amber-600/20 cursor-pointer"
            >
              Fill Survey
            </button>
            <button
              @click="showResults = true"
              class="w-full py-1.5 px-3 rounded-lg bg-purple-600/20 hover:bg-purple-600/30 border border-purple-500/20 text-purple-300 text-xs font-semibold transition-colors text-center shadow-md cursor-pointer"
            >
              View Results
            </button>
          </div>
        </div>

        <!-- File attachment -->
        <button
          v-if="message.type === 'file' && message.metadata?.file_name"
          @click="downloadAttachment"
          :disabled="isDownloading"
          class="flex items-center gap-2 mt-2 p-2.5 rounded-xl bg-black/30 hover:bg-black/40 transition-colors text-left w-full cursor-pointer disabled:opacity-50 border border-white/10"
        >
          <svg v-if="isDownloading" class="w-5 h-5 animate-spin flex-shrink-0 text-purple-300" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          <svg v-else class="w-5 h-5 flex-shrink-0 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
          </svg>
          <span class="text-xs font-semibold truncate flex-1">{{ message.metadata.file_name }}</span>
          <span v-if="message.metadata.file_size" class="text-[10px] opacity-60 flex-shrink-0 font-mono">
            {{ formatSize(message.metadata.file_size) }}
          </span>
        </button>

        <!-- Image attachment -->
        <div v-if="message.type === 'image'" class="mt-2 relative">
          <div v-if="isImageLoading" class="w-[200px] h-[150px] bg-black/30 rounded-xl animate-pulse flex items-center justify-center border border-white/10">
            <span class="text-xs opacity-50">Loading image...</span>
          </div>
          <img
            v-else-if="imageUrl"
            :src="imageUrl"
            :alt="message.metadata?.file_name ?? 'Image'"
            class="rounded-xl max-w-[250px] max-h-[200px] object-cover cursor-pointer hover:opacity-90 transition-opacity border border-white/10 shadow-md"
            @click="downloadAttachment"
            title="Click to download image"
          />
          <button
            v-else
            @click="downloadAttachment"
            class="text-xs underline opacity-80 hover:opacity-100 flex items-center gap-1 cursor-pointer"
          >
            🖼 {{ message.metadata?.file_name ?? 'Download Image' }}
          </button>
        </div>

        <!-- Timestamp -->
        <p class="text-[10px] mt-1 opacity-40 text-right">{{ formattedTime }}</p>
      </div>
    </div>

    <!-- Fill Survey Modal -->
    <FillSurveyModal
      v-if="showSurvey && message.metadata?.survey_id"
      :survey-id="message.metadata.survey_id"
      @close="showSurvey = false"
      @submitted="onSurveySubmitted"
    />

    <!-- View Survey Results Modal -->
    <ViewSurveyResultsModal
      v-if="showResults && message.metadata?.survey_id"
      :survey-id="message.metadata.survey_id"
      @close="showResults = false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue'
import api from '@/api'
import type { ChatMessage } from '@/stores/messagingStore'
import FillSurveyModal from '@/components/messaging/FillSurveyModal.vue'
import ViewSurveyResultsModal from '@/components/messaging/ViewSurveyResultsModal.vue'

const props = defineProps<{
  message: ChatMessage
  isMine: boolean
}>()

const showSurvey = ref(false)
const showResults = ref(false)
const isDownloading = ref(false)
const isImageLoading = ref(false)
const imageUrl = ref<string | null>(null)

function onSurveySubmitted() {
  showSurvey.value = false
  alert('Survey submitted successfully! Thank you.')
}

const senderInitial = computed(() => {
  const name = props.message.sender?.name ?? '?'
  return name.charAt(0).toUpperCase()
})

const formattedTime = computed(() => {
  if (!props.message.created_at) return ''
  return new Date(props.message.created_at).toLocaleTimeString('en', {
    hour: '2-digit',
    minute: '2-digit',
  })
})

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

async function downloadAttachment() {
  if (isDownloading.value) return
  isDownloading.value = true
  try {
    const res = await api.get(`/nachhilfe/chat/messages/${props.message.id}/attachment`, {
      responseType: 'blob',
    })
    const rawType = res.headers['content-type']
    const mimeType = typeof rawType === 'string' ? rawType : 'application/octet-stream'
    const blob = new Blob([res.data], { type: mimeType })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = props.message.metadata?.file_name ?? 'attachment'
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to download attachment', e)
  } finally {
    isDownloading.value = false
  }
}

async function loadImage() {
  if (props.message.type !== 'image') return
  isImageLoading.value = true
  try {
    const res = await api.get(`/nachhilfe/chat/messages/${props.message.id}/attachment`, {
      responseType: 'blob',
    })
    const rawType = res.headers['content-type']
    const mimeType = typeof rawType === 'string' ? rawType : 'image/png'
    const blob = new Blob([res.data], { type: mimeType })
    imageUrl.value = window.URL.createObjectURL(blob)
  } catch (e) {
    console.error('Failed to load inline image', e)
  } finally {
    isImageLoading.value = false
  }
}

onMounted(() => {
  if (props.message.type === 'image') {
    loadImage()
  }
})

watch(
  () => props.message.id,
  () => {
    if (props.message.type === 'image') {
      loadImage()
    }
  }
)
</script>
