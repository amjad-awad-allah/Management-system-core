<template>
  <SlideOver v-model="isOpen" title="Schedule Lesson" description="Create a new individual or group lesson. Conflicts are checked automatically.">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <!-- Lesson Type -->
      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Type</label>
        <select v-model="form.type" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
          <option value="individual">Individual</option>
          <option value="group">Group</option>
        </select>
      </div>

      <!-- Date & Time -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Date</label>
          <input type="date" v-model="form.date" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Start Time</label>
          <input type="time" v-model="form.start_time" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">End Time</label>
          <input type="time" v-model="form.end_time" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <!-- IDs (Placeholders for real dropdowns) -->
      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Teacher ID</label>
        <input type="text" v-model="form.teacher_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Room ID</label>
        <input type="text" v-model="form.room_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Subject ID</label>
        <input type="text" v-model="form.subject_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <!-- Student(s) -->
      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Student ID</label>
        <input type="text" v-model="form.students[0].student_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
        <button type="submit" :disabled="isSubmitting" class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 sm:col-start-2 disabled:opacity-50">
          {{ isSubmitting ? 'Saving...' : 'Save Lesson' }}
        </button>
        <button type="button" @click="isOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:col-start-1 sm:mt-0">
          Cancel
        </button>
      </div>
    </form>
  </SlideOver>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import { useLessonsStore } from '@/stores/lessonsStore'

const isOpen = ref(false)
const store = useLessonsStore()
const isSubmitting = ref(false)

const form = ref({
  type: 'individual',
  teacher_id: '',
  room_id: '',
  subject_id: '',
  date: '',
  start_time: '',
  end_time: '',
  notes: '',
  students: [{ student_id: '', package_id: null }]
})

function open() {
  isOpen.value = true
}

defineExpose({ open })

async function submitForm() {
  isSubmitting.value = true
  const success = await store.bookLesson(form.value)
  isSubmitting.value = false
  if (success) {
    isOpen.value = false
    // reset form omitted for brevity
  }
}
</script>
