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
  CalendarIcon,
  UsersIcon,
  AcademicCapIcon,
  Bars3Icon,
  SunIcon,
  MoonIcon,
  UserGroupIcon,
  CurrencyEuroIcon,
  BriefcaseIcon,
  CurrencyDollarIcon,
  BookOpenIcon,
  ArrowRightOnRectangleIcon,
  ShieldCheckIcon,
  ClipboardDocumentListIcon
} from '@heroicons/vue/24/outline'
import { useUiStore } from '@/stores/uiStore'
import { useRouter } from 'vue-router'
import api from '@/api'
import { Cog6ToothIcon } from '@heroicons/vue/24/outline'

const uiStore = useUiStore()
const router = useRouter()

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
  { name: 'Students', href: '/students', icon: UsersIcon },
  { name: 'Teachers', href: '/teachers', icon: AcademicCapIcon },
  { name: 'Packages', href: '/packages', icon: BriefcaseIcon },
  { name: 'Invoices', href: '/invoices', icon: CurrencyDollarIcon },
  { name: 'Lessons', href: '/lessons', icon: BookOpenIcon },
  { name: 'Settings', href: '/settings', icon: Cog6ToothIcon },
  { name: 'Users', href: '/users', icon: UsersIcon },
  { name: 'Roles', href: '/roles', icon: ShieldCheckIcon },
  { name: 'Audit Logs', href: '/audit-logs', icon: ClipboardDocumentListIcon },
]
</script>
