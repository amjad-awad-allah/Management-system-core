<template>
  <div>
    <div class="messaging-container flex h-[calc(100vh-64px)] overflow-hidden rounded-2xl shadow-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">

      <!-- ─── Sidebar ─────────────────────────────────────────────── -->
      <aside class="w-80 flex-shrink-0 bg-gray-50/80 dark:bg-gray-900/90 backdrop-blur-xl border-r border-gray-200 dark:border-white/10 flex flex-col">

        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-white/10 space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <span>Messages</span>
            </h2>
            <!-- WhatsApp Action Icons -->
            <div class="flex items-center gap-1">
              <!-- Create Group Button -->
              <button
                @click="isCreateGroupModalOpen = true"
                class="p-2 rounded-xl bg-purple-100 dark:bg-purple-600/30 hover:bg-purple-600 text-purple-600 dark:text-purple-300 hover:text-white transition-all shadow-sm cursor-pointer"
                title="Create New Group"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
                </svg>
              </button>
              <!-- Create Direct Chat Button -->
              <button
                @click="isCreateDirectModalOpen = true"
                class="p-2 rounded-xl bg-blue-100 dark:bg-blue-600/30 hover:bg-blue-600 text-blue-600 dark:text-blue-300 hover:text-white transition-all shadow-sm cursor-pointer"
                title="New Direct Message"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Scope Tabs -->
          <div class="flex rounded-xl bg-gray-200/70 dark:bg-gray-800/60 p-1 gap-1">
            <button
              @click="setScope('mine')"
              :class="[
                'flex-1 py-1.5 px-3 text-xs font-semibold rounded-lg transition-all cursor-pointer',
                activeScope === 'mine'
                  ? 'bg-purple-600 text-white shadow'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
              ]"
            >
              My Chats
              <span v-if="totalUnread > 0" class="ml-1 bg-purple-500 text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold">
                {{ totalUnread }}
              </span>
            </button>
            <button
              @click="setScope('all')"
              :class="[
                'flex-1 py-1.5 px-3 text-xs font-semibold rounded-lg transition-all cursor-pointer',
                activeScope === 'all'
                  ? 'bg-gray-700 text-white shadow'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
              ]"
            >
              All Chats
              <span v-if="observerUnread > 0 && activeScope !== 'all'" class="ml-1 bg-gray-400 dark:bg-gray-600 text-gray-900 dark:text-gray-200 text-[10px] rounded-full px-1.5 py-0.5 font-bold">
                {{ observerUnread }}
              </span>
            </button>
          </div>

          <!-- Search Channels Bar -->
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="channelSearchQuery"
              type="text"
              placeholder="Search chats..."
              class="w-full rounded-xl bg-white dark:bg-gray-800/60 border border-gray-300 dark:border-white/10 focus:border-purple-500 focus:outline-none py-2 pr-3 text-xs text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-colors shadow-sm"
              style="padding-left: 2.25rem !important;"
            />
          </div>
        </div>

        <!-- Channel List -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="isLoadingChannels" class="p-4 space-y-3">
            <div v-for="i in 5" :key="i" class="h-16 rounded-xl bg-gray-200 dark:bg-white/5 animate-pulse" />
          </div>

          <div v-else-if="filteredChannels.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 gap-3 p-6 text-center">
            <svg class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-xs text-gray-500 dark:text-gray-400">No conversations found</p>
            <div class="flex gap-2">
              <button @click="isCreateGroupModalOpen = true" class="text-purple-600 dark:text-purple-400 hover:underline text-xs font-semibold cursor-pointer">Create Group</button>
              <span class="text-gray-400">•</span>
              <button @click="isCreateDirectModalOpen = true" class="text-blue-600 dark:text-blue-400 hover:underline text-xs font-semibold cursor-pointer">New Message</button>
            </div>
          </div>

          <div v-else>
            <button
              v-for="channel in filteredChannels"
              :key="channel.id"
              @click="selectChannel(channel.id)"
              :class="[
                'w-full text-left px-4 py-3 border-b border-gray-100 dark:border-white/5 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors flex items-start gap-3 cursor-pointer',
                activeChannelId === channel.id ? 'bg-purple-50 dark:bg-purple-900/40 border-l-4 border-l-purple-600 dark:border-l-purple-500 shadow-sm' : ''
              ]"
            >
              <!-- Avatar -->
              <div :class="[
                'flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold shadow-inner',
                channel.type === 'group' ? 'bg-purple-100 dark:bg-purple-600/30 text-purple-700 dark:text-purple-300' :
                channel.type === 'survey' ? 'bg-amber-100 dark:bg-amber-600/30 text-amber-700 dark:text-amber-300' :
                'bg-blue-100 dark:bg-blue-600/30 text-blue-700 dark:text-blue-300'
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
                  <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                    {{ channel.name || channelDisplayName(channel) }}
                  </span>
                  <div class="flex items-center gap-1 flex-shrink-0">
                    <!-- Active unread badge -->
                    <span v-if="(channel.unread_count ?? 0) > 0 && channel.my_role !== 'observer'"
                      class="bg-purple-500 text-white text-[10px] font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1">
                      {{ channel.unread_count }}
                    </span>
                    <!-- Observer silent grey badge -->
                    <span v-else-if="(channel.unread_count ?? 0) > 0 && channel.my_role === 'observer'"
                      class="bg-gray-400 dark:bg-gray-600 text-white dark:text-gray-300 text-[10px] font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1">
                      {{ channel.unread_count }}
                    </span>
                  </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                  {{ latestMessagePreview(channel) }}
                </p>
              </div>
            </button>
          </div>
        </div>
      </aside>

      <!-- ─── Chat Window ──────────────────────────────────────────── -->
      <main class="flex-1 flex flex-col bg-gray-100/60 dark:bg-gray-950/60 backdrop-blur-xl">

        <!-- Empty state (Only if no channel available) -->
        <div v-if="!activeChannelId" class="flex-1 flex flex-col items-center justify-center text-gray-400 gap-4 p-8 text-center">
          <div class="w-20 h-20 rounded-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 flex items-center justify-center text-purple-600 dark:text-purple-400/40 shadow-sm">
            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-gray-300">Welcome to Instant Messaging</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm">Select a conversation from the sidebar or start a new group with teachers and students</p>
          </div>
          <div class="flex gap-3">
            <button @click="isCreateGroupModalOpen = true" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold transition-all shadow cursor-pointer">
              + Create Group
            </button>
            <button @click="isCreateDirectModalOpen = true" class="px-4 py-2 rounded-xl bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-transparent text-xs font-semibold transition-all shadow-sm cursor-pointer">
              New Direct Message
            </button>
          </div>
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

    <!-- Create Group Modal -->
    <CreateGroupModal
      v-if="isCreateGroupModalOpen"
      @close="isCreateGroupModalOpen = false"
      @created="handleChannelCreated"
    />

    <!-- Create Direct Modal -->
    <CreateDirectModal
      v-if="isCreateDirectModalOpen"
      @close="isCreateDirectModalOpen = false"
      @created="handleChannelCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useMessagingStore } from '@/stores/messagingStore'
import { useAuthStore } from '@/stores/authStore'
import ChatWindow from '@/components/messaging/ChatWindow.vue'
import CreateGroupModal from '@/components/messaging/CreateGroupModal.vue'
import CreateDirectModal from '@/components/messaging/CreateDirectModal.vue'

const store = useMessagingStore()
const authStore = useAuthStore()

const isCreateGroupModalOpen = ref(false)
const isCreateDirectModalOpen = ref(false)
const channelSearchQuery = ref('')

let syncIntervalTimer: any = null

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

const filteredChannels = computed(() => {
  const query = channelSearchQuery.value.trim().toLowerCase()
  if (!query) return displayedChannels.value
  return displayedChannels.value.filter(ch => {
    const name = (ch.name || channelDisplayName(ch)).toLowerCase()
    return name.includes(query)
  })
})

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

// ── Auto-select first channel on load or scope change ──────────────
function autoSelectFirstChannel() {
  if (!activeChannelId.value && displayedChannels.value.length > 0) {
    selectChannel(displayedChannels.value[0].id)
  }
}

// ── Lifecycle ──────────────────────────────────────────────────
onMounted(async () => {
  await store.fetchMyChannels()
  await store.fetchAllChannels()
  autoSelectFirstChannel()

  // ── Smart Instant Sync Timer (SignalR/Firebase style fallback) ──
  // Polls for new messages and channels updates every 3s to guarantee 100% real-time sync everywhere
  syncIntervalTimer = setInterval(async () => {
    if (store.activeChannelId) {
      await store.syncLatestMessages()
    }
    await store.refreshChannelsList()
  }, 3000)
})

onUnmounted(() => {
  if (syncIntervalTimer) {
    clearInterval(syncIntervalTimer)
  }
  unsubscribeAll()
})

// ── Handlers ───────────────────────────────────────────────────

function setScope(scope: 'mine' | 'all') {
  store.activeScope = scope
  autoSelectFirstChannel()
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
  if (confirm('Are you sure you want to archive this conversation?')) {
    await store.archiveChannel(channelId)
    autoSelectFirstChannel()
  }
}

function handleChannelCreated(channel: any) {
  isCreateGroupModalOpen.value = false
  isCreateDirectModalOpen.value = false
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
      return others.map((p: any) => p.user?.name ?? 'Member').join(', ')
    }
  }
  return 'Conversation'
}

function latestMessagePreview(channel: any): string {
  const latest = channel.latest_message?.[0] ?? channel.latestMessage?.[0]
  if (!latest) return 'No messages yet'
  if (latest.type === 'file') return '📎 File attachment'
  if (latest.type === 'image') return '🖼 Image'
  return latest.body ?? ''
}
</script>

<style scoped>
.messaging-container {
  background: linear-gradient(135deg, rgba(17, 24, 39, 0.95) 0%, rgba(10, 10, 30, 0.98) 100%);
}
</style>
