<template>
  <div class="space-y-6 h-full flex flex-col">
    <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Settings</h1>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-800 shrink-0">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button 
          @click="activeTab = 'subjects_rooms'" 
          :class="[activeTab === 'subjects_rooms' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          Subjects & Rooms
        </button>
        <button 
          @click="activeTab = 'notifications'" 
          :class="[activeTab === 'notifications' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          Notification Preferences
        </button>
        <button 
          @click="activeTab = 'whatsapp_settings'" 
          :class="[activeTab === 'whatsapp_settings' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          WhatsApp Settings
        </button>
      </nav>
    </div>

    <div v-if="activeTab === 'subjects_rooms'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1 overflow-hidden">
      
      <!-- Subjects Management -->
      <div class="glass-panel rounded-2xl flex flex-col overflow-hidden relative">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Subjects</h2>
          <button @click="openCreateSubject" class="bg-purple-600 hover:bg-purple-700 text-white p-1.5 rounded-lg shadow-sm transition-colors">
            <PlusIcon class="w-5 h-5" />
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
          <div v-if="subjectsStore.isLoading" class="flex justify-center p-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div>
          </div>
          <ul v-else class="space-y-3">
            <li v-for="subject in subjectsStore.subjects" :key="subject.id" class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900 dark:text-white">{{ subject.name }}</div>
                <div class="text-xs text-gray-500" v-if="subject.description">{{ subject.description }}</div>
              </div>
              <div class="flex gap-2">
                <button @click="deleteSubject(subject)" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Rooms Management -->
      <div class="glass-panel rounded-2xl flex flex-col overflow-hidden relative">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Rooms</h2>
          <button @click="openCreateRoom" class="bg-blue-600 hover:bg-blue-700 text-white p-1.5 rounded-lg shadow-sm transition-colors">
            <PlusIcon class="w-5 h-5" />
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
          <div v-if="roomsStore.isLoading" class="flex justify-center p-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
          </div>
          <ul v-else class="space-y-3">
            <li v-for="room in roomsStore.rooms" :key="room.id" class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900 dark:text-white">{{ room.name }}</div>
                <div class="text-xs text-gray-500">Capacity: {{ room.capacity }}</div>
              </div>
              <div class="flex gap-2">
                <button @click="deleteRoom(room)" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Notification Preferences Grid -->
    <div v-else-if="activeTab === 'notifications'" class="glass-panel rounded-2xl p-6 flex-1 overflow-y-auto space-y-6">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Notification Preferences</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure which delivery channels to use for different types of alerts.</p>
      </div>

      <div v-if="isPreferencesLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      <div v-else class="space-y-6">
        <div 
          v-for="notif in defaultTypes" 
          :key="notif.key" 
          class="p-6 rounded-2xl bg-gray-50/50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-800 grid grid-cols-1 lg:grid-cols-3 gap-4 items-center"
        >
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white">{{ notif.label }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ notif.description }}</p>
          </div>
          
          <div class="lg:col-span-2 grid grid-cols-3 gap-4">
            <div 
              v-for="channel in channels" 
              :key="channel.key" 
              class="flex flex-col items-center justify-center p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200/60 dark:border-gray-700 shadow-sm"
            >
              <label :for="notif.key + '_' + channel.key" class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3 cursor-pointer">
                {{ channel.label }}
              </label>
              <input 
                type="checkbox" 
                :id="notif.key + '_' + channel.key"
                v-model="preferenceMatrix[notif.key][channel.key]"
                @change="savePreferences"
                class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- WhatsApp Settings Panel -->
    <div v-else-if="activeTab === 'whatsapp_settings'" class="glass-panel rounded-2xl p-6 flex-1 overflow-y-auto space-y-6">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">WhatsApp Integration Settings</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure your Twilio WhatsApp API credentials to send automated alerts directly to parents.</p>
      </div>

      <div v-if="isSettingsLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <form v-else @submit.prevent="saveSettings" class="max-w-xl space-y-5">
        <div>
          <label for="twilio_sid" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Twilio Account SID</label>
          <input 
            type="text" 
            id="twilio_sid"
            v-model="twilioSid"
            placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
          />
        </div>

        <div>
          <label for="twilio_token" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Twilio Auth Token</label>
          <input 
            type="password" 
            id="twilio_token"
            v-model="twilioToken"
            placeholder="••••••••••••••••••••••••••••••••"
            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
          />
        </div>

        <div>
          <label for="twilio_from" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Twilio WhatsApp Sender Number</label>
          <input 
            type="text" 
            id="twilio_from"
            v-model="twilioFrom"
            placeholder="whatsapp:+14155238886"
            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
          />
          <p class="text-xs text-gray-400 mt-1">Must start with 'whatsapp:' followed by the phone number in E.164 format.</p>
        </div>

        <div class="pt-2">
          <button 
            type="submit"
            :disabled="isSavingSettings"
            class="rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-6 py-2.5 text-sm font-semibold shadow-md transition-colors cursor-pointer flex items-center gap-2"
          >
            <svg v-if="isSavingSettings" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Save Settings
          </button>
        </div>
      </form>
    </div>
    
    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/20/solid'
import { useSubjectsStore, type Subject } from '@/stores/subjectsStore'
import { useRoomsStore, type Room } from '@/stores/roomsStore'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import api from '@/api'
import { useToastStore } from '@/stores/toastStore'

const subjectsStore = useSubjectsStore()
const roomsStore = useRoomsStore()
const toastStore = useToastStore()
const confirmModal = ref<InstanceType<typeof ConfirmModal> | null>(null)

// Tab handling
const activeTab = ref('subjects_rooms')

// Preferences settings
const isPreferencesLoading = ref(false)
const preferenceMatrix = ref<Record<string, Record<string, boolean>>>({
  voucher_low_hours: { in_app: true, chat: true, whatsapp: true },
  voucher_expiring_soon: { in_app: true, chat: true, whatsapp: true }
})

const defaultTypes = [
  { key: 'voucher_low_hours', label: 'Voucher Low Hours Alert', description: 'Triggered when student remaining hours fall below 3 hours.' },
  { key: 'voucher_expiring_soon', label: 'Voucher Expiring Soon Alert', description: 'Triggered when student package voucher expires in less than 14 days.' }
]

const channels = [
  { key: 'in_app', label: 'In-App Feed' },
  { key: 'chat', label: 'Internal Chat Messages' },
  { key: 'whatsapp', label: 'WhatsApp Deliveries' }
]

async function fetchPreferences() {
  isPreferencesLoading.value = true
  try {
    const res = await api.get('/notifications/preferences')
    res.data.forEach((p: any) => {
      if (preferenceMatrix.value[p.notification_type]) {
        preferenceMatrix.value[p.notification_type][p.channel] = p.enabled
      }
    })
  } catch (err) {
    console.error('Failed to load notification preferences', err)
  } finally {
    isPreferencesLoading.value = false
  }
}

async function savePreferences() {
  try {
    const payload: any[] = []
    for (const notifType of Object.keys(preferenceMatrix.value)) {
      for (const channel of Object.keys(preferenceMatrix.value[notifType])) {
        payload.push({
          notification_type: notifType,
          channel: channel,
          enabled: preferenceMatrix.value[notifType][channel]
        })
      }
    }
    await api.post('/notifications/preferences', { preferences: payload })
    toastStore.success('Preferences saved successfully')
  } catch (err) {
    console.error('Failed to save preferences', err)
    toastStore.error('Failed to save preferences')
  }
}

// General System Settings (Twilio API)
const twilioSid = ref('')
const twilioToken = ref('')
const twilioFrom = ref('')
const isSettingsLoading = ref(false)
const isSavingSettings = ref(false)

async function fetchSettings() {
  isSettingsLoading.value = true
  try {
    const res = await api.get('/settings')
    twilioSid.value = res.data.twilio_sid || ''
    twilioToken.value = res.data.twilio_token || ''
    twilioFrom.value = res.data.twilio_from || ''
  } catch (err) {
    console.error('Failed to load settings', err)
  } finally {
    isSettingsLoading.value = false
  }
}

async function saveSettings() {
  isSavingSettings.value = true
  try {
    await api.post('/settings', {
      settings: [
        { key: 'twilio_sid', value: twilioSid.value },
        { key: 'twilio_token', value: twilioToken.value },
        { key: 'twilio_from', value: twilioFrom.value },
      ]
    })
    toastStore.success('WhatsApp API Settings saved successfully')
  } catch (err) {
    console.error('Failed to save settings', err)
    toastStore.error('Failed to save settings')
  } finally {
    isSavingSettings.value = false
  }
}

onMounted(() => {
  subjectsStore.fetchSubjects()
  roomsStore.fetchRooms()
  fetchPreferences()
  fetchSettings()
})

function openCreateSubject() {
  const name = prompt('Enter subject name:')
  if (name) {
    subjectsStore.createSubject({ name, is_active: true })
  }
}

function deleteSubject(subject: Subject) {
  confirmModal.value?.open(
    'Delete Subject',
    `Are you sure you want to delete ${subject.name}?`,
    'Delete',
    'Cancel',
    async () => {
      await subjectsStore.deleteSubject(subject.id)
    }
  )
}

function openCreateRoom() {
  const name = prompt('Enter room name:')
  if (name) {
    const capacityStr = prompt('Enter capacity (e.g. 10):')
    const parsedCapacity = parseInt(capacityStr || '10')
    const capacity = isNaN(parsedCapacity) || parsedCapacity < 1 ? 10 : parsedCapacity
    roomsStore.createRoom({ name, capacity })
  }
}

function deleteRoom(room: Room) {
  confirmModal.value?.open(
    'Delete Room',
    `Are you sure you want to delete ${room.name}?`,
    'Delete',
    'Cancel',
    async () => {
      await roomsStore.deleteRoom(room.id)
    }
  )
}
</script>
