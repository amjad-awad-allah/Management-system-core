<template>
  <div class="h-full flex bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Mobile Sidebar -->
    <TransitionRoot as="template" :show="uiStore.isSidebarOpen">
      <Dialog as="div" class="relative z-50 lg:hidden" @close="uiStore.setSidebarOpen(false)">
        <TransitionChild
          as="template"
          enter="transition-opacity ease-linear duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="transition-opacity ease-linear duration-300"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 flex">
          <TransitionChild
            as="template"
            enter="transition ease-in-out duration-300 transform"
            enter-from="-translate-x-full"
            enter-to="translate-x-0"
            leave="transition ease-in-out duration-300 transform"
            leave-from="translate-x-0"
            leave-to="-translate-x-full"
          >
            <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
              <!-- Sidebar content -->
              <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 px-6 pb-4 shadow-2xl">
                <div class="flex h-16 shrink-0 items-center">
                  <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-blue-500 bg-clip-text text-transparent">Nachhilfe Admin</span>
                </div>
                <nav class="flex flex-1 flex-col">
                  <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                      <ul role="list" class="-mx-2 space-y-1">
                        <li v-for="item in navigation" :key="item.name">
                          <router-link
                            :to="item.href"
                            :class="[
                              $route.path === item.href
                                ? 'bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400'
                                : 'text-gray-700 hover:text-purple-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-purple-400 dark:hover:bg-gray-800',
                              'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold transition-all duration-200'
                            ]"
                            @click="uiStore.setSidebarOpen(false)"
                          >
                            <component :is="item.icon" class="h-6 w-6 shrink-0" aria-hidden="true" />
                            {{ item.name }}
                            <span
                              v-if="item.name === 'Messages' && messagingStore.totalUnread > 0"
                              class="ml-auto bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"
                            >
                              {{ messagingStore.totalUnread }}
                            </span>
                          </router-link>
                        </li>
                      </ul>
                    </li>
                  </ul>
                </nav>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Static Sidebar for desktop -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
      <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50 backdrop-blur-xl px-6 pb-4">
        <div class="flex h-16 shrink-0 items-center">
          <span class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-blue-500 bg-clip-text text-transparent">Nachhilfe Admin</span>
        </div>
        <nav class="flex flex-1 flex-col">
          <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
              <ul role="list" class="-mx-2 space-y-2">
                <li v-for="item in navigation" :key="item.name">
                  <router-link
                    :to="item.href"
                    :class="[
                      $route.path === item.href
                        ? 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 shadow-sm'
                        : 'text-gray-700 hover:text-purple-600 hover:bg-gray-50/50 dark:text-gray-400 dark:hover:text-purple-400 dark:hover:bg-gray-800/50',
                      'group flex gap-x-3 rounded-xl p-3 text-sm leading-6 font-semibold transition-all duration-200'
                    ]"
                  >
                    <component :is="item.icon" class="h-6 w-6 shrink-0" aria-hidden="true" />
                    {{ item.name }}
                    <span
                      v-if="item.name === 'Messages' && messagingStore.totalUnread > 0"
                      class="ml-auto bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"
                    >
                      {{ messagingStore.totalUnread }}
                    </span>
                  </router-link>
                </li>
              </ul>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Main Content -->
    <div class="lg:pl-72 w-full flex flex-col min-w-0">
      <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50 backdrop-blur-md px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
        <button type="button" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="uiStore.setSidebarOpen(true)">
          <span class="sr-only">Open sidebar</span>
          <Bars3Icon class="h-6 w-6" aria-hidden="true" />
        </button>

        <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 justify-end items-center">
          <!-- Theme Toggle -->
          <button @click="uiStore.toggleDarkMode()" class="p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
            <SunIcon v-if="uiStore.isDarkMode" class="w-6 h-6" />
            <MoonIcon v-else class="w-6 h-6" />
          </button>

          <!-- Notification Bell -->
          <div class="relative" ref="notifDropdownRef">
            <button
              @click="toggleNotifications"
              class="relative p-2 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
              aria-label="Notifications"
            >
              <BellIcon class="w-6 h-6" />
              <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-gray-900"
              >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </button>

            <!-- Dropdown -->
            <Transition
              enter-active-class="transition ease-out duration-150"
              enter-from-class="opacity-0 translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition ease-in duration-100"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div
                v-if="showNotifications"
                class="absolute right-0 mt-2 w-96 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-900/5 dark:ring-gray-800 overflow-hidden z-50"
              >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-sm font-bold text-gray-900 dark:text-white">Notifications</span>
                  <button
                    v-if="unreadCount > 0"
                    @click="markAllRead"
                    class="text-xs text-purple-600 hover:text-purple-700 dark:text-purple-400 font-medium"
                  >
                    Mark all read
                  </button>
                </div>

                <!-- List -->
                <div class="max-h-96 overflow-y-auto">
                  <div v-if="isLoadingNotifications" class="flex justify-center items-center py-10">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div>
                  </div>

                  <div v-else-if="globalNotifications.length === 0" class="text-center py-10">
                    <BellIcon class="w-8 h-8 text-gray-300 dark:text-gray-700 mx-auto mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
                  </div>

                  <div v-else>
                    <div
                      v-for="notif in globalNotifications"
                      :key="notif.id"
                      @click="handleNotificationClick(notif)"
                      class="flex items-start gap-3 px-4 py-3 border-b border-gray-50 dark:border-gray-800/60 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors cursor-pointer"
                      :class="{ 'bg-purple-50/40 dark:bg-purple-900/10': !notif.read_at }"
                    >
                      <!-- Icon -->
                      <div class="flex-shrink-0 mt-0.5">
                        <div
                          class="w-8 h-8 rounded-full flex items-center justify-center"
                          :class="notif.priority === 'high' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30'"
                        >
                          <BellIcon
                            class="w-4 h-4"
                            :class="notif.priority === 'high' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400'"
                          />
                        </div>
                      </div>
                      <!-- Content -->
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ notif.title }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ notif.message }}</p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-600 mt-1">{{ formatRelativeTime(notif.created_at) }}</p>
                      </div>
                      <!-- Unread dot -->
                      <div v-if="!notif.read_at" class="flex-shrink-0 mt-2">
                        <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                  <p class="text-[11px] text-gray-400 dark:text-gray-600 text-center">
                    Showing latest {{ globalNotifications.length }} notifications
                  </p>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Logout -->
          <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>
          <button @click="handleLogout" class="flex items-center gap-2 p-2 text-sm font-semibold text-gray-700 hover:text-red-600 dark:text-gray-300 dark:hover:text-red-400 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
            <span class="hidden sm:inline">Logout</span>
          </button>
        </div>
      </div>

      <main class="flex-1 min-w-0 overflow-y-auto p-4 sm:p-6 lg:p-8">
        <router-view v-slot="{ Component }">
          <transition 
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
            mode="out-in"
          >
            <component :is="Component" :key="$route.fullPath" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  HomeIcon,
  UsersIcon,
  AcademicCapIcon,
  Bars3Icon,
  SunIcon,
  MoonIcon,
  CurrencyEuroIcon,
  CurrencyDollarIcon,
  BookOpenIcon,
  ArrowRightOnRectangleIcon,
  ShieldCheckIcon,
  ClipboardDocumentListIcon,
  BellIcon,
  ChatBubbleOvalLeftEllipsisIcon
} from '@heroicons/vue/24/outline'
import { useUiStore } from '@/stores/uiStore'
import { useMessagingStore } from '@/stores/messagingStore'
import { useRouter } from 'vue-router'
import api from '@/api'
import { Cog6ToothIcon } from '@heroicons/vue/24/outline'
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'

const uiStore = useUiStore()
const messagingStore = useMessagingStore()
const router = useRouter()

// ─── Notification Bell ─────────────────────────────────────────────────────────
const showNotifications = ref(false)
const isLoadingNotifications = ref(false)
const globalNotifications = ref<any[]>([])
const notifDropdownRef = ref<HTMLElement | null>(null)

const unreadCount = computed(() => globalNotifications.value.filter((n: any) => !n.read_at).length)

async function fetchGlobalNotifications() {
  isLoadingNotifications.value = true
  try {
    const res = await api.get('/notifications')
    globalNotifications.value = res.data.data ?? res.data
  } catch (e) {
    // Silent – user may not be authenticated yet
  } finally {
    isLoadingNotifications.value = false
  }
}

async function toggleNotifications() {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value && globalNotifications.value.length === 0) {
    await fetchGlobalNotifications()
  }
}

async function markAllRead() {
  try {
    await api.post('/notifications/read-all')
    globalNotifications.value = globalNotifications.value.map((n: any) => ({ ...n, read_at: new Date().toISOString() }))
  } catch (e) {
    console.error(e)
  }
}

async function handleNotificationClick(notif: any) {
  if (!notif.read_at) {
    try {
      await api.post(`/notifications/${notif.id}/read`)
      notif.read_at = new Date().toISOString()
    } catch (e) {
      console.error(e)
    }
  }

  showNotifications.value = false

  const channelId = notif.metadata?.channel_id
  if (notif.type === 'new_chat_message' || notif.source_type === 'chat_message' || channelId) {
    if (channelId) {
      await messagingStore.selectChannel(channelId)
    }
    router.push('/messaging')
  } else if (notif.source_type === 'invoice') {
    router.push('/invoices')
  } else if (notif.source_type === 'student') {
    router.push('/students')
  } else if (notif.source_type === 'teacher') {
    router.push('/teachers')
  } else {
    router.push('/messaging')
  }
}

function formatRelativeTime(dateStr: string): string {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}

function handleClickOutside(event: MouseEvent) {
  if (notifDropdownRef.value && !notifDropdownRef.value.contains(event.target as Node)) {
    showNotifications.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  // Prefetch notifications count on load
  fetchGlobalNotifications()
  // Prefetch messaging channels on load
  messagingStore.fetchMyChannels()
  messagingStore.fetchAllChannels()
})
onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
// ─── End Notification Bell ──────────────────────────────────────────────────────

async function handleLogout() {
  try {
    await api.post('/logout')
  } catch (e) {
    console.error(e)
  }
  localStorage.removeItem('auth_token')
  router.push('/login')
}

const navigation = [
  { name: 'Dashboard', href: '/', icon: HomeIcon },
  { name: 'Messages', href: '/messaging', icon: ChatBubbleOvalLeftEllipsisIcon },
  { name: 'Students', href: '/students', icon: UsersIcon },
  { name: 'Teachers', href: '/teachers', icon: AcademicCapIcon },
  { name: 'Invoices', href: '/invoices', icon: CurrencyDollarIcon },
  { name: 'Payrolls', href: '/payrolls', icon: CurrencyEuroIcon },
  { name: 'Lessons', href: '/lessons', icon: BookOpenIcon },
  { name: 'Settings', href: '/settings', icon: Cog6ToothIcon },
  { name: 'Users', href: '/users', icon: UsersIcon },
  { name: 'Roles', href: '/roles', icon: ShieldCheckIcon },
  { name: 'Audit Logs', href: '/audit-logs', icon: ClipboardDocumentListIcon },
]
</script>
