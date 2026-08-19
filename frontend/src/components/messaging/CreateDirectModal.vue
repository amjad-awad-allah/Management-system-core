<template>
  <!-- Backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')" />

    <div class="relative z-10 w-full max-w-lg max-h-[85vh] flex flex-col bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-white/10 overflow-hidden">

      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between flex-shrink-0 bg-gray-50 dark:bg-gray-900/80">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-600/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">New Direct Message</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Select a person to start messaging directly</p>
          </div>
        </div>
        <button @click="$emit('close')" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/10 transition-colors cursor-pointer">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-4 overflow-y-auto flex-1 min-h-0">

        <!-- Search Bar -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name or email..."
            class="w-full rounded-xl bg-gray-100 dark:bg-gray-800/80 border border-gray-300 dark:border-white/10 focus:border-blue-500 focus:outline-none py-2.5 pr-4 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
            style="padding-left: 2.75rem !important;"
          />
        </div>

        <!-- Contact List -->
        <div>
          <div v-if="isLoadingUsers" class="text-center py-8">
            <div class="w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
            <p class="text-xs text-gray-500 dark:text-gray-400">Loading contacts list...</p>
          </div>

          <div v-else-if="!hasResults" class="text-center py-8 text-gray-500 text-sm border border-dashed border-gray-300 dark:border-white/10 rounded-xl">
            No contacts found matching search
          </div>

          <div v-else class="space-y-4">

            <!-- Staff Section -->
            <div v-if="filteredStaff.length > 0">
              <div 
                @click="isStaffExpanded = !isStaffExpanded"
                class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2 flex items-center justify-between cursor-pointer select-none py-1 hover:text-amber-500 transition-colors"
              >
                <div class="flex items-center gap-1.5">
                  <svg :class="['w-3.5 h-3.5 transition-transform duration-200', isStaffExpanded ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Management & Staff ({{ filteredStaff.length }})</span>
                </div>
              </div>
              <div v-show="isStaffExpanded" class="space-y-1.5">
                <div
                  v-for="user in filteredStaff"
                  :key="user.id"
                  @click="startDirectChat(user.id)"
                  class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-gray-800/40 hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:border-blue-300 dark:hover:border-blue-500/50 text-gray-800 dark:text-gray-300 hover:text-blue-950 dark:hover:text-white cursor-pointer transition-all group"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium truncate">{{ user.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email || 'Staff' }}</p>
                    </div>
                  </div>
                  <span class="text-xs font-medium text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">Start Chat &rarr;</span>
                </div>
              </div>
            </div>

            <!-- Teachers Section -->
            <div v-if="filteredTeachers.length > 0">
              <div 
                @click="isTeachersExpanded = !isTeachersExpanded"
                class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider mb-2 flex items-center justify-between cursor-pointer select-none py-1 hover:text-purple-500 transition-colors"
              >
                <div class="flex items-center gap-1.5">
                  <svg :class="['w-3.5 h-3.5 transition-transform duration-200', isTeachersExpanded ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Teachers ({{ filteredTeachers.length }})</span>
                </div>
              </div>
              <div v-show="isTeachersExpanded" class="space-y-1.5">
                <div
                  v-for="user in filteredTeachers"
                  :key="user.id"
                  @click="startDirectChat(user.id)"
                  class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-gray-800/40 hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:border-blue-300 dark:hover:border-blue-500/50 text-gray-800 dark:text-gray-300 hover:text-blue-950 dark:hover:text-white cursor-pointer transition-all group"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium truncate">{{ user.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email || 'Teacher' }}</p>
                    </div>
                  </div>
                  <span class="text-xs font-medium text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">Start Chat &rarr;</span>
                </div>
              </div>
            </div>

            <!-- Students Section -->
            <div v-if="filteredStudents.length > 0">
              <div 
                @click="isStudentsExpanded = !isStudentsExpanded"
                class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2 flex items-center justify-between cursor-pointer select-none py-1 hover:text-blue-500 transition-colors"
              >
                <div class="flex items-center gap-1.5">
                  <svg :class="['w-3.5 h-3.5 transition-transform duration-200', isStudentsExpanded ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Students ({{ filteredStudents.length }})</span>
                </div>
              </div>
              <div v-show="isStudentsExpanded" class="space-y-1.5">
                <div
                  v-for="user in filteredStudents"
                  :key="user.id"
                  @click="startDirectChat(user.id)"
                  class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-gray-800/40 hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:border-blue-300 dark:hover:border-blue-500/50 text-gray-800 dark:text-gray-300 hover:text-blue-950 dark:hover:text-white cursor-pointer transition-all group"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-teal-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium truncate">{{ user.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email || 'Student' }}</p>
                    </div>
                  </div>
                  <span class="text-xs font-medium text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">Start Chat &rarr;</span>
                </div>
              </div>
            </div>

          </div>
        </div>

        <p v-if="error" class="text-red-700 dark:text-red-400 text-xs bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-500/30 p-3 rounded-xl">{{ error }}</p>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api'
import { useMessagingStore } from '@/stores/messagingStore'

interface UserItem {
  id: string
  name: string
  email?: string
  role: string
}

const emit = defineEmits<{
  close: []
  created: [channel: any]
}>()

const store = useMessagingStore()

const staff = ref<UserItem[]>([])
const students = ref<UserItem[]>([])
const teachers = ref<UserItem[]>([])
const searchQuery = ref('')
const isLoadingUsers = ref(false)
const isCreating = ref(false)
const error = ref('')

const isStaffExpanded = ref(true)
const isTeachersExpanded = ref(true)
const isStudentsExpanded = ref(true)

const filteredStaff = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return staff.value
  return staff.value.filter(u =>
    u.name.toLowerCase().includes(query) ||
    (u.email && u.email.toLowerCase().includes(query))
  )
})

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
  return filteredStaff.value.length > 0 || filteredStudents.value.length > 0 || filteredTeachers.value.length > 0
})

onMounted(async () => {
  isLoadingUsers.value = true
  const userMap = new Map<string, UserItem>()

  // 1. Fetch system users (Admins / Staff)
  try {
    const usersRes = await api.get('/users')
    const userList = usersRes.data.data ?? usersRes.data
    if (Array.isArray(userList)) {
      for (const u of userList) {
        if (u.id) {
          const roleName = u.roles?.[0]?.name ?? 'Admin'
          userMap.set(u.id, {
            id: u.id,
            name: u.name || u.email || 'User',
            email: u.email,
            role: roleName,
          })
        }
      }
    }
  } catch (err) {
    console.error('Error fetching system users:', err)
  }

  // 2. Fetch students
  try {
    const studentsRes = await api.get('/nachhilfe/students', { params: { per_page: 100 } })
    const studentList = studentsRes.data.data ?? studentsRes.data
    if (Array.isArray(studentList)) {
      for (const s of studentList) {
        const uid = s.user_id || s.id
        if (uid) {
          const existing = userMap.get(uid)
          const name = s.name ?? ((`${s.first_name || ''} ${s.last_name || ''}`.trim()) || existing?.name || 'Student')
          userMap.set(uid, {
            id: uid,
            name,
            email: s.email ?? existing?.email,
            role: 'Student',
          })
        }
      }
    }
  } catch (err) {
    console.error('Error fetching students:', err)
  }

  // 3. Fetch teachers
  try {
    const teachersRes = await api.get('/nachhilfe/teachers', { params: { per_page: 100 } })
    const teacherList = teachersRes.data.data ?? teachersRes.data
    if (Array.isArray(teacherList)) {
      for (const t of teacherList) {
        const uid = t.user_id || t.id
        if (uid) {
          const existing = userMap.get(uid)
          userMap.set(uid, {
            id: uid,
            name: t.name || existing?.name || 'Teacher',
            email: t.email ?? existing?.email,
            role: 'Teacher',
          })
        }
      }
    }
  } catch (err) {
    console.error('Error fetching teachers:', err)
  }

  const staffList: UserItem[] = []
  const teachersList: UserItem[] = []
  const studentsList: UserItem[] = []

  for (const item of userMap.values()) {
    if (item.role === 'Teacher') teachersList.push(item)
    else if (item.role === 'Student') studentsList.push(item)
    else staffList.push(item)
  }

  staff.value = staffList
  teachers.value = teachersList
  students.value = studentsList

  isLoadingUsers.value = false
})

async function startDirectChat(userId: string) {
  isCreating.value = true
  error.value = ''
  try {
    const channel = await store.createChannel({
      type: 'direct',
      participant_ids: [userId],
    })
    emit('created', channel)
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Failed to open conversation.'
  } finally {
    isCreating.value = false
  }
}
</script>
