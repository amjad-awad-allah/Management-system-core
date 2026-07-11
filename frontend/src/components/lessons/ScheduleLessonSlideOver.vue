<template>
  <SlideOver v-model="isOpen" :title="isEditing ? 'Edit Lesson' : 'Schedule Lesson'" :description="isEditing ? 'Modify lesson details and students. Conflicts are checked automatically.' : 'Create a new individual or group lesson. Conflicts are checked automatically.'">
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

      <!-- Relationships -->
      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Teacher</label>
        <select v-model="form.teacher_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
          <option value="" disabled>Select a teacher</option>
          <option v-for="teacher in teachersStore.teachers" :key="teacher.id" :value="teacher.id">
            {{ teacher.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Room</label>
        <select v-model="form.room_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
          <option value="" disabled>Select a room</option>
          <option v-for="room in roomsStore.rooms" :key="room.id" :value="room.id">
            {{ room.name }} (Capacity: {{ room.capacity }})
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Subject</label>
        <select v-model="form.subject_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
          <option value="" disabled>Select a subject</option>
          <option v-for="subject in subjectsStore.subjects" :key="subject.id" :value="subject.id">
            {{ subject.name }}
          </option>
        </select>
      </div>

      <!-- Student(s) -->
      <div class="space-y-3">
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Students</label>
        
        <div v-for="(studentSlot, index) in form.students" :key="index" class="flex gap-2 items-center">
          <div class="flex-1">
            <select v-model="studentSlot.student_id" required class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
              <option value="" disabled>Select a student</option>
              <option v-for="student in studentsStore.students" :key="student.id" :value="student.id" :disabled="form.students.some((s, idx) => s.student_id === student.id && idx !== index)">
                {{ student.first_name }} {{ student.last_name }}
              </option>
            </select>
          </div>
          
          <button v-if="form.type === 'group' && form.students.length > 1" type="button" @click="removeStudentSlot(index)" class="text-red-600 hover:text-red-500 p-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
            <TrashIcon class="w-5 h-5" />
          </button>
        </div>

        <button v-if="form.type === 'group'" type="button" @click="addStudentSlot" class="mt-2 text-xs font-semibold text-purple-600 hover:text-purple-500 flex items-center gap-1">
          <PlusIcon class="w-4 h-4" /> Add another student
        </button>
      </div>

      <!-- Recurrence Settings (Only in Create Mode) -->
      <div v-if="!isEditing" class="bg-purple-50/50 dark:bg-purple-950/20 p-4 rounded-xl border border-purple-100 dark:border-purple-900/50 space-y-4">
        <div>
          <label class="block text-sm font-semibold text-purple-950 dark:text-purple-300">Repeat Pattern</label>
          <select v-model="form.recurrence_pattern" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-purple-300 dark:ring-purple-900 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6">
            <option value="none">Does not repeat</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>
        
        <div v-if="form.recurrence_pattern && form.recurrence_pattern !== 'none'">
          <label class="block text-sm font-semibold text-purple-950 dark:text-purple-300">Repeat Until</label>
          <input type="date" v-model="form.recurrence_end_date" :min="form.date" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-purple-300 dark:ring-purple-900 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6" />
        </div>
      </div>

      <!-- Notes -->
      <div>
        <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">Notes (Optional)</label>
        <textarea v-model="form.notes" rows="3" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-100 dark:bg-gray-800 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 sm:text-sm sm:leading-6"></textarea>
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
import { ref, onMounted, computed, watch } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/20/solid'
import { useLessonsStore } from '@/stores/lessonsStore'
import { useTeachersStore } from '@/stores/teachersStore'
import { useRoomsStore } from '@/stores/roomsStore'
import { useSubjectsStore } from '@/stores/subjectsStore'
import { useStudentsStore } from '@/stores/studentsStore'

const isOpen = ref(false)
const store = useLessonsStore()
const teachersStore = useTeachersStore()
const roomsStore = useRoomsStore()
const subjectsStore = useSubjectsStore()
const studentsStore = useStudentsStore()
const isSubmitting = ref(false)

const lessonId = ref<string | null>(null)
const isEditing = computed(() => !!lessonId.value)

onMounted(() => {
  teachersStore.fetchTeachers()
  roomsStore.fetchRooms()
  subjectsStore.fetchSubjects()
  studentsStore.fetchStudents()
})

const form = ref({
  type: 'individual',
  teacher_id: '',
  room_id: '',
  subject_id: '',
  date: '',
  start_time: '',
  end_time: '',
  notes: '',
  students: [{ student_id: '', package_id: null }],
  recurrence_pattern: 'none',
  recurrence_end_date: ''
})

watch(() => form.value.type, (newType) => {
  if (newType === 'individual' && form.value.students.length > 1) {
    form.value.students = form.value.students.slice(0, 1)
  }
})

function open(lessonOrDate?: any) {
  if (lessonOrDate && typeof lessonOrDate === 'object' && lessonOrDate.id) {
    // Edit mode
    lessonId.value = lessonOrDate.id
    form.value = {
      type: lessonOrDate.type,
      teacher_id: lessonOrDate.teacher_id,
      room_id: lessonOrDate.room_id,
      subject_id: lessonOrDate.subject_id,
      date: lessonOrDate.date,
      start_time: lessonOrDate.start_time.substring(0, 5),
      end_time: lessonOrDate.end_time.substring(0, 5),
      notes: lessonOrDate.notes || '',
      students: lessonOrDate.students.map((s: any) => ({
        student_id: s.id,
        package_id: s.pivot?.package_id || null
      })),
      recurrence_pattern: 'none',
      recurrence_end_date: ''
    }
  } else {
    // Create mode
    lessonId.value = null
    form.value = {
      type: 'individual',
      teacher_id: '',
      room_id: '',
      subject_id: '',
      date: typeof lessonOrDate === 'string' ? lessonOrDate : new Date().toISOString().split('T')[0],
      start_time: '',
      end_time: '',
      notes: '',
      students: [{ student_id: '', package_id: null }],
      recurrence_pattern: 'none',
      recurrence_end_date: ''
    }
  }
  isOpen.value = true
}

function addStudentSlot() {
  form.value.students.push({ student_id: '', package_id: null })
}

function removeStudentSlot(index: number) {
  form.value.students.splice(index, 1)
}

async function submitForm() {
  isSubmitting.value = true
  let success = false
  
  if (isEditing.value && lessonId.value) {
    success = await store.updateLesson(lessonId.value, form.value)
  } else {
    success = await store.bookLesson(form.value)
  }
  
  isSubmitting.value = false
  if (success) {
    isOpen.value = false
  }
}

defineExpose({ open })
</script>
