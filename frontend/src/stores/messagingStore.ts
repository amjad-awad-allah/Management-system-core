import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export interface ChatParticipant {
  id: string
  user_id: string
  role: 'member' | 'admin' | 'observer'
  last_read_at: string | null
  user?: { id: string; name: string }
}

export interface ChatMessage {
  id: string
  channel_id: string
  sender_id: string
  sender?: { id: string; name: string }
  body: string | null
  type: 'text' | 'file' | 'image' | 'system' | 'survey_response'
  metadata?: {
    file_path?: string
    file_name?: string
    file_size?: number
    mime_type?: string
    survey_id?: string
    survey_title?: string
  }
  is_deleted: boolean
  created_at: string
}

export interface ChatChannel {
  id: string
  type: 'direct' | 'group' | 'survey'
  name: string | null
  created_by: string
  unread_count?: number
  my_role?: 'member' | 'admin' | 'observer'
  participants?: ChatParticipant[]
  latest_message?: ChatMessage[]
  deleted_at?: string | null
  created_at: string
  updated_at: string
}

export const useMessagingStore = defineStore('messaging', () => {
  // ── State ──────────────────────────────────────────────────────
  const myChannels = ref<ChatChannel[]>([])
  const allChannels = ref<ChatChannel[]>([])
  const activeChannelId = ref<string | null>(null)
  const messages = ref<ChatMessage[]>([])
  const isLoadingChannels = ref(false)
  const isLoadingMessages = ref(false)
  const isSending = ref(false)
  const activeScope = ref<'mine' | 'all'>('mine')

  // ── Computed ───────────────────────────────────────────────────
  const activeChannel = computed<ChatChannel | null>(() => {
    const list = activeScope.value === 'mine' ? myChannels.value : allChannels.value
    return list.find(c => c.id === activeChannelId.value) ?? null
  })

  const totalUnread = computed<number>(() =>
    myChannels.value.reduce((sum, c) => sum + (c.unread_count ?? 0), 0)
  )

  // Unread count for "all channels" (observer view — silent grey badge)
  const observerUnread = computed<number>(() =>
    allChannels.value
      .filter(c => (c.my_role === 'observer' || !c.my_role))
      .reduce((sum, c) => sum + (c.unread_count ?? 0), 0)
  )

  // ── Actions ────────────────────────────────────────────────────

  async function fetchMyChannels() {
    isLoadingChannels.value = true
    try {
      const res = await api.get('/nachhilfe/chat/channels', { params: { scope: 'mine' } })
      myChannels.value = res.data.data ?? res.data
    } catch (e) {
      console.error('Failed to fetch my channels', e)
    } finally {
      isLoadingChannels.value = false
    }
  }

  async function fetchAllChannels() {
    isLoadingChannels.value = true
    try {
      const res = await api.get('/nachhilfe/chat/channels', { params: { scope: 'all' } })
      allChannels.value = res.data.data ?? res.data
    } catch (e) {
      console.error('Failed to fetch all channels', e)
    } finally {
      isLoadingChannels.value = false
    }
  }

  async function selectChannel(channelId: string) {
    activeChannelId.value = channelId
    messages.value = []
    await fetchMessages(channelId)
    await markRead(channelId)
  }

  async function fetchMessages(channelId: string) {
    isLoadingMessages.value = true
    try {
      const res = await api.get(`/nachhilfe/chat/channels/${channelId}/messages`)
      const data = res.data.data ?? res.data
      // Reverse so oldest is first
      messages.value = [...data].reverse()
    } catch (e) {
      console.error('Failed to fetch messages', e)
    } finally {
      isLoadingMessages.value = false
    }
  }

  async function sendMessage(channelId: string, body: string | null, file?: File | null) {
    isSending.value = true
    try {
      const formData = new FormData()
      if (body) formData.append('body', body)
      if (file) formData.append('file', file)

      const res = await api.post(`/nachhilfe/chat/channels/${channelId}/messages`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      // Append sent message locally (broadcast will handle other users)
      messages.value.push(res.data)

      // Reset unread for this channel
      updateUnreadCount(channelId, 0)

      return res.data
    } catch (e) {
      console.error('Failed to send message', e)
      throw e
    } finally {
      isSending.value = false
    }
  }

  async function createChannel(payload: {
    type: 'direct' | 'group' | 'survey'
    name?: string
    participant_ids: string[]
    observer_ids?: string[]
  }) {
    const res = await api.post('/nachhilfe/chat/channels', payload)
    const channel = res.data
    myChannels.value.unshift(channel)
    allChannels.value.unshift(channel)
    return channel
  }

  async function archiveChannel(channelId: string) {
    await api.delete(`/nachhilfe/chat/channels/${channelId}`)
    myChannels.value = myChannels.value.filter(c => c.id !== channelId)
    allChannels.value = allChannels.value.filter(c => c.id !== channelId)
    if (activeChannelId.value === channelId) {
      activeChannelId.value = null
      messages.value = []
    }
  }

  async function markRead(channelId: string) {
    try {
      await api.post(`/nachhilfe/chat/channels/${channelId}/read`)
      updateUnreadCount(channelId, 0)
    } catch (e) {
      // silently fail
    }
  }

  async function deleteMessage(messageId: string) {
    await api.delete(`/nachhilfe/chat/messages/${messageId}`)
    const idx = messages.value.findIndex(m => m.id === messageId)
    if (idx !== -1) messages.value[idx].is_deleted = true
  }

  // ── Real-Time (Reverb) ─────────────────────────────────────────

  /**
   * Called by the Reverb WebSocket listener when a new message arrives.
   * Appends to messages array if the active channel matches.
   */
  function onMessageReceived(msg: ChatMessage) {
    // If this channel is currently open, append
    if (activeChannelId.value === msg.channel_id) {
      messages.value.push(msg)
      // Auto mark read since user is viewing
      markRead(msg.channel_id)
    } else {
      // Increment unread badge
      incrementUnread(msg.channel_id)
    }
  }

  // ── Internal helpers ───────────────────────────────────────────

  function updateUnreadCount(channelId: string, count: number) {
    const inMine = myChannels.value.find(c => c.id === channelId)
    if (inMine) inMine.unread_count = count
    const inAll = allChannels.value.find(c => c.id === channelId)
    if (inAll) inAll.unread_count = count
  }

  function incrementUnread(channelId: string) {
    const inMine = myChannels.value.find(c => c.id === channelId)
    if (inMine) inMine.unread_count = (inMine.unread_count ?? 0) + 1
    const inAll = allChannels.value.find(c => c.id === channelId)
    if (inAll) inAll.unread_count = (inAll.unread_count ?? 0) + 1
  }

  return {
    myChannels,
    allChannels,
    activeChannelId,
    activeChannel,
    activeScope,
    messages,
    isLoadingChannels,
    isLoadingMessages,
    isSending,
    totalUnread,
    observerUnread,
    fetchMyChannels,
    fetchAllChannels,
    selectChannel,
    fetchMessages,
    sendMessage,
    createChannel,
    archiveChannel,
    markRead,
    deleteMessage,
    onMessageReceived,
  }
})
