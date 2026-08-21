<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
        {{ $t('settings.users') }}
      </h1>
      <button @click="openCreateModal" class="inline-flex items-center rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors cursor-pointer">
        <UserPlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
        {{ $t('settings.addUser') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-800">
      <nav class="-mb-px flex space-x-6" aria-label="Tabs">
        <button
          @click="activeTab = 'staff'"
          type="button"
          :class="[
            activeTab === 'staff'
              ? 'border-purple-500 text-purple-600 dark:text-purple-400'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold cursor-pointer transition-all'
          ]"
        >
          Staff & Administrators
        </button>
        <button
          @click="activeTab = 'students'"
          type="button"
          :class="[
            activeTab === 'students'
              ? 'border-purple-500 text-purple-600 dark:text-purple-400'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold cursor-pointer transition-all'
          ]"
        >
          {{ $t('students.title') }}
        </button>
      </nav>
    </div>

    <!-- Users Table -->
    <div class="glass-panel rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800">
      <div v-if="store.isLoading" class="p-12 flex justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50/50 dark:bg-gray-800/50">
          <tr>
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">
              {{ $t('settings.userName') }}
            </th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ $t('settings.userEmail') }}
            </th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ $t('settings.userRole') }}
            </th>
            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
              <span class="sr-only">{{ $t('common.actions') }}</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
          <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6">{{ user.name }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
              <div class="flex flex-wrap gap-1">
                <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-400/30">
                  {{ role.name }}
                </span>
              </div>
            </td>
            <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <div class="flex justify-end gap-2">
                <button @click="openEditModal(user)" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 cursor-pointer">
                  <PencilIcon class="h-5 w-5" />
                </button>
                <button v-if="user.id !== authStore.user?.id" @click="deleteUser(user)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 cursor-pointer">
                  <TrashIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- User Modal (Create/Edit) -->
    <TransitionRoot appear :show="isModalOpen" as="template">
      <Dialog as="div" @close="closeModal" class="relative z-50">
        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all border border-gray-100 dark:border-gray-700">
                <DialogTitle as="h3" class="text-lg font-bold leading-6 text-gray-900 dark:text-white">
                  {{ isEditing ? $t('common.edit') : $t('settings.addUser') }}
                </DialogTitle>
                
                <form @submit.prevent="saveUser" class="mt-4 space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('settings.userName') }}</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500 dark:bg-gray-700 sm:text-sm" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('settings.userEmail') }}</label>
                    <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500 dark:bg-gray-700 sm:text-sm" />
                  </div>

                  <div v-if="!isEditing">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input v-model="form.password" type="password" required class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500 dark:bg-gray-700 sm:text-sm" />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('settings.userRole') }}</label>
                    <div class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl p-3">
                      <div v-for="role in rolesStore.roles" :key="role.id" class="flex items-center">
                        <input :id="'role-' + role.id" v-model="form.roles" :value="role.name" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900" />
                        <label :for="'role-' + role.id" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ role.name }}</label>
                      </div>
                    </div>
                  </div>

                  <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer">
                      {{ $t('common.cancel') }}
                    </button>
                    <button type="submit" class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 cursor-pointer">
                      {{ $t('common.save') }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useUsersStore } from '@/stores/usersStore'
import { useRolesStore } from '@/stores/rolesStore'
import { useAuthStore } from '@/stores/authStore'
import { UserPlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const store = useUsersStore()
const rolesStore = useRolesStore()
const authStore = useAuthStore()

const activeTab = ref<'staff' | 'students'>('staff')
const isModalOpen = ref(false)
const isEditing = ref(false)
const selectedUserId = ref<string | null>(null)

const form = ref({
  name: '',
  email: '',
  password: '',
  roles: [] as string[]
})

const filteredUsers = computed(() => {
  if (activeTab.value === 'staff') {
    return (store.users as any[]).filter((u: any) => !u.roles?.some((r: any) => r.name === 'student' || r.name === 'parent'))
  } else {
    return (store.users as any[]).filter((u: any) => u.roles?.some((r: any) => r.name === 'student' || r.name === 'parent'))
  }
})

onMounted(() => {
  store.fetchUsers()
  rolesStore.fetchRoles()
})

function openCreateModal() {
  isEditing.value = false
  selectedUserId.value = null
  form.value = { name: '', email: '', password: '', roles: [] }
  isModalOpen.value = true
}

function openEditModal(user: any) {
  isEditing.value = true
  selectedUserId.value = user.id
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    roles: user.roles.map((r: any) => r.name)
  }
  isModalOpen.value = true
}

function closeModal() {
  isModalOpen.value = false
}

async function saveUser() {
  if (isEditing.value && selectedUserId.value) {
    await store.updateUser(selectedUserId.value, { roles: form.value.roles })
  } else {
    await store.createUser(form.value)
  }
  closeModal()
}

async function deleteUser(user: any) {
  if (confirm(`Are you sure you want to delete ${user.name}?`)) {
    await store.deleteUser(user.id)
  }
}
</script>
