<template>
  <div class="messaging-container flex h-[calc(100vh-64px)] overflow-hidden rounded-2xl shadow-xl border border-white/10">

    <!-- ─── Sidebar ─────────────────────────────────────────────── -->
    <aside class="w-80 flex-shrink-0 bg-gray-900/80 backdrop-blur-xl border-r border-white/10 flex flex-col">

      <!-- Header -->
      <div class="p-4 border-b border-white/10">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-white">Messages</h2>
          <button
            @click="openCreateModal"
            class="p-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white transition-colors"
            title="New Conversation"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>

        <!-- Scope Tabs -->
        <div class="flex rounded-xl bg-gray-800/60 p-1 gap-1">
          <button
            @click="setScope('mine')"
            :class="[
              'flex-1 py-1.5 px-3 text-sm font-medium rounded-lg transition-all',
              activeScope === 'mine'
                ? 'bg-purple-600 text-white shadow'
                : 'text-gray-400 hover:text-white'
            ]"
          >
            My Chats
            <span v-if="totalUnread > 0" class="ml-1 bg-purple-500 text-white text-xs rounded-full px-1.5 py-0.5">
              {{ totalUnread }}
            </span>
          </button>
          <button
            @click="setScope('all')"
            :class="[
              'flex-1 py-1.5 px-3 text-sm font-medium rounded-lg transition-all',
              activeScope === 'all'
                ? 'bg-gray-600 text-white shadow'
                : 'text-gray-400 hover:text-white'
            ]"
          >
            All Chats
            <!-- Silent grey badge for observer admin -->
            <span v-if="observerUnread > 0 && activeScope !== 'all'" class="ml-1 bg-gray-500 text-gray-200 text-xs rounded-full px-1.5 py-0.5">
              {{ observerUnread }}
            </span>
          </button>
        </div>
      </div>

      <!-- Channel List -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="isLoadingChannels" class="p-4 space-y-3">
          <div v-for="i in 5" :key="i" class="h-16 rounded-xl bg-white/5 animate-pulse" />
        </div>

        <div v-else-if="displayedChannels.length === 0" class="flex flex-col items-center justify-center h-full text-gray-500 gap-3">
          <svg class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p class="text-sm">No conversations yet</p>
          <button @click="openCreateModal" class="text-purple-400 hover:text-purple-300 text-sm font-medium">Start one</button>
        </div>

        <div v-else>
          <button
            v-for="channel in displayedChannels"
            :key="channel.id"
            @click="selectChannel(channel.id)"
            :class="[
              'w-full text-left px-4 py-3 border-b border-white/5 hover:bg-white/5 transition-colors flex items-start gap-3',
              activeChannelId === channel.id ? 'bg-purple-900/30 border-l-2 border-purple-500' : ''
            ]"
          >
            <!-- Avatar -->
            <div :class="[
              'flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold',
              channel.type === 'group' ? 'bg-blue-600/30 text-blue-300' :
              channel.type === 'survey' ? 'bg-amber-600/30 text-amber-300' :
              'bg-purple-600/30 text-purple-300'
            ]">
              <svg v-if="channel.type === 'group'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
              </svg>
              <svg v-else-if="channel.type === 'survey'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-sm text-white truncate">
                  {{ channel.name || channelDisplayName(channel) }}
                </span>
                <div class="flex items-center gap-1 flex-shrink-0">
                  <!-- Active unread badge -->
                  <span v-if="(channel.unread_count ?? 0) > 0 && channel.my_role !== 'observer'"
                    class="bg-purple-500 text-white text-xs rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1">
                    {{ channel.unread_count }}
                  </span>
                  <!-- Observer silent grey badge -->
                  <span v-else-if="(channel.unread_count ?? 0) > 0 && channel.my_role === 'observer'"
                    class="bg-gray-600 text-gray-300 text-xs rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1">
                    {{ channel.unread_count }}
                  </span>
                </div>
              </div>
              <p class="text-xs text-gray-500 truncate mt-0.5">
                {{ latestMessagePreview(channel) }}
              </p>
            </div>
          </button>
        </div>
      </div>
    </aside>

    <!-- ─── Chat Window ──────────────────────────────────────────── -->
    <main class="flex-1 flex flex-col bg-gray-950/60 backdrop-blur-xl">

      <!-- Empty state -->
      <div v-if="!activeChannelId" class="flex-1 flex flex-col items-center justify-center text-gray-600 gap-4">
        <svg class="w-20 h-20 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <p class="text-lg font-medium text-gray-500">Select a conversation to start messaging</p>
      </div>

      <ChatWindow
        v-else
        :channel-id="activeChannelId"
        :channel="activeChannel"
        :messages="messages"
        :is-loading="isLoadingMessages"
        :is-sending="isSending"
        @send="handleSend"
        @archive="handleArchive"
      />
    </main>

  </div>

  <!-- Create Channel Modal -->
  <CreateChannelModal
    v-if="isCreateModalOpen"
    @close="isCreateModalOpen = false"
    @created="handleChannelCreated"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useMessagingStore } from '@/stores/messagingStore'
import { useAuthStore } from '@/stores/authStore'
import ChatWindow from '@/components/messaging/ChatWindow.vue'
import CreateChannelModal from '@/components/messaging/CreateChannelModal.vue'

const store = useMessagingStore()
const authStore = useAuthStore()

const isCreateModalOpen = ref(false)
const activeScope = computed(() => store.activeScope)
const activeChannelId = computed(() => store.activeChannelId)
const activeChannel = computed(() => store.activeChannel)
const messages = computed(() => store.messages)
const isLoadingChannels = computed(() => store.isLoadingChannels)
const isLoadingMessages = computed(() => store.isLoadingMessages)
const isSending = computed(() => store.isSending)
const totalUnread = computed(() => store.totalUnread)
const observerUnread = computed(() => store.observerUnread)

const displayedChannels = computed(() =>
  activeScope.value === 'mine' ? store.myChannels : store.allChannels
)

// ── WebSocket subscription tracker ────────────────────────────
const subscribedChannels = new Set<string>()

function subscribeToChannel(channelId: string) {
  if (subscribedChannels.has(channelId)) return
  subscribedChannels.add(channelId)
  const echo = (window as any).Echo
  if (!echo) return
  echo.private(`chat.${channelId}`)
    .listen('.message.sent', (data: any) => {
      store.onMessageReceived(data)
    })
}

function unsubscribeAll() {
  const echo = (window as any).Echo
  if (!echo) return
  for (const id of subscribedChannels) {
    echo.leave(`chat.${id}`)
  }
  subscribedChannels.clear()
}

// Subscribe to all loaded channels
watch(
  () => store.myChannels,
  (channels) => {
    for (const ch of channels) {
      if (ch.my_role !== 'observer') subscribeToChannel(ch.id)
    }
  },
  { immediate: true }
)

// ── Lifecycle ──────────────────────────────────────────────────
onMounted(async () => {
  await store.fetchMyChannels()
  await store.fetchAllChannels()
})

onUnmounted(() => {
  unsubscribeAll()
})

// ── Handlers ───────────────────────────────────────────────────

function setScope(scope: 'mine' | 'all') {
  store.activeScope = scope
}

async function selectChannel(channelId: string) {
  await store.selectChannel(channelId)
  subscribeToChannel(channelId)
}

async function handleSend({ body, file }: { body: string | null; file?: File | null }) {
  if (!activeChannelId.value) return
  await store.sendMessage(activeChannelId.value, body, file)
}

async function handleArchive(channelId: string) {
  if (confirm('Archive this conversation?')) {
    await store.archiveChannel(channelId)
  }
}

function openCreateModal() {
  isCreateModalOpen.value = true
}

function handleChannelCreated(channel: any) {
  isCreateModalOpen.value = false
  store.activeScope = 'mine'
  selectChannel(channel.id)
  subscribeToChannel(channel.id)
}

function channelDisplayName(channel: any): string {
  if (channel.participants && channel.participants.length > 0) {
    const others = channel.participants.filter(
      (p: any) => p.user_id !== authStore.user?.id
    )
    if (others.length > 0) {
      return others.map((p: any) => p.user?.name ?? 'Unknown').join(', ')
    }
  }
  return 'Conversation'
}

function latestMessagePreview(channel: any): string {
  const latest = channel.latest_message?.[0] ?? channel.latestMessage?.[0]
  if (!latest) return 'No messages yet'
  if (latest.type === 'file') return '📎 File'
  if (latest.type === 'image') return '🖼 Image'
  return latest.body ?? ''
}
</script>

<style scoped>
.messaging-container {
  background: linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(10, 10, 30, 0.98) 100%);
}
</style>
