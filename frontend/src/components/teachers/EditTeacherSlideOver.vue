<template>
  <SlideOver v-model="isOpen" :title="$t('teachers.editTeacher')" :description="$t('teachers.subtitle')">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <!-- Personal Info -->
      <div class="grid grid-cols-1 gap-4">
        <div>
          <label for="edit-teacher-name" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('teachers.name') }}</label>
          <input id="edit-teacher-name" name="name" type="text" v-model="form.name" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <!-- Contact Info -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="edit-teacher-email" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('teachers.email') }}</label>
          <input id="edit-teacher-email" name="email" type="email" v-model="form.email" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
        <div>
          <label for="edit-teacher-phone" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('teachers.phone') }}</label>
          <input id="edit-teacher-phone" name="phone" type="text" v-model="form.phone" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4">
        <div>
          <label for="edit-teacher-subjects" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('teachers.subjects') }}</label>
          <select id="edit-teacher-subjects" name="subjects" multiple v-model="form.subject_ids" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" size="4">
            <option v-for="subject in subjectsStore.subjects" :key="subject.id" :value="subject.id">
              {{ subject.name }}
            </option>
          </select>
        </div>
      </div>

      <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
        <button type="submit" :disabled="isSubmitting" class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 sm:col-start-2 disabled:opacity-50 transition-colors cursor-pointer">
          {{ isSubmitting ? $t('common.save') + '...' : $t('common.save') }}
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
import { useTeachersStore, type Teacher } from '@/stores/teachersStore'
import { useSubjectsStore } from '@/stores/subjectsStore'

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
    subject_ids: teacher.subjects ? teacher.subjects.map(s => s.id) : []
  }
  isOpen.value = true
}

async function submitForm() {
  isSubmitting.value = true
  try {
    await store.updateTeacher(teacherId.value, {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      subject_ids: form.value.subject_ids
    })
    isOpen.value = false
  } catch (error) {
    console.error('Failed to update teacher', error)
  } finally {
    isSubmitting.value = false
  }
}

defineExpose({
  open
})
</script>
