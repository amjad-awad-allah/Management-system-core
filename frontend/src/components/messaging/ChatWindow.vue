<template>
  <div class="flex flex-col h-full">

    <!-- ── Channel Header ───────────────────────────────────────── -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-white/80 dark:bg-gray-900/40">
      <div class="flex items-center gap-3">
        <div :class="[
          'w-10 h-10 rounded-xl flex items-center justify-center',
          channel?.type === 'group' ? 'bg-blue-100 dark:bg-blue-600/30 text-blue-600 dark:text-blue-300' :
          channel?.type === 'survey' ? 'bg-amber-100 dark:bg-amber-600/30 text-amber-600 dark:text-amber-300' :
          'bg-purple-100 dark:bg-purple-600/30 text-purple-600 dark:text-purple-300'
        ]">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
            {{ channel?.name || 'Conversation' }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ channel?.participants?.length ?? 0 }} participants
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <button
          @click="$emit('archive', channelId)"
          class="p-2 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer"
          title="Archive conversation"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8" />
          </svg>
        </button>
      </div>
    </div>

    <!-- ── Messages List ─────────────────────────────────────────── -->
    <div
      ref="messagesContainer"
      class="flex-1 overflow-y-auto px-6 py-4 space-y-3 scroll-smooth bg-gray-50/50 dark:bg-transparent"
    >
      <div v-if="isLoading" class="flex justify-center py-8">
        <div class="w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full animate-spin" />
      </div>

      <template v-else>
        <div
          v-for="msg in visibleMessages"
          :key="msg.id"
          :class="[
            'flex gap-2',
            isMyMessage(msg) ? 'justify-end' : 'justify-start'
          ]"
        >
          <MessageBubble
            :message="msg"
            :is-mine="isMyMessage(msg)"
          />
        </div>

        <div v-if="visibleMessages.length === 0" class="text-center py-12 text-gray-400 dark:text-gray-600">
          <p class="text-sm">No messages yet. Say hello! 👋</p>
        </div>

        <!-- Scroll anchor -->
        <div ref="bottomAnchor" />
      </template>
    </div>

    <!-- ── Message Input ─────────────────────────────────────────── -->
    <div class="border-t border-gray-200 dark:border-white/10 p-4 bg-white/80 dark:bg-gray-900/40">

      <!-- File preview -->
      <div v-if="selectedFile" class="mb-3 flex items-center gap-2 px-3 py-2 bg-purple-50 dark:bg-purple-900/30 rounded-xl border border-purple-200 dark:border-purple-500/30">
        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
        </svg>
        <span class="text-sm text-purple-700 dark:text-purple-300 flex-1 truncate">{{ selectedFile.name }}</span>
        <button @click="clearFile" class="text-gray-400 hover:text-gray-700 dark:hover:text-white transition-colors cursor-pointer">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="flex items-end gap-3">
        <!-- Attachment button -->
        <label class="flex-shrink-0 p-2.5 rounded-xl text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors cursor-pointer">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
          </svg>
          <input
            type="file"
            class="sr-only"
            @change="handleFileSelect"
            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt"
          />
        </label>

        <!-- Text input -->
        <textarea
          v-model="messageBody"
          @keydown.enter.prevent="handleEnter"
          placeholder="Type a message… (Enter to send, Shift+Enter for new line)"
          rows="1"
          class="flex-1 resize-none rounded-xl bg-gray-100 dark:bg-gray-800/60 border border-gray-300 dark:border-white/10 focus:border-purple-500 focus:outline-none px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 transition-colors max-h-32 overflow-y-auto"
          style="field-sizing: content;"
        />

        <!-- Send button -->
        <button
          @click="handleSend"
          :disabled="isSending || (!messageBody.trim() && !selectedFile)"
          class="flex-shrink-0 p-3 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 disabled:cursor-not-allowed text-white transition-all shadow-lg hover:shadow-purple-500/25 cursor-pointer"
        >
          <svg v-if="isSending" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </button>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import MessageBubble from '@/components/messaging/MessageBubble.vue'
import type { ChatMessage, ChatChannel } from '@/stores/messagingStore'

const props = defineProps<{
  channelId: string
  channel: ChatChannel | null
  messages: ChatMessage[]
  isLoading: boolean
  isSending: boolean
}>()

const emit = defineEmits<{
  send: [{ body: string | null; file?: File | null }]
  archive: [channelId: string]
}>()

const authStore = useAuthStore()
const messageBody = ref('')
const selectedFile = ref<File | null>(null)
const messagesContainer = ref<HTMLElement | null>(null)
const bottomAnchor = ref<HTMLElement | null>(null)

const visibleMessages = computed(() =>
  props.messages.filter(m => !m.is_deleted)
)

function isMyMessage(msg: ChatMessage): boolean {
  return msg.sender_id === authStore.user?.id
}

function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files?.[0]) {
    selectedFile.value = input.files[0]
    input.value = '' // reset input
  }
}

function clearFile() {
  selectedFile.value = null
}

function handleEnter(event: KeyboardEvent) {
  if (event.shiftKey) return // allow newline
  handleSend()
}

function handleSend() {
  const body = messageBody.value.trim() || null
  if (!body && !selectedFile.value) return

  emit('send', { body, file: selectedFile.value })
  messageBody.value = ''
  selectedFile.value = null
}

// Auto-scroll to bottom when messages change
watch(
  () => props.messages.length,
  async () => {
    await nextTick()
    bottomAnchor.value?.scrollIntoView({ behavior: 'smooth' })
  }
)

watch(
  () => props.channelId,
  async () => {
    await nextTick()
    bottomAnchor.value?.scrollIntoView({ behavior: 'instant' })
  }
)
</script>
