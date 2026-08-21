<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
          {{ $t('settings.packages') }}
        </h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          {{ $t('students.subtitle') }}
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <button @click="openCreateSlideOver" type="button" class="block rounded-xl bg-purple-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors cursor-pointer">
          <span class="flex items-center gap-2">
            <PlusIcon class="h-4 w-4" />
            {{ $t('settings.addPackage') }}
          </span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="store.isLoading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!store.packages.length" class="text-center bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-12">
      <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('students.noPackages') }}</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('students.subtitle') }}</p>
      <div class="mt-6">
        <button @click="openCreateSlideOver" type="button" class="inline-flex items-center rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors cursor-pointer">
          <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
          {{ $t('settings.addPackage') }}
        </button>
      </div>
    </div>

    <!-- Packages List -->
    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="pkg in store.packages" :key="pkg.id" class="glass-panel rounded-2xl p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ pkg.name }}</h3>
            <span v-if="pkg.is_active" class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/20 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-600/20">
              {{ $t('common.active') }}
            </span>
            <span v-else class="inline-flex items-center rounded-md bg-gray-50 dark:bg-gray-800 px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-500/10">
              {{ $t('common.inactive') }}
            </span>
          </div>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-2 min-h-[2.5rem]">{{ pkg.description || '' }}</p>
          <div class="mt-4 flex items-center gap-x-4 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex flex-col">
              <span class="font-semibold text-xl">{{ pkg.hours }}</span>
              <span class="text-xs text-gray-500">{{ $t('students.totalHours') }}</span>
            </div>
            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
            <div class="flex flex-col">
              <span class="font-semibold text-xl text-green-600 dark:text-green-400">{{ formatCurrency(pkg.price) }}</span>
              <span class="text-xs text-gray-500">{{ $t('billing.amount') }}</span>
            </div>
          </div>
        </div>
        <div class="mt-6 flex gap-3">
          <button @click="openEditSlideOver(pkg)" class="flex-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer">
            {{ $t('common.edit') }}
          </button>
          <button @click="handleDelete(pkg.id)" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-2 rounded-xl border border-red-200 dark:border-red-800/40 hover:bg-red-100 transition-colors cursor-pointer">
            <TrashIcon class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- SlideOvers -->
    <CreatePackageSlideOver ref="createSlideOverRef" />
    <EditPackageSlideOver ref="editSlideOverRef" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePackagesStore } from '@/stores/packagesStore'
import { useFormatters } from '@/composables/useFormatters'
import type { Package } from '@/stores/packagesStore'
import { PlusIcon, TrashIcon } from '@heroicons/vue/20/solid'
import CreatePackageSlideOver from '@/components/packages/CreatePackageSlideOver.vue'
import EditPackageSlideOver from '@/components/packages/EditPackageSlideOver.vue'

const store = usePackagesStore()
const { formatCurrency } = useFormatters()
const createSlideOverRef = ref<InstanceType<typeof CreatePackageSlideOver> | null>(null)
const editSlideOverRef = ref<InstanceType<typeof EditPackageSlideOver> | null>(null)

onMounted(() => {
  store.fetchPackages()
})

function openCreateSlideOver() {
  createSlideOverRef.value?.open()
}

function openEditSlideOver(pkg: Package) {
  editSlideOverRef.value?.open(pkg)
}

function handleDelete(id: string) {
  if (confirm('Are you sure you want to delete this package?')) {
    store.deletePackage(id)
  }
}
</script>
