<template>
  <SlideOver v-model="isOpen" title="Edit Student" description="Update student information in the system.">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <!-- Personal Info -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">First Name</label>
          <input type="text" v-model="form.first_name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Last Name</label>
          <input type="text" v-model="form.last_name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Date of Birth</label>
        <input type="date" v-model="form.birth_date" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">School</label>
          <input type="text" v-model="form.school" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Grade</label>
          <input type="number" min="1" max="13" v-model="form.grade" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
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

      <div class="border-t border-gray-200 dark:border-gray-800 pt-6 mt-6">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Parent Information</h4>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Parent Name</label>
            <input type="text" v-model="form.parent_name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Primary Phone</label>
              <input type="text" v-model="form.parent_phone_1" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
            </div>
            <div>
              <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Secondary Phone</label>
              <input type="text" v-model="form.parent_phone_2" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Email</label>
            <input type="email" v-model="form.parent_email" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 dark:border-gray-800 pt-6 mt-6">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Billing Settings</h4>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Billing Type</label>
          <select v-model="form.billing_type" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
            <option value="private">Private</option>
            <option value="jobcenter">Jobcenter (BuT)</option>
          </select>
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
import { ref, onMounted } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import { useStudentsStore, type Student } from '@/stores/studentsStore'
import { useSubjectsStore } from '@/stores/subjectsStore'

const isOpen = ref(false)
const store = useStudentsStore()
const subjectsStore = useSubjectsStore()
const isSubmitting = ref(false)
const studentId = ref('')

onMounted(() => {
  if (subjectsStore.subjects.length === 0) {
    subjectsStore.fetchSubjects()
  }
})

const form = ref({
  first_name: '',
  last_name: '',
  birth_date: '',
  school: '',
  grade: 1 as number | string,
  parent_name: '',
  parent_phone_1: '',
  parent_phone_2: '',
  parent_email: '',
  billing_type: 'private',
  subject_ids: [] as string[]
})

function open(student: Student) {
  studentId.value = student.id
  form.value = {
    first_name: student.first_name,
    last_name: student.last_name,
    birth_date: student.birth_date || '',
    school: student.school || '',
    grade: student.grade || '',
    parent_name: student.parent_name || '',
    parent_phone_1: student.parent_phone_1 || '',
    parent_phone_2: student.parent_phone_2 || '',
    parent_email: student.parent_email || '',
    billing_type: student.billing_type || 'private',
    subject_ids: student.subjects?.map(s => s.id) || []
  }
  isOpen.value = true
}

defineExpose({ open })

async function submitForm() {
  isSubmitting.value = true
  const success = await store.updateStudent(studentId.value, form.value)
  isSubmitting.value = false
  if (success) {
    isOpen.value = false
  }
}
</script>
