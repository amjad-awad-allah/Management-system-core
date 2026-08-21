<template>
  <SlideOver v-model="isOpen" :title="$t('students.editStudent')" :description="$t('students.subtitle')">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <!-- Personal Info -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('students.firstName') }}</label>
          <input type="text" v-model="form.first_name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('students.lastName') }}</label>
          <input type="text" v-model="form.last_name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('students.dob') }}</label>
        <input type="date" v-model="form.birth_date" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Schule</label>
          <input type="text" v-model="form.school" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Klassenstufe</label>
          <input type="number" min="1" max="13" v-model="form.grade" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('teachers.subjects') }}</label>
          <select multiple v-model="form.subject_ids" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" size="4">
            <option v-for="subject in subjectsStore.subjects" :key="subject.id" :value="subject.id">
              {{ subject.name }}
            </option>
          </select>
        </div>
      </div>

      <div class="border-t border-gray-200 dark:border-gray-800 pt-6 mt-6">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">{{ $t('students.parent') }}</h4>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('students.parentName') }}</label>
            <input type="text" v-model="form.parent_name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('students.parentPhone') }}</label>
              <input type="text" v-model="form.parent_phone_1" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
            </div>
            <div>
              <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('students.email') }}</label>
              <input type="email" v-model="form.parent_email" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
            </div>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 dark:border-gray-800 pt-6 mt-6">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">{{ $t('students.billingType') }}</h4>
        
        <div>
          <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('students.billingType') }}</label>
          <select v-model="form.billing_type" required class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
            <option value="bu_t">{{ $t('students.billingTypeOptions.bu_t') }}</option>
            <option value="self_payer">{{ $t('students.billingTypeOptions.self_payer') }}</option>
          </select>
        </div>
      </div>

      <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
        <button type="submit" :disabled="isSubmitting" class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 sm:col-start-2 disabled:opacity-50 transition-colors cursor-pointer">
          {{ isSubmitting ? $t('common.save') + '...' : $t('students.editStudent') }}
        </button>
        <button type="button" @click="isOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:col-start-1 sm:mt-0 transition-colors cursor-pointer">
          {{ $t('common.cancel') }}
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

onMounted(() => {
  if (subjectsStore.subjects.length === 0) {
    subjectsStore.fetchSubjects()
  }
})

const studentId = ref('')

const form = ref({
  first_name: '',
  last_name: '',
  birth_date: '',
  school: '',
  grade: 1,
  parent_name: '',
  parent_email: '',
  parent_phone_1: '',
  billing_type: 'bu_t' as 'bu_t' | 'self_payer',
  subject_ids: [] as string[]
})

function open(student: Student) {
  studentId.value = student.id
  form.value = {
    first_name: student.first_name,
    last_name: student.last_name,
    birth_date: student.date_of_birth || '',
    school: student.school || '',
    grade: student.grade || 1,
    parent_name: student.parent_name || '',
    parent_email: student.parent_email || '',
    parent_phone_1: student.parent_phone_1 || '',
    billing_type: (student.billing_type as 'bu_t' | 'self_payer') || 'bu_t',
    subject_ids: student.subjects ? student.subjects.map(s => s.id) : []
  }
  isOpen.value = true
}

async function submitForm() {
  isSubmitting.value = true
  try {
    await store.updateStudent(studentId.value, {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      date_of_birth: form.value.birth_date,
      school: form.value.school,
      grade: form.value.grade,
      parent_name: form.value.parent_name,
      parent_email: form.value.parent_email,
      parent_phone_1: form.value.parent_phone_1,
      billing_type: form.value.billing_type,
      subject_ids: form.value.subject_ids
    })
    isOpen.value = false
  } catch (error) {
    console.error('Failed to update student', error)
  } finally {
    isSubmitting.value = false
  }
}

defineExpose({
  open
})
</script>
