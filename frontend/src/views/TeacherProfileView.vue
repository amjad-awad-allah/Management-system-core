<template>
<div>
  <div v-if="isLoading" class="flex items-center justify-center h-full">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
  </div>
  
  <div v-else-if="teacher" class="space-y-6 h-full flex flex-col overflow-y-auto pb-8 custom-scrollbar">
    
    <!-- Header -->
    <div class="glass-panel p-6 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
      <div class="flex items-center gap-4">
        <div class="h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold text-2xl shadow-sm">
          {{ teacher.name[0] }}
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ teacher.name }}</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ teacher.qualification }} • €{{ teacher.hourly_rate }}/hr
          </p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span :class="[
          teacher.status === 'active' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/10',
          'inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium ring-1 ring-inset'
        ]">
          {{ teacher.status === 'active' ? $t('common.active') : $t('common.inactive') }}
        </span>
        <button @click="openEditSlideOver" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors cursor-pointer">
          {{ $t('students.editProfile') }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 min-h-0">
      
      <!-- Left Column: Details & Subjects -->
      <div class="space-y-6 lg:col-span-1 overflow-y-auto custom-scrollbar pr-1">
        
        <!-- Contact Info -->
        <div class="glass-panel rounded-2xl p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('teachers.contact') }}</h2>
          <div class="space-y-4">
            <div>
              <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ $t('teachers.email') }}</p>
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ teacher.email || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ $t('teachers.phone') }}</p>
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ teacher.phone || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <!-- Subjects -->
        <div class="glass-panel rounded-2xl p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('teachers.subjects') }}</h2>
          </div>
          
          <div v-if="!teacher.subjects || teacher.subjects.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
            {{ $t('students.noSubjects') }}
          </div>
          
          <div v-else class="flex flex-wrap gap-2">
            <span v-for="subject in teacher.subjects" :key="subject.id" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
              {{ subject.name }}
            </span>
          </div>
        </div>

        <!-- App Access (Mobile Access Code) -->
        <div class="glass-panel rounded-2xl p-6 space-y-4 print:p-0 print:border-0 print:shadow-none">
          <Teleport to="body">
            <div class="print:block hidden print-card-container">
              <div class="border-4 border-purple-600 rounded-[2.5rem] p-10 w-[440px] mx-auto text-center bg-white text-gray-900 shadow-2xl border-double flex flex-col items-center justify-between min-h-[500px] pb-12 my-8">
                <!-- Header with premium line decoration -->
                <div class="w-full">
                  <div class="text-[11px] uppercase tracking-[0.25em] text-purple-600 font-extrabold mb-1">Access Pass</div>
                  <h2 class="text-2xl font-black tracking-tight text-gray-900">{{ teacher.name }}</h2>
                  <div class="text-xs text-gray-500 font-medium mt-1">Teacher Profile</div>
                </div>

                <div class="w-full my-6 flex flex-col items-center">
                  <!-- Decorative dashed divider -->
                  <div class="w-full border-t border-dashed border-gray-300 my-4"></div>
                  
                  <!-- QR Code with white padding border -->
                  <div class="p-4 bg-white border border-gray-200 rounded-3xl shadow-md my-2">
                    <img 
                      v-if="loginCode" 
                      :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=nachhilfe://login?code=${loginCode.code}`"
                      alt="QR Code Login Link"
                      class="w-36 h-36"
                    />
                  </div>

                  <div class="w-full border-t border-dashed border-gray-300 my-4"></div>
                </div>

                <!-- Access Code -->
                <div class="w-full">
                  <span class="text-[9px] uppercase tracking-[0.2em] text-gray-400 font-bold block mb-1">Access Code</span>
                  <div class="text-2xl font-black tracking-[0.15em] bg-purple-50 text-purple-700 py-3 rounded-2xl font-mono border border-purple-100 shadow-inner">
                    {{ loginCode?.code }}
                  </div>
                  <p class="text-[10px] text-gray-400 mt-4 leading-relaxed max-w-[280px] mx-auto">
                    Scan QR code using the Nachhilfe App camera, or enter the code manually to access your profile.
                  </p>
                </div>
              </div>
            </div>
          </Teleport>

          <div class="print:hidden">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">App Access</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Scan the QR code or enter the code to log in on mobile.</p>
            
            <div v-if="isMobileAccessLoading" class="flex justify-center py-4">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div>
            </div>
            <div v-else class="flex flex-col items-center">
              <div class="bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800 mb-4">
                <img 
                  v-if="loginCode" 
                  :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=nachhilfe://login?code=${loginCode.code}`"
                  alt="QR Code Login Link"
                  class="w-36 h-36"
                />
              </div>

              <div class="text-center mb-4">
                <span class="text-[10px] text-gray-500 uppercase tracking-widest block font-medium">Access Code</span>
                <span class="text-xl font-bold tracking-widest text-purple-600 dark:text-purple-400 font-mono">
                  {{ loginCode?.code }}
                </span>
              </div>

              <div class="flex gap-2 w-full">
                <button 
                  @click="printCard"
                  class="flex-1 rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer flex justify-center items-center gap-1"
                >
                  Print Card
                </button>
                <button 
                  @click="showRegenPasswordModal = true"
                  class="flex-1 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 text-xs font-semibold shadow-sm transition-colors cursor-pointer"
                >
                  Regenerate
                </button>
              </div>
            </div>

            <!-- Active Devices -->
            <div v-if="activeDevices.length > 0" class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-850">
              <span class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider block mb-2">Logged Devices</span>
              <ul class="space-y-2">
                <li v-for="device in activeDevices" :key="device.id" class="flex items-center justify-between text-xs py-1">
                  <span class="text-gray-700 dark:text-gray-300 font-medium truncate max-w-[120px]">{{ device.device_name }}</span>
                  <button @click="revokeDevice(device.id)" class="text-[10px] text-red-500 hover:text-red-600 font-semibold cursor-pointer">
                    Revoke
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>

      </div>

      <!-- Regenerate Password Confirmation Modal for Teacher -->
      <div v-if="showRegenPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 print:hidden">
        <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-gray-100 dark:border-gray-700 text-left">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Regenerate Login Code</h3>
            <p class="text-xs text-gray-500 mt-1">This will invalidate the current QR/short code and immediately log out all active mobile devices for security.</p>
          </div>
          <form @submit.prevent="handleRegenerateCode" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Confirm Administrator Password</label>
              <input 
                type="password" 
                required 
                v-model="adminPasswordForRegen" 
                placeholder="••••••••••••••"
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-950 dark:text-white focus:border-purple-500 focus:ring-purple-500"
              />
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <button 
                type="button" 
                @click="showRegenPasswordModal = false; adminPasswordForRegen = ''"
                class="rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                :disabled="isRegeneratingCode"
                class="rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-semibold shadow-sm transition-colors cursor-pointer flex items-center gap-1.5"
              >
                <svg v-if="isRegeneratingCode" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                Regenerate Code
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Right Column: Students -->
      <div class="lg:col-span-2 glass-panel rounded-2xl flex flex-col overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 px-6 py-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">My Students ({{ teacher.students?.length || 0 }})</h2>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
          <div v-if="!teacher.students || teacher.students.length === 0" class="flex flex-col items-center justify-center h-full text-center py-12 text-gray-500 dark:text-gray-400">
            <div class="bg-gray-100 dark:bg-gray-800 h-16 w-16 rounded-full flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <p>No students have taken lessons with this teacher yet.</p>
          </div>
          
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="student in teacher.students" :key="student.id" @click="$router.push(`/students/${student.id}`)" class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-purple-200 dark:hover:border-purple-800 bg-white dark:bg-gray-800 hover:shadow-md transition-all cursor-pointer group">
              <div class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-700 dark:text-purple-300 font-bold text-sm shrink-0">
                {{ student.first_name[0] }}{{ student.last_name[0] }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                  {{ student.first_name }} {{ student.last_name }}
                </p>
              </div>
              <div class="text-gray-400 group-hover:text-purple-500">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
      
    </div>

    <!-- Calendar View for Teacher's Lessons -->
    <div class="glass-panel p-6 rounded-2xl flex-1 min-h-[500px] flex flex-col relative">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Teacher Schedule</h2>
      </div>
      
      <div v-if="lessonsStore.isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center rounded-2xl">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <Calendar 
        :lessons="lessonsStore.lessons"
        @lesson-click="handleLessonClick"
      />
    </div>

  </div>
  

  <div v-else class="flex flex-col items-center justify-center h-full text-center py-12">
    <div class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-4 rounded-full mb-4">
      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    </div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Teacher not found</h3>
    <button @click="$router.push('/teachers')" class="text-purple-600 hover:text-purple-700 font-medium">
      &larr; Back to Teachers
    </button>
  </div>
  
  <EditTeacherSlideOver ref="editSlideOver" @teacher-updated="fetchTeacher" />
  <LessonDetailSlideOver ref="lessonDetailSlideOver" @edit-lesson="handleEditLesson" />
  <ScheduleLessonSlideOver ref="scheduleLessonSlideOver" />
</div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import EditTeacherSlideOver from '@/components/teachers/EditTeacherSlideOver.vue'
import LessonDetailSlideOver from '@/components/lessons/LessonDetailSlideOver.vue'
import ScheduleLessonSlideOver from '@/components/lessons/ScheduleLessonSlideOver.vue'
import Calendar from '@/components/calendar/Calendar.vue'
import { useLessonsStore, type Lesson } from '@/stores/lessonsStore'
import { useToastStore } from '@/stores/toastStore'
import api from '@/api'

const route = useRoute()
const isLoading = ref(true)
const teacher = ref<any>(null)
const editSlideOver = ref<InstanceType<typeof EditTeacherSlideOver> | null>(null)
const lessonDetailSlideOver = ref<InstanceType<typeof LessonDetailSlideOver> | null>(null)
const scheduleLessonSlideOver = ref<InstanceType<typeof ScheduleLessonSlideOver> | null>(null)
const lessonsStore = useLessonsStore()
const toastStore = useToastStore()

function openEditSlideOver() {
  if (teacher.value) {
    editSlideOver.value?.open(teacher.value)
  }
}

// Mobile QR Code Access Logic
const loginCode = ref<any>(null)
const activeDevices = ref<any[]>([])
const isMobileAccessLoading = ref(false)
const showRegenPasswordModal = ref(false)
const adminPasswordForRegen = ref('')
const isRegeneratingCode = ref(false)

async function fetchMobileAccess(teacherId: string) {
  isMobileAccessLoading.value = true
  try {
    const res = await api.get(`/nachhilfe/teachers/${teacherId}/login-code`)
    loginCode.value = res.data.login_code
    activeDevices.value = res.data.devices || []
  } catch (err) {
    console.error('Failed to load mobile access code', err)
  } finally {
    isMobileAccessLoading.value = false
  }
}

async function handleRegenerateCode() {
  const userId = loginCode.value?.user_id
  if (!userId) {
    toastStore.error('No user account linked to this teacher profile')
    return
  }
  if (!adminPasswordForRegen.value) return
  isRegeneratingCode.value = true
  try {
    const res = await api.post(`/users/${userId}/login-code/regenerate`, {
      admin_password: adminPasswordForRegen.value
    })
    loginCode.value = res.data.login_code
    activeDevices.value = []
    showRegenPasswordModal.value = false
    adminPasswordForRegen.value = ''
    toastStore.success('Code regenerated and all mobile sessions revoked successfully')
  } catch (err: any) {
    console.error('Failed to regenerate login code', err)
    toastStore.error(err.response?.data?.message || 'Failed to regenerate code')
  } finally {
    isRegeneratingCode.value = false
  }
}

async function revokeDevice(deviceId: string) {
  try {
    await api.delete(`/mobile/devices/${deviceId}`)
    activeDevices.value = activeDevices.value.filter(d => d.id !== deviceId)
    toastStore.success('Device revoked successfully')
  } catch (err) {
    console.error('Failed to revoke device', err)
    toastStore.error('Failed to revoke device')
  }
}

async function fetchTeacher() {
  isLoading.value = true
  try {
    const res = await api.get(`/nachhilfe/teachers/${route.params.id}`)
    teacher.value = res.data.data
    fetchMobileAccess(route.params.id as string)
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchTeacher()
  lessonsStore.fetchLessons({ teacher_id: route.params.id })
})

function printCard() {
  window.print()
}

function handleLessonClick(lesson: any) {
  lessonDetailSlideOver.value?.open(lesson)
}

function handleEditLesson(lesson: Lesson) {
  scheduleLessonSlideOver.value?.open(lesson)
}
</script>

<style>
@media print {
  /* Hide the main application view entirely to prevent margins and viewport scrolling bugs */
  #app {
    display: none !important;
  }
  
  /* Reset body and make it clear background and margins */
  body {
    background: white !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    height: auto !important;
  }

  /* Display card container centered on the print layout page */
  .print-card-container {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    width: 100% !important;
    min-height: 100vh !important;
    background: white !important;
    margin: 0 !important;
    padding: 20px !important;
    box-sizing: border-box !important;
  }
}
</style>
