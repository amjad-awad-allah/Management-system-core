<template>
  <SlideOver v-model="isOpen" title="Add Package" description="Assign a billing package to this student.">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Select Package</label>
        <select v-model="form.package_id" @change="onPackageChange" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
          <option value="">Custom Package</option>
          <option v-for="pkg in pkgStore.packages" :key="pkg.id" :value="pkg.id">
            {{ pkg.name }} ({{ pkg.hours }}h / €{{ pkg.price }})
          </option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Total Hours</label>
          <input type="number" min="1" v-model="form.total_hours" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Funding Source</label>
          <select v-model="form.funding_source" required class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
            <option value="private">Private</option>
            <option value="jobcenter">Jobcenter (BuT)</option>
          </select>
        </div>
      </div>

      <div v-if="form.funding_source === 'jobcenter'">
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Voucher Reference Number</label>
        <input type="text" v-model="form.voucher_reference" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Expires At (Optional)</label>
        <input type="date" v-model="form.expires_at" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
        <button type="submit" :disabled="isSubmitting" class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 sm:col-start-2 disabled:opacity-50 transition-colors">
          {{ isSubmitting ? 'Saving...' : 'Assign Package' }}
        </button>
        <button type="button" @click="isOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:col-start-1 sm:mt-0 transition-colors">
          Cancel
        </button>
      </div>
    </form>
  </SlideOver>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import { usePackagesStore } from '@/stores/packagesStore'
import { useStudentsStore } from '@/stores/studentsStore'

const isOpen = ref(false)
const pkgStore = usePackagesStore()
const stdStore = useStudentsStore()
const isSubmitting = ref(false)

const form = ref({
  package_id: '',
  funding_source: 'private',
  voucher_reference: '',
  total_hours: 10,
  expires_at: ''
})

onMounted(() => {
  if (pkgStore.packages.length === 0) {
    pkgStore.fetchPackages()
  }
})

function onPackageChange() {
  const pkg = pkgStore.packages.find(p => p.id === form.value.package_id)
  if (pkg) {
    form.value.total_hours = pkg.hours
  }
}

function open() {
  form.value = {
    package_id: '',
    funding_source: stdStore.currentStudent?.billing_type || 'private',
    voucher_reference: '',
    total_hours: 10,
    expires_at: ''
  }
  isOpen.value = true
}

defineExpose({ open })

async function submitForm() {
  if (!stdStore.currentStudent) return
  isSubmitting.value = true
  const success = await pkgStore.assignPackageToStudent(stdStore.currentStudent.id, form.value)
  isSubmitting.value = false
  if (success) {
    // refresh student to get updated packages
    stdStore.fetchStudent(stdStore.currentStudent.id)
    isOpen.value = false
  }
}
</script>
