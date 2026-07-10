<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Packages</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Manage billing packages and pricing.</p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <button @click="openCreateSlideOver" type="button" class="block rounded-md bg-purple-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors">
          <span class="flex items-center gap-2">
            <PlusIcon class="h-4 w-4" />
            Add Package
          </span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="store.isLoading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!store.packages.length" class="text-center bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12">
      <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No packages</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new billing package.</p>
      <div class="mt-6">
        <button @click="openCreateSlideOver" type="button" class="inline-flex items-center rounded-md bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors">
          <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
          New Package
        </button>
      </div>
    </div>

    <!-- Packages List -->
    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="pkg in store.packages" :key="pkg.id" class="glass-panel rounded-2xl p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ pkg.name }}</h3>
            <span v-if="pkg.is_active" class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/20 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20">Active</span>
            <span v-else class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-800 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-500/10">Inactive</span>
          </div>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-2 min-h-[2.5rem]">{{ pkg.description || 'No description provided.' }}</p>
          <div class="mt-4 flex items-center gap-x-4 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex flex-col">
              <span class="font-semibold text-xl">{{ pkg.hours }}</span>
              <span class="text-xs text-gray-500">Hours</span>
            </div>
            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
            <div class="flex flex-col">
              <span class="font-semibold text-xl text-green-600 dark:text-green-400">€{{ pkg.price }}</span>
              <span class="text-xs text-gray-500">Price</span>
            </div>
          </div>
        </div>
        <div class="mt-6 flex gap-3">
          <button @click="openEditSlideOver(pkg)" class="flex-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            Edit
          </button>
          <button @click="confirmDelete(pkg.id)" class="flex-none bg-white dark:bg-gray-800 border border-red-300 dark:border-red-900/50 text-red-600 dark:text-red-400 px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
            Delete
          </button>
        </div>
      </div>
    </div>

    <CreatePackageSlideOver ref="createSlideOver" />
    <EditPackageSlideOver ref="editSlideOver" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { PlusIcon } from '@heroicons/vue/20/solid'
import { usePackagesStore, type Package } from '@/stores/packagesStore'
import CreatePackageSlideOver from '@/components/packages/CreatePackageSlideOver.vue'
import EditPackageSlideOver from '@/components/packages/EditPackageSlideOver.vue'

const store = usePackagesStore()
const createSlideOver = ref<InstanceType<typeof CreatePackageSlideOver> | null>(null)
const editSlideOver = ref<InstanceType<typeof EditPackageSlideOver> | null>(null)

onMounted(() => {
  store.fetchPackages()
})

function openCreateSlideOver() {
  createSlideOver.value?.open()
}

function openEditSlideOver(pkg: Package) {
  editSlideOver.value?.open(pkg)
}

function confirmDelete(id: string) {
  if (confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
    store.deletePackage(id)
  }
}
</script>
