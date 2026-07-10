<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">User Management</h1>
      <button @click="openCreateModal" class="inline-flex items-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors">
        <UserPlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
        Add User
      </button>
    </div>

    <!-- Users Table -->
    <div class="glass-panel rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800">
      <div v-if="store.isLoading" class="p-12 flex justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50/50 dark:bg-gray-800/50">
          <tr>
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">Name</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Email</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Roles</th>
            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
          <tr v-for="user in store.users" :key="user.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6">{{ user.name }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
              <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-400/30 mr-1">
                {{ role.name }}
              </span>
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <button @click="openEditModal(user)" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 mr-4">Edit</button>
              <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
            </td>
          </tr>
          <tr v-if="store.users.length === 0">
            <td colspan="4" class="py-8 text-center text-sm text-gray-500">No users found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- User Modal -->
    <TransitionRoot appear :show="isModalOpen" as="template">
      <Dialog as="div" @close="closeModal" class="relative z-50">
        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-gray-900/25 backdrop-blur-sm" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all border border-gray-100 dark:border-gray-700">
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">
                  {{ isEditing ? 'Edit User' : 'Add New User' }}
                </DialogTitle>

                <form @submit.prevent="saveUser" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input v-model="formData.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm p-2 border" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input v-model="formData.email" type="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm p-2 border" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Password <span v-if="isEditing" class="text-xs text-gray-400 font-normal">(Leave blank to keep current)</span>
                    </label>
                    <input v-model="formData.password" type="password" :required="!isEditing" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm p-2 border" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
                    <div class="space-y-2">
                      <div v-for="role in rolesStore.roles" :key="role.id" class="flex items-center">
                        <input type="checkbox" :id="role.id" :value="role.name" v-model="formData.roles" class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-600 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800" />
                        <label :for="role.id" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">{{ role.name }}</label>
                      </div>
                    </div>
                  </div>

                  <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Save</button>
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
import { ref, onMounted } from 'vue'
import { useUsersStore } from '@/stores/usersStore'
import { useRolesStore } from '@/stores/rolesStore'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { UserPlusIcon } from '@heroicons/vue/24/outline'

const store = useUsersStore()
const rolesStore = useRolesStore()

const isModalOpen = ref(false)
const isEditing = ref(false)
const editId = ref('')

const formData = ref({
  name: '',
  email: '',
  password: '',
  roles: [] as string[]
})

onMounted(() => {
  store.fetchUsers()
  rolesStore.fetchRoles()
})

function openCreateModal() {
  isEditing.value = false
  editId.value = ''
  formData.value = { name: '', email: '', password: '', roles: [] }
  isModalOpen.value = true
}

function openEditModal(user: any) {
  isEditing.value = true
  editId.value = user.id
  formData.value = {
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
  let success = false
  if (isEditing.value) {
    success = await store.updateUser(editId.value, formData.value)
  } else {
    success = await store.createUser(formData.value)
  }
  
  if (success) {
    closeModal()
  }
}

async function deleteUser(user: any) {
  if (confirm(`Are you sure you want to delete ${user.name}?`)) {
    await store.deleteUser(user.id)
  }
}
</script>
