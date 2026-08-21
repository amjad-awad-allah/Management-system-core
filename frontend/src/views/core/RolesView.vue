<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
        {{ $t('settings.roles') }}
      </h1>
      <button @click="openCreateModal" class="inline-flex items-center rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors cursor-pointer">
        <ShieldCheckIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
        {{ $t('settings.addRole') }}
      </button>
    </div>

    <!-- Roles List -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="role in store.roles" :key="role.id" class="glass-panel rounded-2xl p-6 relative">
        <div class="flex justify-between items-start mb-4">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ role.name }}</h2>
          <div v-if="role.name !== 'Super Admin'" class="flex gap-2">
            <button @click="openEditModal(role)" class="text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 cursor-pointer">
              <PencilIcon class="h-5 w-5" />
            </button>
            <button @click="deleteRole(role)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 cursor-pointer">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <div v-else>
            <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-400/30">
              Protected
            </span>
          </div>
        </div>
        
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ $t('settings.permissions') }}:</h3>
          <div class="flex flex-wrap gap-2">
            <span v-if="role.name === 'Super Admin'" class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
              All Permissions (Implicit)
            </span>
            <span v-else-if="role.permissions.length === 0" class="text-xs text-gray-500">
              {{ $t('common.noData') }}
            </span>
            <span v-for="permission in role.permissions" :key="permission.id" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/30">
              {{ permission.name }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Modal -->
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
                  {{ isEditing ? $t('common.edit') : $t('settings.addRole') }}
                </DialogTitle>

                <form @submit.prevent="saveRole" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('settings.roleName') }}</label>
                    <input v-model="formData.name" type="text" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm p-2 border" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $t('settings.permissions') }}</label>
                    <div class="space-y-2 max-h-60 overflow-y-auto p-2 border border-gray-200 dark:border-gray-700 rounded-xl">
                      <div v-for="permission in store.allPermissions" :key="permission.id" class="flex items-center">
                        <input type="checkbox" :id="permission.id" :value="permission.name" v-model="formData.permissions" class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-600 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800" />
                        <label :for="permission.id" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">{{ permission.name }}</label>
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
import { ref, onMounted } from 'vue'
import { useRolesStore } from '@/stores/rolesStore'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ShieldCheckIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'

const store = useRolesStore()

const isModalOpen = ref(false)
const isEditing = ref(false)
const editId = ref('')

const formData = ref({
  name: '',
  permissions: [] as string[]
})

onMounted(() => {
  store.fetchRoles()
  store.fetchPermissions()
})

function openCreateModal() {
  isEditing.value = false
  editId.value = ''
  formData.value = { name: '', permissions: [] }
  isModalOpen.value = true
}

function openEditModal(role: any) {
  isEditing.value = true
  editId.value = role.id
  formData.value = {
    name: role.name,
    permissions: role.permissions.map((p: any) => p.name)
  }
  isModalOpen.value = true
}

function closeModal() {
  isModalOpen.value = false
}

async function saveRole() {
  let success = false
  if (isEditing.value) {
    success = await store.updateRole(editId.value, formData.value)
  } else {
    success = await store.createRole(formData.value)
  }
  
  if (success) {
    closeModal()
  }
}

async function deleteRole(role: any) {
  if (confirm(`Are you sure you want to delete the role '${role.name}'?`)) {
    await store.deleteRole(role.id)
  }
}
</script>
