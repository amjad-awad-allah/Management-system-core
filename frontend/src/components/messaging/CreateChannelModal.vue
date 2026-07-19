<template>
  <!-- Backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')" />

    <div class="relative z-10 w-full max-w-lg max-h-[85vh] flex flex-col bg-gray-900 rounded-2xl shadow-2xl border border-white/10 overflow-hidden">

      <!-- Header -->
      <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between flex-shrink-0">
        <h3 class="text-lg font-bold text-white">New Conversation</h3>
        <button @click="$emit('close')" class="text-gray-500 hover:text-white transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-5 overflow-y-auto flex-1 min-h-0">
        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-400 mb-2">Type</label>
          <div class="flex gap-2">
            <button
              v-for="t in types"
              :key="t.value"
              @click="form.type = t.value"
              :class="[
                'flex-1 py-2 px-3 rounded-xl text-sm font-medium transition-all border',
                form.type === t.value
                  ? 'bg-purple-600 border-purple-500 text-white'
                  : 'bg-gray-800/60 border-white/10 text-gray-400 hover:text-white'
              ]"
            >
              {{ t.label }}
            </button>
          </div>
        </div>

        <!-- Name (for group/survey) -->
        <div v-if="form.type !== 'direct'">
          <label class="block text-sm font-medium text-gray-400 mb-2">Name</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Conversation name"
            class="w-full rounded-xl bg-gray-800/60 border border-white/10 focus:border-purple-500 focus:outline-none px-4 py-3 text-sm text-white placeholder-gray-600 transition-colors"
          />
        </div>

        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-400 mb-2">Search Participants</label>
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by name or email..."
              class="w-full rounded-xl bg-gray-800/60 border border-white/10 focus:border-purple-500 focus:outline-none py-2.5 text-sm text-white placeholder-gray-500 transition-colors"
              style="padding-left: 3rem !important; padding-inline-start: 3rem !important; padding-right: 1rem !important;"
            />
            <div 
              class="absolute inset-y-0 flex items-center pointer-events-none"
              style="left: 0.85rem !important; inset-inline-start: 0.85rem !important;"
            >
              <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Participants List -->
        <div>
          <label class="block text-sm font-medium text-gray-400 mb-2">Participants</label>
          
          <div v-if="isLoadingUsers" class="text-center py-6">
            <div class="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
            <p class="text-xs text-gray-500">Loading participants...</p>
          </div>

          <div v-else-if="!hasResults" class="text-center py-6 text-gray-500 text-sm border border-dashed border-white/10 rounded-xl">
            No participants found
          </div>

          <div v-else class="space-y-4">
            <!-- Teachers Section -->
            <div v-if="filteredTeachers.length > 0">
              <h4 
                @click="toggleTeachers"
                class="text-xs font-semibold text-purple-400 uppercase tracking-wider mb-2 flex items-center justify-between cursor-pointer select-none hover:text-purple-300 transition-colors"
              >
                <div class="flex items-center gap-1.5">
                  <svg 
                    :class="['w-3.5 h-3.5 transition-transform duration-200', isTeachersExpanded ? 'rotate-90' : '']" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Teachers ({{ filteredTeachers.length }})</span>
                </div>
              </h4>
              <div v-show="isTeachersExpanded" class="space-y-2 mt-1">
                <div
                  v-for="user in filteredTeachers"
                  :key="user.id"
                  @click="toggleParticipant(user.id)"
                  :class="[
                    'flex items-center gap-3 px-4 py-3 rounded-xl border cursor-pointer transition-all',
                    selectedParticipants.includes(user.id)
                      ? 'bg-purple-900/30 border-purple-500/50 text-white'
                      : 'bg-gray-800/40 border-white/10 text-gray-400 hover:text-white hover:bg-gray-800/60'
                  ]"
                >
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ user.name }}</p>
                    <p class="text-xs opacity-50">{{ user.email ?? 'Teacher' }}</p>
                  </div>
                  <svg v-if="selectedParticipants.includes(user.id)" class="w-4 h-4 text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Students Section -->
            <div v-if="filteredStudents.length > 0">
              <h4 
                @click="toggleStudents"
                class="text-xs font-semibold text-blue-400 uppercase tracking-wider mb-2 flex items-center justify-between cursor-pointer select-none hover:text-blue-300 transition-colors"
              >
                <div class="flex items-center gap-1.5">
                  <svg 
                    :class="['w-3.5 h-3.5 transition-transform duration-200', isStudentsExpanded ? 'rotate-90' : '']" 
                    fill="none" 
                    viewBox="0 0 24 24" 
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Students ({{ filteredStudents.length }})</span>
                </div>
              </h4>
              <div v-show="isStudentsExpanded" class="space-y-2 mt-1">
                <div
                  v-for="user in filteredStudents"
                  :key="user.id"
                  @click="toggleParticipant(user.id)"
                  :class="[
                    'flex items-center gap-3 px-4 py-3 rounded-xl border cursor-pointer transition-all',
                    selectedParticipants.includes(user.id)
                      ? 'bg-purple-900/30 border-purple-500/50 text-white'
                      : 'bg-gray-800/40 border-white/10 text-gray-400 hover:text-white hover:bg-gray-800/60'
                  ]"
                >
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-teal-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ user.name }}</p>
                    <p class="text-xs opacity-50">{{ user.email ?? 'Student' }}</p>
                  </div>
                  <svg v-if="selectedParticipants.includes(user.id)" class="w-4 h-4 text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-white/10 flex justify-end gap-3 flex-shrink-0">
        <button
          @click="$emit('close')"
          class="px-4 py-2 rounded-xl text-sm text-gray-400 hover:text-white transition-colors"
        >
          Cancel
        </button>
        <button
          @click="handleCreate"
          :disabled="isCreating || selectedParticipants.length === 0"
          class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 text-white text-sm font-medium transition-all"
        >
          <span v-if="isCreating">Creating…</span>
          <span v-else>Create</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import api from '@/api'
import { useMessagingStore } from '@/stores/messagingStore'

const emit = defineEmits<{
  close: []
  created: [channel: any]
}>()

const store = useMessagingStore()

const types: { value: 'direct' | 'group' | 'survey'; label: string }[] = [
  { value: 'direct', label: 'Direct' },
  { value: 'group', label: 'Group' },
  { value: 'survey', label: 'Survey' },
]

const form = reactive({
  type: 'direct' as 'direct' | 'group' | 'survey',
  name: '',
})

const students = ref<any[]>([])
const teachers = ref<any[]>([])
const searchQuery = ref('')
const selectedParticipants = ref<string[]>([])
const isLoadingUsers = ref(false)
const isCreating = ref(false)
const error = ref('')

const isTeachersExpanded = ref(true)
const isStudentsExpanded = ref(true)

function toggleTeachers() {
  isTeachersExpanded.value = !isTeachersExpanded.value
}

function toggleStudents() {
  isStudentsExpanded.value = !isStudentsExpanded.value
}

const filteredStudents = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return students.value
  return students.value.filter(s => 
    s.name.toLowerCase().includes(query) || 
    (s.email && s.email.toLowerCase().includes(query))
  )
})

const filteredTeachers = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return teachers.value
  return teachers.value.filter(t => 
    t.name.toLowerCase().includes(query) || 
    (t.email && t.email.toLowerCase().includes(query))
  )
})

const hasResults = computed(() => {
  return filteredStudents.value.length > 0 || filteredTeachers.value.length > 0
})

onMounted(async () => {
  isLoadingUsers.value = true
  
  // Fetch students
  try {
    const studentsRes = await api.get('/nachhilfe/students', { params: { per_page: 100 } })
    students.value = (studentsRes.data.data ?? studentsRes.data)
      .filter((s: any) => s.user_id)
      .map((s: any) => ({
        id: s.user_id,
        name: s.name ?? `${s.first_name} ${s.last_name}`,
        email: s.email,
        role: 'Student',
      }))
  } catch (err) {
    console.error('Error fetching students:', err)
  }

  // Fetch teachers
  try {
    const teachersRes = await api.get('/nachhilfe/teachers', { params: { per_page: 100 } })
    teachers.value = (teachersRes.data.data ?? teachersRes.data)
      .filter((t: any) => t.user_id)
      .map((t: any) => ({
        id: t.user_id,
        name: t.name,
        email: t.email,
        role: 'Teacher',
      }))
  } catch (err) {
    console.error('Error fetching teachers:', err)
  }

  isLoadingUsers.value = false
})

function toggleParticipant(id: string) {
  const idx = selectedParticipants.value.indexOf(id)
  if (idx === -1) {
    if (form.type === 'direct' && selectedParticipants.value.length >= 1) {
      selectedParticipants.value = [id] // for direct, only 1
    } else {
      selectedParticipants.value.push(id)
    }
  } else {
    selectedParticipants.value.splice(idx, 1)
  }
}

async function handleCreate() {
  if (selectedParticipants.value.length === 0) {
    error.value = 'Please select at least one participant.'
    return
  }
  isCreating.value = true
  error.value = ''
  try {
    const channel = await store.createChannel({
      type: form.type,
      name: form.name || undefined,
      participant_ids: selectedParticipants.value,
    })
    emit('created', channel)
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Failed to create conversation.'
  } finally {
    isCreating.value = false
  }
}
</script>
