import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export interface GermanBundeslandOption {
  code: string
  name: string
}

export interface CenterBranding {
  name: string
  bundesland: string
  bundesland_name: string
  logo_url: string | null
}

export const useSettingsStore = defineStore('settings', () => {
  const center = ref<CenterBranding>({
    name: 'Muster Nachhilfeinstitut',
    bundesland: 'NW',
    bundesland_name: 'Nordrhein-Westfalen',
    logo_url: null,
  })

  const availableBundeslaender = ref<GermanBundeslandOption[]>([
    { code: 'BW', name: 'Baden-Württemberg' },
    { code: 'BY', name: 'Bayern' },
    { code: 'BE', name: 'Berlin' },
    { code: 'BB', name: 'Brandenburg' },
    { code: 'HB', name: 'Bremen' },
    { code: 'HH', name: 'Hamburg' },
    { code: 'HE', name: 'Hessen' },
    { code: 'MV', name: 'Mecklenburg-Vorpommern' },
    { code: 'NI', name: 'Niedersachsen' },
    { code: 'NW', name: 'Nordrhein-Westfalen' },
    { code: 'RP', name: 'Rheinland-Pfalz' },
    { code: 'SL', name: 'Saarland' },
    { code: 'SN', name: 'Sachsen' },
    { code: 'ST', name: 'Sachsen-Anhalt' },
    { code: 'SH', name: 'Schleswig-Holstein' },
    { code: 'TH', name: 'Thüringen' },
  ])

  const canManage = ref(false)
  const isLoading = ref(false)
  const isSaving = ref(false)
  const isUploadingLogo = ref(false)

  const centerName = computed(() => center.value.name)
  const centerBundesland = computed(() => center.value.bundesland)
  const centerBundeslandName = computed(() => center.value.bundesland_name)
  const centerLogoUrl = computed(() => center.value.logo_url)

  /**
   * Fetch center branding and configuration from server.
   */
  async function fetchSettings() {
    isLoading.value = true
    try {
      const res = await api.get('/settings')
      if (res.data.center) {
        center.value = {
          name: res.data.center.name || 'Muster Nachhilfeinstitut',
          bundesland: res.data.center.bundesland || 'NW',
          bundesland_name: res.data.center.bundesland_name || 'Nordrhein-Westfalen',
          logo_url: res.data.center.logo_url || null,
        }
      }
      if (Array.isArray(res.data.available_bundeslaender) && res.data.available_bundeslaender.length > 0) {
        availableBundeslaender.value = res.data.available_bundeslaender
      }
      if (res.data.permissions) {
        canManage.value = Boolean(res.data.permissions.can_manage)
      }
    } catch (e) {
      console.warn('Failed to load center settings', e)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Save center branding & bundesland.
   */
  async function saveCenterSettings(payload: { name: string; bundesland: string }) {
    isSaving.value = true
    try {
      const res = await api.post('/settings', {
        settings: [
          { key: 'center_name', value: payload.name },
          { key: 'center_bundesland', value: payload.bundesland },
        ],
      })
      if (res.data?.data?.center) {
        center.value = {
          name: res.data.data.center.name,
          bundesland: res.data.data.center.bundesland,
          bundesland_name: res.data.data.center.bundesland_name,
          logo_url: res.data.data.center.logo_url,
        }
      }
      return true
    } catch (e) {
      console.error('Failed to save center settings', e)
      throw e
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Upload logo file.
   */
  async function uploadLogo(file: File): Promise<string> {
    isUploadingLogo.value = true
    try {
      const formData = new FormData()
      formData.append('logo', file)
      const res = await api.post('/settings/logo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      if (res.data?.logo_url) {
        center.value.logo_url = res.data.logo_url
      }
      return res.data?.logo_url || ''
    } catch (e) {
      console.error('Failed to upload logo', e)
      throw e
    } finally {
      isUploadingLogo.value = false
    }
  }

  /**
   * Delete center logo.
   */
  async function deleteLogo(): Promise<void> {
    isUploadingLogo.value = true
    try {
      await api.delete('/settings/logo')
      center.value.logo_url = null
    } catch (e) {
      console.error('Failed to delete logo', e)
      throw e
    } finally {
      isUploadingLogo.value = false
    }
  }

  return {
    center,
    availableBundeslaender,
    canManage,
    isLoading,
    isSaving,
    isUploadingLogo,
    centerName,
    centerBundesland,
    centerBundeslandName,
    centerLogoUrl,
    fetchSettings,
    saveCenterSettings,
    uploadLogo,
    deleteLogo,
  }
})
