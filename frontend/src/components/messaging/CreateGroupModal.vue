<template>
  <!-- Backdrop -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')" />

    <div class="relative z-10 w-full max-w-lg max-h-[85vh] flex flex-col bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-white/10 overflow-hidden">

      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between flex-shrink-0 bg-gray-50 dark:bg-gray-900/80">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-600/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Create New Group</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Select members and enter a group name to start</p>
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

        <!-- Group Name Input -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Group Name *</label>
            <span v-if="groupNameError" class="text-xs text-red-500 font-medium">Required field</span>
          </div>
          <input
            ref="groupNameInput"
            v-model="groupName"
            @input="clearGroupNameError"
            type="text"
            placeholder="e.g., Mathematics Group / Teachers Inquiry"
            :class="[
              'w-full rounded-xl bg-gray-100 dark:bg-gray-800/80 border px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all outline-none',
              groupNameError
                ? 'border-red-500 focus:border-red-500 ring-2 ring-red-500/20'
                : 'border-gray-300 dark:border-white/10 focus:border-purple-500'
            ]"
          />
          <p v-if="groupNameError" class="text-xs text-red-500 mt-1.5 flex items-center gap-1 font-medium">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            {{ groupNameError }}
          </p>
        </div>

        <!-- Selected Members Bar (WhatsApp Style Chips) -->
        <div v-if="selectedUsers.length > 0" class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wider">
              Selected Members ({{ selectedUsers.length }})
            </label>
            <button
              type="button"
              @click="selectedParticipantIds = []"
              class="text-[11px] text-red-500 hover:text-red-700 dark:hover:text-red-400 font-medium cursor-pointer"
            >
              Clear All
            </button>
          </div>
          <div class="flex flex-wrap gap-2 max-h-24 overflow-y-auto p-2 bg-purple-50/50 dark:bg-gray-800/40 rounded-xl border border-purple-100 dark:border-white/5">
            <div
              v-for="user in selectedUsers"
              :key="user.id"
              class="flex items-center gap-1.5 px-3 py-1 bg-purple-100 dark:bg-purple-900/50 border border-purple-300 dark:border-purple-500/40 text-purple-800 dark:text-purple-200 rounded-full text-xs animate-fadeIn"
            >
              <span class="font-medium truncate max-w-[120px]">{{ user.name }}</span>
              <button @click.stop="removeParticipant(user.id)" class="hover:text-red-500 transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Search Bar -->
        <div>
          <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">Search Members</label>
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
              class="w-full rounded-xl bg-gray-100 dark:bg-gray-800/80 border border-gray-300 dark:border-white/10 focus:border-purple-500 focus:outline-none py-2.5 pr-4 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-colors"
              style="padding-left: 2.75rem !important;"
            />
          </div>
        </div>

        <!-- Users List -->
        <div>
          <div v-if="isLoadingUsers" class="text-center py-8">
            <div class="w-7 h-7 border-2 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
            <p class="text-xs text-gray-500 dark:text-gray-400">Loading members list...</p>
          </div>

          <div v-else-if="!hasResults" class="text-center py-8 text-gray-500 text-sm border border-dashed border-gray-300 dark:border-white/10 rounded-xl">
            No members found matching search
          </div>

          <div v-else class="space-y-4">

            <!-- Staff Section -->
            <div v-if="filteredStaff.length > 0">
              <div class="flex items-center justify-between mb-2">
                <div 
                  @click="isStaffExpanded = !isStaffExpanded"
                  class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1.5 cursor-pointer select-none py-1 hover:text-amber-500 transition-colors"
                >
                  <svg :class="['w-3.5 h-3.5 transition-transform duration-200', isStaffExpanded ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Management & Staff ({{ filteredStaff.length }})</span>
                </div>
                <button
                  type="button"
                  @click.stop="toggleSelectSection(filteredStaff)"
                  class="text-[11px] font-semibold px-2 py-0.5 rounded-lg transition-colors cursor-pointer"
                  :class="isSectionAllSelected(filteredStaff)
                    ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 hover:bg-amber-200'
                    : 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20'"
                >
                  {{ isSectionAllSelected(filteredStaff) ? 'Deselect All' : `Select All (${filteredStaff.length})` }}
                </button>
              </div>
              <div v-show="isStaffExpanded" class="space-y-1.5">
                <div
                  v-for="user in filteredStaff"
                  :key="user.id"
                  @click="toggleParticipant(user.id)"
                  :class="[
                    'flex items-center justify-between px-4 py-2.5 rounded-xl border cursor-pointer transition-all',
                    selectedParticipantIds.includes(user.id)
                      ? 'bg-purple-100 dark:bg-purple-900/40 border-purple-400 dark:border-purple-500/60 text-purple-950 dark:text-white shadow-sm'
                      : 'bg-gray-50 dark:bg-gray-800/40 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800/80'
                  ]"
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
                  <div :class="[
                    'w-5 h-5 rounded-md border flex items-center justify-center transition-colors',
                    selectedParticipantIds.includes(user.id) ? 'bg-purple-600 border-purple-500 text-white' : 'border-gray-400 dark:border-gray-600'
                  ]">
                    <svg v-if="selectedParticipantIds.includes(user.id)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Teachers Section -->
            <div v-if="filteredTeachers.length > 0">
              <div class="flex items-center justify-between mb-2">
                <div 
                  @click="isTeachersExpanded = !isTeachersExpanded"
                  class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider flex items-center gap-1.5 cursor-pointer select-none py-1 hover:text-purple-500 transition-colors"
                >
                  <svg :class="['w-3.5 h-3.5 transition-transform duration-200', isTeachersExpanded ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Teachers ({{ filteredTeachers.length }})</span>
                </div>
                <button
                  type="button"
                  @click.stop="toggleSelectSection(filteredTeachers)"
                  class="text-[11px] font-semibold px-2 py-0.5 rounded-lg transition-colors cursor-pointer"
                  :class="isSectionAllSelected(filteredTeachers)
                    ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 hover:bg-purple-200'
                    : 'text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20'"
                >
                  {{ isSectionAllSelected(filteredTeachers) ? 'Deselect All' : `Select All (${filteredTeachers.length})` }}
                </button>
              </div>
              <div v-show="isTeachersExpanded" class="space-y-1.5">
                <div
                  v-for="user in filteredTeachers"
                  :key="user.id"
                  @click="toggleParticipant(user.id)"
                  :class="[
                    'flex items-center justify-between px-4 py-2.5 rounded-xl border cursor-pointer transition-all',
                    selectedParticipantIds.includes(user.id)
                      ? 'bg-purple-100 dark:bg-purple-900/40 border-purple-400 dark:border-purple-500/60 text-purple-950 dark:text-white shadow-sm'
                      : 'bg-gray-50 dark:bg-gray-800/40 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800/80'
                  ]"
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
                  <div :class="[
                    'w-5 h-5 rounded-md border flex items-center justify-center transition-colors',
                    selectedParticipantIds.includes(user.id) ? 'bg-purple-600 border-purple-500 text-white' : 'border-gray-400 dark:border-gray-600'
                  ]">
                    <svg v-if="selectedParticipantIds.includes(user.id)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <!-- Students Section -->
            <div v-if="filteredStudents.length > 0">
              <div class="flex items-center justify-between mb-2">
                <div 
                  @click="isStudentsExpanded = !isStudentsExpanded"
                  class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1.5 cursor-pointer select-none py-1 hover:text-blue-500 transition-colors"
                >
                  <svg :class="['w-3.5 h-3.5 transition-transform duration-200', isStudentsExpanded ? 'rotate-90' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                  </svg>
                  <span>Students ({{ filteredStudents.length }})</span>
                </div>
                <button
                  type="button"
                  @click.stop="toggleSelectSection(filteredStudents)"
                  class="text-[11px] font-semibold px-2 py-0.5 rounded-lg transition-colors cursor-pointer"
                  :class="isSectionAllSelected(filteredStudents)
                    ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 hover:bg-blue-200'
                    : 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20'"
                >
                  {{ isSectionAllSelected(filteredStudents) ? 'Deselect All' : `Select All (${filteredStudents.length})` }}
                </button>
              </div>
              <div v-show="isStudentsExpanded" class="space-y-1.5">
                <div
                  v-for="user in filteredStudents"
                  :key="user.id"
                  @click="toggleParticipant(user.id)"
                  :class="[
                    'flex items-center justify-between px-4 py-2.5 rounded-xl border cursor-pointer transition-all',
                    selectedParticipantIds.includes(user.id)
                      ? 'bg-purple-100 dark:bg-purple-900/40 border-purple-400 dark:border-purple-500/60 text-purple-950 dark:text-white shadow-sm'
                      : 'bg-gray-50 dark:bg-gray-800/40 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800/80'
                  ]"
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
                  <div :class="[
                    'w-5 h-5 rounded-md border flex items-center justify-center transition-colors',
                    selectedParticipantIds.includes(user.id) ? 'bg-purple-600 border-purple-500 text-white' : 'border-gray-400 dark:border-gray-600'
                  ]">
                    <svg v-if="selectedParticipantIds.includes(user.id)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- General Error Alert Banner -->
        <div v-if="error" class="flex items-center gap-2 text-red-700 dark:text-red-300 text-xs bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-500/50 p-3 rounded-xl shadow-sm animate-fadeIn">
          <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="font-medium">{{ error }}</span>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-white/10 flex items-center justify-between flex-shrink-0 bg-gray-50 dark:bg-gray-900/80">
        <span class="text-xs text-gray-500 dark:text-gray-400">
          <strong class="text-gray-900 dark:text-white">{{ selectedParticipantIds.length }}</strong> member(s) selected
        </span>
        <div class="flex gap-2">
          <button
            @click="$emit('close')"
            class="px-4 py-2 rounded-xl text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/5 transition-colors cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="handleCreate"
            :disabled="isCreating || selectedParticipantIds.length === 0"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold transition-all shadow-lg hover:shadow-purple-500/25 flex items-center gap-2 cursor-pointer"
          >
            <svg v-if="isCreating" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span v-if="isCreating">Creating…</span>
            <span v-else>Create Group</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue'
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

const groupName = ref('')
const groupNameError = ref('')
const groupNameInput = ref<HTMLInputElement | null>(null)

const staff = ref<UserItem[]>([])
const students = ref<UserItem[]>([])
const teachers = ref<UserItem[]>([])
const searchQuery = ref('')
const selectedParticipantIds = ref<string[]>([])
const isLoadingUsers = ref(false)
const isCreating = ref(false)
const error = ref('')

const isStaffExpanded = ref(true)
const isTeachersExpanded = ref(true)
const isStudentsExpanded = ref(true)

function clearGroupNameError() {
  if (groupName.value.trim()) {
    groupNameError.value = ''
    if (error.value.includes('group name')) {
      error.value = ''
    }
  }
}

const allUsersMap = computed(() => {
  const map = new Map<string, UserItem>()
  for (const u of [...staff.value, ...teachers.value, ...students.value]) {
    map.set(u.id, u)
  }
  return map
})

const selectedUsers = computed(() => {
  return selectedParticipantIds.value
    .map(id => allUsersMap.value.get(id))
    .filter((u): u is UserItem => u !== undefined)
})

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

function isSectionAllSelected(list: UserItem[]): boolean {
  if (list.length === 0) return false
  return list.every(u => selectedParticipantIds.value.includes(u.id))
}

function toggleSelectSection(list: UserItem[]) {
  if (list.length === 0) return
  if (isSectionAllSelected(list)) {
    const idsToRemove = new Set(list.map(u => u.id))
    selectedParticipantIds.value = selectedParticipantIds.value.filter(id => !idsToRemove.has(id))
  } else {
    const currentSet = new Set(selectedParticipantIds.value)
    for (const u of list) {
      currentSet.add(u.id)
    }
    selectedParticipantIds.value = Array.from(currentSet)
  }
}

function toggleParticipant(id: string) {
  const idx = selectedParticipantIds.value.indexOf(id)
  if (idx === -1) {
    selectedParticipantIds.value.push(id)
  } else {
    selectedParticipantIds.value.splice(idx, 1)
  }
}

function removeParticipant(id: string) {
  const idx = selectedParticipantIds.value.indexOf(id)
  if (idx !== -1) {
    selectedParticipantIds.value.splice(idx, 1)
  }
}

async function handleCreate() {
  // Validate empty group name
  if (!groupName.value.trim()) {
    groupNameError.value = 'Group name cannot be empty. Please enter a group name.'
    error.value = 'Please specify a name for your new group.'
    await nextTick()
    groupNameInput.value?.focus()
    return
  }

  // Validate empty participants selection
  if (selectedParticipantIds.value.length === 0) {
    error.value = 'Please select at least one member to join the group.'
    return
  }

  isCreating.value = true
  error.value = ''
  groupNameError.value = ''
  try {
    const channel = await store.createChannel({
      type: 'group',
      name: groupName.value.trim(),
      participant_ids: selectedParticipantIds.value,
    })
    emit('created', channel)
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Failed to create group.'
  } finally {
    isCreating.value = false
  }
}
</script>
