<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog as="div" class="relative z-50" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-gray-900 p-6 sm:p-8 text-left shadow-2xl transition-all w-full max-w-lg border border-gray-100 dark:border-gray-800">
              
              <!-- Header -->
              <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-blue-500 flex items-center justify-center text-white text-xl font-bold shadow-md">
                    {{ userInitials }}
                  </div>
                  <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ authStore.user?.name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ userRoles }}</p>
                  </div>
                </div>
                <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>

              <!-- Tabs: Profile Info / Security -->
              <div class="flex gap-2 border-b border-gray-100 dark:border-gray-800 my-4">
                <button
                  @click="activeTab = 'profile'"
                  :class="[
                    activeTab === 'profile'
                      ? 'border-purple-600 text-purple-600 dark:text-purple-400'
                      : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                    'pb-2 px-3 text-xs font-bold border-b-2 transition-colors cursor-pointer'
                  ]"
                >
                  Profil & Daten
                </button>
                <button
                  @click="activeTab = 'security'"
                  :class="[
                    activeTab === 'security'
                      ? 'border-purple-600 text-purple-600 dark:text-purple-400'
                      : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                    'pb-2 px-3 text-xs font-bold border-b-2 transition-colors cursor-pointer'
                  ]"
                >
                  Passwort ändern
                </button>
              </div>

              <!-- TAB 1: Profile Form -->
              <form v-if="activeTab === 'profile'" @submit.prevent="handleUpdateProfile" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Vollständiger Name</label>
                  <input
                    v-model="profileForm.name"
                    type="text"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">E-Mail-Adresse</label>
                  <input
                    v-model="profileForm.email"
                    type="email"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>

                <div class="pt-2 flex justify-end gap-2">
                  <button
                    type="button"
                    @click="close"
                    class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition cursor-pointer"
                  >
                    Abbrechen
                  </button>
                  <button
                    type="submit"
                    :disabled="isSavingProfile"
                    class="px-5 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-md shadow-purple-500/20 disabled:opacity-50 transition cursor-pointer"
                  >
                    {{ isSavingProfile ? 'Speichert...' : 'Profil aktualisieren' }}
                  </button>
                </div>
              </form>

              <!-- TAB 2: Password Form -->
              <form v-if="activeTab === 'security'" @submit.prevent="handleUpdatePassword" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Aktuelles Passwort</label>
                  <input
                    v-model="passwordForm.current_password"
                    type="password"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Neues Passwort (min. 8 Zeichen)</label>
                  <input
                    v-model="passwordForm.new_password"
                    type="password"
                    required
                    minlength="8"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Neues Passwort bestätigen</label>
                  <input
                    v-model="passwordForm.new_password_confirmation"
                    type="password"
                    required
                    minlength="8"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>

                <div class="pt-2 flex justify-end gap-2">
                  <button
                    type="button"
                    @click="close"
                    class="px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition cursor-pointer"
                  >
                    Abbrechen
                  </button>
                  <button
                    type="submit"
                    :disabled="isSavingPassword"
                    class="px-5 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-md shadow-purple-500/20 disabled:opacity-50 transition cursor-pointer"
                  >
                    {{ isSavingPassword ? 'Aktualisiert...' : 'Passwort speichern' }}
                  </button>
                </div>
              </form>

            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/authStore'
import { useToastStore } from '@/stores/toastStore'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
}>()

const authStore = useAuthStore()
const toast = useToastStore()

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const activeTab = ref<'profile' | 'security'>('profile')
const isSavingProfile = ref(false)
const isSavingPassword = ref(false)

const profileForm = ref({
  name: '',
  email: ''
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

watch(() => authStore.user, (u) => {
  if (u) {
    profileForm.value.name = u.name || ''
    profileForm.value.email = u.email || ''
  }
}, { immediate: true })

const userInitials = computed(() => {
  const n = authStore.user?.name || 'U'
  return n.split(' ').map((p: string) => p[0]).join('').substring(0, 2).toUpperCase()
})

const userRoles = computed(() => {
  const roles = authStore.user?.roles || []
  return Array.isArray(roles) ? roles.join(', ') : 'User'
})

function close() {
  isOpen.value = false
  passwordForm.value = {
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  }
}

async function handleUpdateProfile() {
  isSavingProfile.value = true
  try {
    await authStore.updateProfile({
      name: profileForm.value.name,
      email: profileForm.value.email
    })
    toast.success('Erfolg', 'Profil erfolgreich aktualisiert.')
    close()
  } catch (err: any) {
    toast.error('Fehler', err?.response?.data?.message || 'Fehler beim Aktualisieren des Profils.')
  } finally {
    isSavingProfile.value = false
  }
}

async function handleUpdatePassword() {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    toast.error('Fehler', 'Die Passwörter stimmen nicht überein.')
    return
  }
  isSavingPassword.value = true
  try {
    await authStore.updatePassword({
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation
    })
    toast.success('Erfolg', 'Passwort erfolgreich geändert.')
    close()
  } catch (err: any) {
    toast.error('Fehler', err?.response?.data?.message || 'Aktuelles Passwort ist nicht korrekt.')
  } finally {
    isSavingPassword.value = false
  }
}
</script>
