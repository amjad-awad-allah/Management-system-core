<template>
  <SlideOver v-model="isOpen" title="Edit Package" description="Update the billing package details.">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Package Name</label>
        <input type="text" v-model="form.name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Description</label>
        <textarea v-model="form.description" rows="3" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Total Hours</label>
          <input type="number" min="1" v-model="form.hours" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Price</label>
          <input type="number" min="0" step="0.01" v-model="form.price" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
        <button type="submit" :disabled="isSubmitting" class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 sm:col-start-2 disabled:opacity-50 transition-colors">
          {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
        </button>
        <button type="button" @click="isOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:col-start-1 sm:mt-0 transition-colors">
          Cancel
        </button>
      </div>
    </form>
  </SlideOver>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import { usePackagesStore } from '@/stores/packagesStore'

const isOpen = ref(false)
const store = usePackagesStore()
const isSubmitting = ref(false)

const form = ref<any>({
  name: '',
  description: '',
  hours: 10,
  price: 0,
  is_active: true
})
const packageId = ref<string>('')

function open(pkg: any) {
  packageId.value = pkg.id
  form.value = {
    name: pkg.name,
    description: pkg.description || '',
    hours: pkg.hours,
    price: pkg.price,
    is_active: pkg.is_active
  }
  isOpen.value = true
}

defineExpose({ open })

async function submitForm() {
  isSubmitting.value = true
  const success = await store.updatePackage(packageId.value, form.value)
  isSubmitting.value = false
  if (success) {
    isOpen.value = false
  }
}
</script>
