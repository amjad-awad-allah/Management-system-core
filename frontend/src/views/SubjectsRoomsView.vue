<template>
  <div class="space-y-6 h-full flex flex-col">
    <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ $t('settings.title') }}</h1>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-800 shrink-0">
      <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
        <button 
          @click="activeTab = 'general'" 
          :class="[activeTab === 'general' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          {{ $t('settings.general') }}
        </button>
        <button 
          @click="activeTab = 'subjects_rooms'" 
          :class="[activeTab === 'subjects_rooms' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          {{ $t('settings.subjectsRooms') }}
        </button>
        <button 
          @click="activeTab = 'notifications'" 
          :class="[activeTab === 'notifications' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          {{ $t('settings.notifications') }}
        </button>
        <button 
          @click="activeTab = 'whatsapp_settings'" 
          :class="[activeTab === 'whatsapp_settings' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          {{ $t('settings.whatsapp') }}
        </button>
      </nav>
    </div>

    <!-- ─── TAB 1: General Center Branding & Bundesland ────────────── -->
    <div v-if="activeTab === 'general'" class="flex-1 overflow-y-auto space-y-6 pb-8">
      
      <!-- Top Overview Header Card -->
      <div class="glass-panel rounded-2xl p-6 border border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <!-- Center Logo / Fallback Badge -->
            <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-tr from-purple-600 to-blue-500 flex items-center justify-center text-white font-bold text-2xl shadow-md overflow-hidden ring-2 ring-purple-500/20">
              <img 
                v-if="settingsStore.centerLogoUrl" 
                :src="settingsStore.centerLogoUrl" 
                alt="Center Logo" 
                class="w-full h-full object-contain p-1.5 bg-white/90 dark:bg-gray-900/90" 
              />
              <span v-else>{{ centerForm.name ? centerForm.name[0]?.toUpperCase() : 'N' }}</span>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ centerForm.name || $t('settings.centerName') }}</h2>
              <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                  📍 {{ currentBundeslandName }}
                </span>
                <span class="text-xs text-gray-400">• {{ $t('settings.centerBrandingDesc') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Center Profile Form (Name & Bundesland) -->
        <div class="lg:col-span-2 glass-panel rounded-2xl p-6 border border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('settings.centerBranding') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('settings.centerBrandingDesc') }}</p>
          </div>

          <form @submit.prevent="handleSaveCenterProfile" class="space-y-5">
            <!-- Center Name Input -->
            <div>
              <label for="setting-center-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('settings.centerName') }}
              </label>
              <input 
                id="setting-center-name"
                name="center_name"
                type="text" 
                v-model="centerForm.name"
                :placeholder="$t('settings.centerNamePlaceholder')"
                required
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
              />
            </div>

            <!-- German Bundesland Dropdown -->
            <div>
              <label for="setting-center-bundesland" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('settings.bundesland') }}
              </label>
              <select 
                id="setting-center-bundesland"
                name="center_bundesland"
                v-model="centerForm.bundesland"
                required
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20"
              >
                <option 
                  v-for="state in settingsStore.availableBundeslaender" 
                  :key="state.code" 
                  :value="state.code"
                >
                  {{ state.name }} ({{ state.code }})
                </option>
              </select>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 flex items-center gap-1">
                <span>ℹ️</span> {{ $t('settings.bundeslandDesc') }}
              </p>
            </div>

            <div class="pt-2 flex justify-end">
              <button 
                type="submit"
                :disabled="settingsStore.isSaving"
                class="rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-6 py-2.5 text-sm font-semibold shadow-md transition-all cursor-pointer flex items-center gap-2"
              >
                <svg v-if="settingsStore.isSaving" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                {{ settingsStore.isSaving ? $t('common.save') + '...' : $t('common.save') }}
              </button>
            </div>
          </form>
        </div>

        <!-- Logo Upload & Contrast Container -->
        <div class="glass-panel rounded-2xl p-6 border border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl flex flex-col justify-between space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('settings.logo') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('settings.logoDesc') }}</p>
          </div>

          <!-- Logo Display Preview Box -->
          <div class="flex flex-col items-center justify-center p-6 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 text-center gap-4">
            <div class="w-32 h-32 rounded-2xl p-2.5 bg-white/90 dark:bg-white/10 dark:ring-1 dark:ring-white/20 shadow-inner flex items-center justify-center backdrop-blur-sm overflow-hidden">
              <img 
                v-if="localLogoPreview || settingsStore.centerLogoUrl" 
                :src="(localLogoPreview || settingsStore.centerLogoUrl) ?? undefined" 
                alt="Logo Preview" 
                class="max-w-full max-h-full object-contain"
              />
              <div v-else class="text-gray-300 dark:text-gray-600 flex flex-col items-center">
                <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-xs mt-1 font-medium">Kein Logo</span>
              </div>
            </div>

            <!-- Upload File Input -->
            <input 
              ref="logoFileInput" 
              type="file" 
              accept="image/png,image/jpeg,image/jpg,image/webp" 
              class="hidden" 
              @change="onLogoFileSelected" 
            />

            <div class="flex flex-wrap gap-2 justify-center">
              <button 
                type="button" 
                @click="logoFileInput?.click()" 
                :disabled="settingsStore.isUploadingLogo"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-sm transition-all cursor-pointer flex items-center gap-1.5"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                {{ settingsStore.centerLogoUrl ? $t('settings.changeLogo') : $t('settings.uploadLogo') }}
              </button>

              <button 
                v-if="settingsStore.centerLogoUrl"
                type="button" 
                @click="handleDeleteLogo" 
                :disabled="settingsStore.isUploadingLogo"
                class="px-3 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-950/30 dark:hover:bg-red-900/40 text-xs font-semibold transition-all cursor-pointer"
              >
                {{ $t('settings.removeLogo') }}
              </button>
            </div>
          </div>

          <div class="text-[11px] text-gray-400 dark:text-gray-500 text-center">
            Max. 3 MB • PNG / JPG / WebP
          </div>
        </div>

      </div>
    </div>

    <!-- ─── TAB 2: Subjects & Rooms ────────────────────────────────── -->
    <div v-else-if="activeTab === 'subjects_rooms'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1 overflow-hidden">
      
      <!-- Subjects Management -->
      <div class="glass-panel rounded-2xl flex flex-col overflow-hidden relative">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('teachers.subjects') }}</h2>
          <button @click="openCreateSubject" class="bg-purple-600 hover:bg-purple-700 text-white p-1.5 rounded-lg shadow-sm transition-colors cursor-pointer">
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
                <button @click="deleteSubject(subject)" class="text-gray-400 hover:text-red-500 transition-colors p-1 cursor-pointer">
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
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('settings.rooms') }}</h2>
          <button @click="openCreateRoom" class="bg-blue-600 hover:bg-blue-700 text-white p-1.5 rounded-lg shadow-sm transition-colors cursor-pointer">
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
                <div class="text-xs text-gray-500">{{ $t('settings.capacity') }}: {{ room.capacity }}</div>
              </div>
              <div class="flex gap-2">
                <button @click="deleteRoom(room)" class="text-gray-400 hover:text-red-500 transition-colors p-1 cursor-pointer">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- ─── TAB 3: Notification Preferences Grid ────────────────────── -->
    <div v-else-if="activeTab === 'notifications'" class="glass-panel rounded-2xl p-6 flex-1 overflow-y-auto space-y-6">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('settings.notifications') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $t('settings.notificationPreferences') }}</p>
      </div>

      <div v-if="isPreferencesLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>

      <div v-else class="space-y-6">
        <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-800/60">
              <tr>
                <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-900 dark:text-gray-200 uppercase tracking-wider">{{ $t('settings.actionType') }}</th>
                <th v-for="channel in channels" :key="channel.key" class="px-6 py-3.5 text-center text-xs font-semibold text-gray-900 dark:text-gray-200 uppercase tracking-wider">
                  {{ channel.label }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
              <tr v-for="type in defaultTypes" :key="type.key">
                <td class="px-6 py-4">
                  <div class="font-medium text-gray-900 dark:text-white text-sm">{{ type.label }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ type.description }}</div>
                </td>
                <td v-for="channel in channels" :key="channel.key" class="px-6 py-4 text-center">
                  <input 
                    type="checkbox" 
                    v-model="preferenceMatrix[type.key][channel.key]"
                    class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-600 cursor-pointer"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex justify-end">
          <button 
            @click="savePreferences"
            class="rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 text-sm font-semibold shadow-md transition-colors cursor-pointer"
          >
            {{ $t('common.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- ─── TAB 4: WhatsApp Settings Panel ─────────────────────────── -->
    <div v-else-if="activeTab === 'whatsapp_settings'" class="glass-panel rounded-2xl p-6 flex-1 overflow-y-auto space-y-6">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('settings.whatsapp') }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure credentials for outgoing WhatsApp messages.</p>
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
        </div>

        <div class="pt-4 flex justify-end">
          <button 
            type="submit"
            :disabled="isSavingSettings"
            class="rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-6 py-2.5 text-sm font-semibold shadow-md transition-colors cursor-pointer flex items-center gap-2"
          >
            <svg v-if="isSavingSettings" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            {{ $t('common.save') }}
          </button>
        </div>
      </form>
    </div>
    
    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/20/solid'
import { useI18n } from 'vue-i18n'
import { useSubjectsStore, type Subject } from '@/stores/subjectsStore'
import { useRoomsStore, type Room } from '@/stores/roomsStore'
import { useSettingsStore } from '@/stores/settingsStore'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import api from '@/api'
import { useToastStore } from '@/stores/toastStore'

const { t } = useI18n()
const subjectsStore = useSubjectsStore()
const roomsStore = useRoomsStore()
const settingsStore = useSettingsStore()
const toastStore = useToastStore()
const confirmModal = ref<InstanceType<typeof ConfirmModal> | null>(null)

// Tab handling (Defaults to General)
const activeTab = ref('general')

// ─── Center Profile Form ─────────────────────────────────────────
const centerForm = ref({
  name: '',
  bundesland: 'NW',
})

const logoFileInput = ref<HTMLInputElement | null>(null)
const localLogoPreview = ref<string | null>(null)

const currentBundeslandName = computed(() => {
  const match = settingsStore.availableBundeslaender.find(s => s.code === centerForm.value.bundesland)
  return match?.name || centerForm.value.bundesland
})

watch(() => settingsStore.center, (newCenter) => {
  if (newCenter) {
    centerForm.value.name = newCenter.name
    centerForm.value.bundesland = newCenter.bundesland
  }
}, { immediate: true })

async function handleSaveCenterProfile() {
  try {
    await settingsStore.saveCenterSettings({
      name: centerForm.value.name,
      bundesland: centerForm.value.bundesland,
    })
    toastStore.success(t('settings.settingsSaved'))
  } catch (err: any) {
    toastStore.error(err?.response?.data?.message || t('common.error'))
  }
}

async function onLogoFileSelected(e: Event) {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  if (file.size > 3 * 1024 * 1024) {
    toastStore.error(t('settings.logoDesc'))
    return
  }

  // Create immediate local object URL for instant UI response
  const objectUrl = URL.createObjectURL(file)
  localLogoPreview.value = objectUrl

  try {
    await settingsStore.uploadLogo(file)
    toastStore.success(t('settings.logoUpdated'))
  } catch (err: any) {
    localLogoPreview.value = null
    toastStore.error(err?.response?.data?.message || t('common.error'))
  } finally {
    if (logoFileInput.value) logoFileInput.value.value = ''
  }
}

function handleDeleteLogo() {
  confirmModal.value?.open(
    t('settings.removeLogo'),
    t('settings.removeLogo') + '?',
    t('common.delete'),
    t('common.cancel'),
    async () => {
      try {
        localLogoPreview.value = null
        await settingsStore.deleteLogo()
        toastStore.success(t('settings.logoRemoved'))
      } catch (err: any) {
        toastStore.error(t('common.error'))
      }
    }
  )
}

// ─── Preferences settings ─────────────────────────────────────────
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

// ─── Twilio WhatsApp Settings ─────────────────────────────────────
const twilioSid = ref('')
const twilioToken = ref('')
const twilioFrom = ref('')
const isSettingsLoading = ref(false)
const isSavingSettings = ref(false)

async function fetchRawSettings() {
  isSettingsLoading.value = true
  try {
    const res = await api.get('/settings')
    const raw = res.data.raw_settings || res.data
    twilioSid.value = raw.twilio_sid || ''
    twilioToken.value = raw.twilio_token || ''
    twilioFrom.value = raw.twilio_from || ''
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
  settingsStore.fetchSettings()
  subjectsStore.fetchSubjects()
  roomsStore.fetchRooms()
  fetchPreferences()
  fetchRawSettings()
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
