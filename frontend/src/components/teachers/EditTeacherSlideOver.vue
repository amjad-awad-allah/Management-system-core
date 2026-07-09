<template>
  <SlideOver v-model="isOpen" title="Edit Teacher" description="Update teacher information in the system.">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <!-- Personal Info -->
      <div class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Full Name</label>
          <input type="text" v-model="form.name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <!-- Contact Info -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Email</label>
          <input type="email" v-model="form.email" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Phone</label>
          <input type="text" v-model="form.phone" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Subjects</label>
          <select multiple v-model="form.subject_ids" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" size="4">
            <option v-for="subject in subjectsStore.subjects" :key="subject.id" :value="subject.id">
              {{ subject.name }}
            </option>
          </select>
          <p class="mt-1 text-xs text-gray-500">Hold Ctrl (Windows) or Cmd (Mac) to select multiple subjects.</p>
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
import { useTeachersStore, type Teacher } from '@/stores/teachersStore'
import { useSubjectsStore } from '@/stores/subjectsStore'
import { onMounted } from 'vue'

const isOpen = ref(false)
const store = useTeachersStore()
const subjectsStore = useSubjectsStore()
const isSubmitting = ref(false)

onMounted(() => {
  if (subjectsStore.subjects.length === 0) {
    subjectsStore.fetchSubjects()
  }
})
const teacherId = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  subject_ids: [] as string[]
})

function open(teacher: Teacher) {
  teacherId.value = teacher.id
  form.value = {
    name: teacher.name,
    email: teacher.email || '',
    phone: teacher.phone || '',
    subject_ids: teacher.subjects?.map(s => s.id) || []
  }
  isOpen.value = true
}

async function submitForm() {
  isSubmitting.value = true
  try {
    const success = await store.updateTeacher(teacherId.value, {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      qualification: 'Teacher',
      hourly_rate: 20,
      subject_ids: form.value.subject_ids
    })
    if (success) {
      isOpen.value = false
    }
  } finally {
    isSubmitting.value = false
  }
}

defineExpose({ open })
</script>
