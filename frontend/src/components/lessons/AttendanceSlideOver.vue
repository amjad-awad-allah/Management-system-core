<template>
  <SlideOver v-model="isOpen" :title="$t('lessons.attendance')" :description="$t('lessons.subtitle')">
    <form @submit.prevent="submitForm" class="space-y-6">
      
      <div>
        <label for="attendance-status" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('common.status') }}</label>
        <select id="attendance-status" name="status" v-model="form.status" required class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
          <option value="present">{{ $t('lessons.attendanceStatus.present') }}</option>
          <option value="absent_excused">{{ $t('lessons.attendanceStatus.absent_excused') }}</option>
          <option value="absent_unexcused">{{ $t('lessons.attendanceStatus.absent_unexcused') }}</option>
          <option value="late">{{ $t('lessons.attendanceStatus.late') }}</option>
        </select>
      </div>

      <div>
        <label for="attendance-note" class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">{{ $t('settings.entity') }}</label>
        <textarea id="attendance-note" name="note" v-model="form.note" rows="3" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6"></textarea>
      </div>

      <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
        <button type="submit" :disabled="isSubmitting" class="inline-flex w-full justify-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 sm:col-start-2 disabled:opacity-50 transition-colors cursor-pointer">
          {{ isSubmitting ? $t('common.save') + '...' : $t('lessons.attendance') }}
        </button>
        <button type="button" @click="isOpen = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:col-start-1 sm:mt-0 transition-colors cursor-pointer">
          {{ $t('common.cancel') }}
        </button>
      </div>
    </form>
  </SlideOver>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import { useLessonsStore } from '@/stores/lessonsStore'

const isOpen = ref(false)
const store = useLessonsStore()
const isSubmitting = ref(false)
const currentLessonStudentId = ref('')

const form = ref({
  status: 'present',
  note: ''
})

function open(lessonStudentId: string) {
  currentLessonStudentId.value = lessonStudentId
  form.value.status = 'present'
  form.value.note = ''
  isOpen.value = true
}

defineExpose({ open })

async function submitForm() {
  if (!currentLessonStudentId.value) return

  isSubmitting.value = true
  const success = await store.markAttendance(currentLessonStudentId.value, form.value)
  isSubmitting.value = false
  if (success) {
    isOpen.value = false
  }
}
</script>
