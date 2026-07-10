<template>
  <div class="space-y-6 h-full flex flex-col">
    <div class="flex items-center justify-between shrink-0">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Settings</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 flex-1 overflow-hidden">
      
      <!-- Subjects Management -->
      <div class="glass-panel rounded-2xl flex flex-col overflow-hidden relative">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Subjects</h2>
          <button @click="openCreateSubject" class="bg-purple-600 hover:bg-purple-700 text-white p-1.5 rounded-lg shadow-sm transition-colors">
            <PlusIcon class="w-5 h-5" />
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
          <div v-if="subjectsStore.isLoading" class="flex justify-center p-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-purple-600"></div>
          </div>
          <ul v-else class="space-y-3">
            <li v-for="subject in subjectsStore.subjects" :key="subject.id" class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900 dark:text-white">{{ subject.name }}</div>
                <div class="text-xs text-gray-500" v-if="subject.description">{{ subject.description }}</div>
              </div>
              <div class="flex gap-2">
                <button @click="deleteSubject(subject)" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Rooms Management -->
      <div class="glass-panel rounded-2xl flex flex-col overflow-hidden relative">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Rooms</h2>
          <button @click="openCreateRoom" class="bg-blue-600 hover:bg-blue-700 text-white p-1.5 rounded-lg shadow-sm transition-colors">
            <PlusIcon class="w-5 h-5" />
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
          <div v-if="roomsStore.isLoading" class="flex justify-center p-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
          </div>
          <ul v-else class="space-y-3">
            <li v-for="room in roomsStore.rooms" :key="room.id" class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900 dark:text-white">{{ room.name }}</div>
                <div class="text-xs text-gray-500">Capacity: {{ room.capacity }}</div>
              </div>
              <div class="flex gap-2">
                <button @click="deleteRoom(room)" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
    
    <ConfirmModal ref="confirmModal" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/20/solid'
import { useSubjectsStore, type Subject } from '@/stores/subjectsStore'
import { useRoomsStore, type Room } from '@/stores/roomsStore'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'

const subjectsStore = useSubjectsStore()
const roomsStore = useRoomsStore()
const confirmModal = ref<InstanceType<typeof ConfirmModal> | null>(null)

onMounted(() => {
  subjectsStore.fetchSubjects()
  roomsStore.fetchRooms()
})

function openCreateSubject() {
  const name = prompt('Enter subject name:')
  if (name) {
    subjectsStore.createSubject({ name, is_active: true })
  }
}

function deleteSubject(subject: Subject) {
  confirmModal.value?.open(
    'Delete Subject',
    `Are you sure you want to delete ${subject.name}?`,
    'Delete',
    'Cancel',
    () => subjectsStore.deleteSubject(subject.id)
  )
}

function openCreateRoom() {
  const name = prompt('Enter room name:')
  if (name) {
    const capacityStr = prompt('Enter capacity (e.g. 10):')
    const parsedCapacity = parseInt(capacityStr || '10')
    const capacity = isNaN(parsedCapacity) || parsedCapacity < 1 ? 10 : parsedCapacity
    roomsStore.createRoom({ name, capacity })
  }
}

function deleteRoom(room: Room) {
  confirmModal.value?.open(
    'Delete Room',
    `Are you sure you want to delete ${room.name}?`,
    'Delete',
    'Cancel',
    () => roomsStore.deleteRoom(room.id)
  )
}
</script>
