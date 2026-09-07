<template>
  <div class="h-full flex flex-col space-y-4 select-none">
    <!-- Top Header & View Selector -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
      <!-- Title & Main Controls -->
      <div class="flex items-center space-x-3">
        <button
          @click="store.jumpToToday()"
          class="px-3 py-1.5 text-xs font-semibold rounded-xl border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 bg-purple-50/50 dark:bg-purple-950/40 hover:bg-purple-100 transition-colors shadow-xs cursor-pointer"
        >
          {{ $t('common.today') }}
        </button>
        <div class="flex items-center space-x-1 border border-gray-200 dark:border-gray-700 rounded-xl p-0.5">
          <button
            @click="store.previousPeriod()"
            class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
            title="Previous"
          >
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <button
            @click="store.nextPeriod()"
            class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
            title="Next"
          >
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
          {{ formattedPeriodTitle }}
        </h2>
      </div>

      <!-- Live Summary Pills -->
      <div class="hidden lg:flex items-center space-x-2 text-xs font-medium">
        <span class="px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
          {{ $t('lessons.scheduled') }}: {{ store.summary.scheduled }}
        </span>
        <span v-if="store.summary.in_progress > 0" class="px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 animate-pulse">
          ⚡ {{ $t('lessons.live') }}: {{ store.summary.in_progress }}
        </span>
        <span class="px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
          {{ $t('lessons.completed') }}: {{ store.summary.completed }}
        </span>
        <span v-if="store.summary.conflicts > 0" class="px-2.5 py-1 rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-bold">
          🔴 {{ $t('lessons.conflicts') }}: {{ store.summary.conflicts }}
        </span>
      </div>

      <!-- View Mode Tabs (Week > Day > Month > Resource) -->
      <div class="flex items-center bg-gray-100 dark:bg-gray-900 p-1 rounded-xl border border-gray-200 dark:border-gray-700">
        <button
          v-for="mode in viewModeOptions"
          :key="mode.id"
          @click="switchViewMode(mode.id)"
          :class="[
            store.viewMode === mode.id
              ? 'bg-white dark:bg-gray-800 text-purple-600 dark:text-purple-400 shadow-sm font-bold'
              : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 font-medium',
            'px-3 py-1.5 text-xs rounded-lg transition-all capitalize cursor-pointer'
          ]"
        >
          {{ mode.label }}
        </button>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-wrap items-center gap-3 bg-white/70 dark:bg-gray-800/70 backdrop-blur-md p-3 rounded-2xl border border-gray-100 dark:border-gray-700 text-xs">
      <div class="flex items-center space-x-1.5 text-gray-500 font-semibold uppercase tracking-wider text-[10px]">
        <FunnelIcon class="w-3.5 h-3.5 text-purple-500" />
        <span>{{ $t('common.filter') }}:</span>
      </div>

      <!-- Teacher Filter -->
      <select
        v-model="store.filterTeacherId"
        @change="store.fetchCalendar()"
        class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-2.5 py-1.5 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-purple-500"
      >
        <option value="">{{ $t('lessons.filterTeacher') }}</option>
        <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>

      <!-- Room Filter -->
      <select
        v-model="store.filterRoomId"
        @change="store.fetchCalendar()"
        class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-2.5 py-1.5 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-purple-500"
      >
        <option value="">{{ $t('lessons.filterRoom') }}</option>
        <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>

      <!-- Subject Filter -->
      <select
        v-model="store.filterSubjectId"
        @change="store.fetchCalendar()"
        class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-2.5 py-1.5 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-purple-500"
      >
        <option value="">{{ $t('lessons.filterSubject') }}</option>
        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>

      <!-- Status Filter -->
      <select
        v-model="store.filterStatus"
        @change="store.fetchCalendar()"
        class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-2.5 py-1.5 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-purple-500"
      >
        <option value="">{{ $t('common.status') }}</option>
        <option value="scheduled">{{ $t('lessons.scheduled') }}</option>
        <option value="completed">{{ $t('lessons.completed') }}</option>
        <option value="cancelled">{{ $t('common.cancel') }}</option>
      </select>

      <button
        v-if="store.filterTeacherId || store.filterRoomId || store.filterSubjectId || store.filterStatus"
        @click="clearFilters()"
        class="text-purple-600 hover:text-purple-700 dark:text-purple-400 font-semibold underline underline-offset-2 ml-auto cursor-pointer"
      >
        {{ $t('common.cancel') }}
      </button>
    </div>

    <!-- Conflict Warning Banner (If 422 Rollback happens) -->
    <div v-if="toastMessage" class="bg-rose-500 text-white px-4 py-2.5 rounded-xl shadow-md flex items-center justify-between text-xs animate-bounce">
      <div class="flex items-center space-x-2 font-medium">
        <ExclamationTriangleIcon class="w-5 h-5 shrink-0" />
        <span>{{ toastMessage }}</span>
      </div>
      <button @click="toastMessage = null" class="font-bold underline text-white/80 hover:text-white">Dismiss</button>
    </div>

    <!-- Main Calendar Area -->
    <div class="flex-1 min-h-0 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col relative">
      <!-- Loading Overlay -->
      <div v-if="store.isLoading" class="absolute inset-0 z-20 bg-white/50 dark:bg-gray-900/50 backdrop-blur-xs flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>

      <!-- API Error Banner with Retry -->
      <div v-if="store.error" class="p-3 bg-rose-50 dark:bg-rose-950/40 border-b border-rose-200 dark:border-rose-800 flex items-center justify-between text-xs text-rose-700 dark:text-rose-300 z-10">
        <div class="flex items-center space-x-2">
          <ExclamationTriangleIcon class="w-4 h-4 text-rose-500 shrink-0" />
          <span>Could not load calendar data: {{ store.error }}</span>
        </div>
        <button @click="store.fetchCalendar()" class="px-2.5 py-1 bg-rose-600 text-white rounded-lg font-bold hover:bg-rose-700 transition-colors">
          Retry
        </button>
      </div>

      <!-- ================= 1. WEEK VIEW (PRIMARY OPERATIONAL DEFAULT) ================= -->
      <div v-if="store.viewMode === 'week'" class="flex-1 flex flex-col overflow-x-auto min-w-[800px]">
        <!-- Days Header (Mon -> Sun) -->
        <div class="grid grid-cols-8 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 shrink-0">
          <div class="py-2.5 text-center text-[11px] font-bold uppercase text-gray-400 border-r border-gray-200 dark:border-gray-700">
            {{ $t('common.time') }}
          </div>
          <div
            v-for="day in weekDays"
            :key="day.dateStr"
            :class="[
              day.isWeekend ? 'bg-gray-100/60 dark:bg-gray-900/60 text-gray-500' : 'text-gray-700 dark:text-gray-200',
              day.isToday ? 'bg-purple-50/50 dark:bg-purple-950/30' : '',
              'py-2 px-1 text-center border-r border-gray-200 dark:border-gray-700 relative'
            ]"
          >
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ day.dayName }}</div>
            <div :class="[day.isToday ? 'w-6 h-6 rounded-full bg-purple-600 text-white font-bold inline-flex items-center justify-center' : 'font-semibold text-sm']">
              {{ day.dayNumber }}
            </div>

            <!-- German Holiday Banner Badge -->
            <div v-if="day.holiday" class="mt-1">
              <span
                :class="[
                  day.holiday.type === 'public' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200 border-rose-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200 border-blue-300',
                  'inline-block max-w-full truncate px-1.5 py-0.5 rounded text-[9px] font-bold border'
                ]"
                :title="day.holiday.name"
              >
                {{ day.holiday.type === 'public' ? '🚩' : '🏖️' }} {{ day.holiday.name }}
              </span>
            </div>
          </div>
        </div>

        <!-- Hourly Time Grid -->
        <div class="flex-1 overflow-y-auto custom-scrollbar">
          <div v-for="hour in timeSlots" :key="hour" class="grid grid-cols-8 border-b border-gray-100 dark:border-gray-700/60 min-h-[64px]">
            <!-- Time Column -->
            <div class="p-2 text-right text-xs font-mono text-gray-400 border-r border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 select-none">
              {{ hour }}:00
            </div>

            <!-- Days Slots (Mon-Sun) -->
            <div
              v-for="day in weekDays"
              :key="day.dateStr + '-' + hour"
              @click="handleSlotClick(day.dateStr, `${hour.toString().padStart(2, '0')}:00`)"
              @dragover.prevent
              @drop="handleDrop($event, day.dateStr, `${hour.toString().padStart(2, '0')}:00`)"
              :class="[
                day.isWeekend ? 'bg-gray-50/50 dark:bg-gray-900/40' : 'bg-white dark:bg-gray-800',
                'border-r border-gray-100 dark:border-gray-700/50 p-1 relative hover:bg-purple-50/30 dark:hover:bg-purple-900/20 cursor-pointer transition-colors'
              ]"
            >
              <!-- Lessons in this slot -->
              <div
                v-for="lesson in getLessonsForSlot(day.dateStr, hour)"
                :key="lesson.id"
                draggable="true"
                @dragstart="handleDragStart(lesson)"
                @click.stop="emit('lesson-click', lesson)"
                :class="[
                  getLessonStatusClasses(lesson),
                  'p-1.5 rounded-xl border text-xs shadow-xs mb-1 cursor-grab active:cursor-grabbing transition-all hover:scale-[1.02]'
                ]"
              >
                <!-- Card Header -->
                <div class="flex items-center justify-between font-bold text-[11px] leading-tight">
                  <span class="truncate">{{ lesson.start_time.substring(0,5) }} - {{ lesson.end_time.substring(0,5) }}</span>
                  <span v-if="lesson.has_conflict" class="text-rose-600 font-extrabold text-[10px] animate-bounce" title="Schedule Conflict Detected!">
                    🔴 Conflict
                  </span>
                </div>

                <!-- Subject & Teacher -->
                <div class="font-semibold truncate text-[11px] mt-0.5">
                  {{ lesson.subject?.name || 'Subject' }}
                </div>
                <div class="text-[10px] opacity-80 truncate">
                  👨‍🏫 {{ lesson.teacher?.name || 'Teacher' }}
                </div>
                <div class="flex items-center justify-between text-[9.5px] opacity-75 mt-0.5">
                  <span class="truncate">📍 {{ lesson.room?.name || 'Room' }}</span>
                  <span class="font-bold">🎓 {{ lesson.students?.length || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= 2. DAY VIEW ================= -->
      <div v-else-if="store.viewMode === 'day'" class="flex-1 flex flex-col overflow-y-auto p-4 space-y-3 custom-scrollbar">
        <div class="text-sm font-bold text-gray-500 dark:text-gray-400">
          Schedule for {{ format(store.selectedDate, 'EEEE, MMMM d, yyyy') }}
        </div>
        <div v-if="getLessonsForDay(format(store.selectedDate, 'yyyy-MM-dd')).length === 0" class="text-center py-12 text-gray-400">
          No lessons scheduled for this day. Click anywhere in Week view to add one!
        </div>
        <div
          v-for="lesson in getLessonsForDay(format(store.selectedDate, 'yyyy-MM-dd'))"
          :key="lesson.id"
          @click="emit('lesson-click', lesson)"
          :class="[getLessonStatusClasses(lesson), 'p-4 rounded-2xl border shadow-sm flex items-center justify-between cursor-pointer']"
        >
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <span class="font-bold text-base">{{ lesson.start_time.substring(0,5) }} - {{ lesson.end_time.substring(0,5) }}</span>
              <span v-if="lesson.is_in_progress" class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500 text-white font-bold animate-pulse">LIVE</span>
              <span v-if="lesson.has_conflict" class="px-2 py-0.5 rounded-full text-[10px] bg-rose-600 text-white font-bold">CONFLICT</span>
            </div>
            <div class="font-semibold text-sm">{{ lesson.subject?.name }}</div>
            <div class="text-xs opacity-80">Teacher: {{ lesson.teacher?.name }} | Room: {{ lesson.room?.name }}</div>
          </div>
          <div class="text-right font-bold text-xs">
            🎓 {{ lesson.students?.length || 0 }} Students
          </div>
        </div>
      </div>

      <!-- ================= 3. MONTH VIEW ================= -->
      <div v-else-if="store.viewMode === 'month'" class="flex-1 flex flex-col min-h-0 overflow-y-auto custom-scrollbar">
        <!-- Month Weekday Header -->
        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 bg-gray-50/90 dark:bg-gray-800/90 sticky top-0 z-10 font-bold text-xs text-gray-600 dark:text-gray-300">
          <div
            v-for="(header, i) in monthWeekdayHeaders"
            :key="i"
            class="py-2.5 text-center border-r border-gray-200/60 dark:border-gray-700/60 last:border-r-0"
          >
            {{ header }}
          </div>
        </div>

        <!-- Month Days Grid -->
        <div class="flex-1 grid grid-cols-7 auto-rows-fr gap-px bg-gray-200 dark:bg-gray-700 min-h-[550px]">
          <div
            v-for="day in monthDays"
            :key="day.dateStr"
            @click="handleSlotClick(day.dateStr, '09:00')"
            :class="[
              day.isSameMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-900/60 text-gray-400 dark:text-gray-500',
              day.isToday ? 'ring-2 ring-inset ring-purple-500 bg-purple-50/20 dark:bg-purple-950/20' : '',
              'p-2 flex flex-col cursor-pointer hover:bg-purple-50/40 dark:hover:bg-purple-900/20 min-h-[110px] transition-colors relative group'
            ]"
          >
            <!-- Date & Holiday Header -->
            <div class="flex items-center justify-between text-xs font-semibold mb-1">
              <span
                :class="[
                  day.isToday ? 'w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-xs' : 'font-bold text-gray-800 dark:text-gray-200'
                ]"
              >
                {{ day.dayNumber }}
              </span>
              <span v-if="day.holiday" class="text-[9px] text-rose-600 dark:text-rose-400 font-bold truncate max-w-[100px]" :title="day.holiday.name">
                🚩 {{ day.holiday.name }}
              </span>
              <span v-else-if="getLessonsForDay(day.dateStr).length > 0" class="text-[10px] font-bold text-purple-600 dark:text-purple-400">
                {{ getLessonsForDay(day.dateStr).length }}
              </span>
            </div>

            <!-- Lessons List in Month Cell -->
            <div class="flex-1 overflow-y-auto space-y-1 custom-scrollbar max-h-[85px]">
              <div
                v-for="lesson in getLessonsForDay(day.dateStr)"
                :key="lesson.id"
                @click.stop="emit('lesson-click', lesson)"
                :class="[
                  getLessonStatusClasses(lesson),
                  'px-1.5 py-0.5 rounded-lg text-[10px] font-semibold truncate border shadow-2xs hover:scale-[1.02] transition-transform cursor-pointer'
                ]"
                :title="`${lesson.start_time.substring(0,5)} - ${lesson.end_time.substring(0,5)}: ${lesson.subject?.name || ''} (${lesson.teacher?.name || ''})`"
              >
                <span class="font-bold">{{ lesson.start_time.substring(0,5) }}</span> {{ lesson.subject?.name }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= 4. RESOURCE / ROOM MATRIX VIEW ================= -->
      <div v-else-if="store.viewMode === 'resource'" class="flex-1 flex flex-col overflow-x-auto min-w-[800px] p-4">
        <div class="text-sm font-bold text-gray-500 mb-2">{{ $t('settings.rooms') }}</div>
        <div class="grid grid-cols-6 border-b font-bold text-xs py-2 bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700">
          <div>{{ $t('settings.rooms') }}</div>
          <div v-for="day in weekDays.slice(0, 5)" :key="day.dateStr">{{ day.dayName }} {{ day.dayNumber }}</div>
        </div>
        <div v-for="room in rooms" :key="room.id" class="grid grid-cols-6 border-b py-3 text-xs items-center border-gray-100 dark:border-gray-700/60">
          <div class="font-bold text-purple-700 dark:text-purple-400">🏫 {{ room.name }}</div>
          <div v-for="day in weekDays.slice(0, 5)" :key="day.dateStr" class="px-1">
            <div
              v-for="lesson in getLessonsForRoomDay(room.id, day.dateStr)"
              :key="lesson.id"
              @click="emit('lesson-click', lesson)"
              :class="[getLessonStatusClasses(lesson), 'p-1 rounded text-[10px] mb-1 cursor-pointer truncate']"
            >
              {{ lesson.start_time.substring(0,5) }} {{ lesson.subject?.name }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  format,
  startOfWeek,
  endOfWeek,
  startOfMonth,
  endOfMonth,
  eachDayOfInterval,
  isSameMonth,
  isToday,
  isWeekend
} from 'date-fns'
import { de as dateFnsDe, enUS as dateFnsEn } from 'date-fns/locale'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  FunnelIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/20/solid'
import { useCalendarStore, type CalendarLesson } from '@/stores/calendarStore'
import api from '@/api'

const { t, locale } = useI18n()

const emit = defineEmits<{
  (e: 'lesson-click', lesson: CalendarLesson): void
  (e: 'slot-click', payload: { date: string; startTime: string }): void
}>()

const store = useCalendarStore()
const toastMessage = ref<string | null>(null)

// Option Lists for Filter Dropdowns
const teachers = ref<Array<{ id: string; name: string }>>([])
const rooms = ref<Array<{ id: string; name: string }>>([])
const subjects = ref<Array<{ id: string; name: string }>>([])

const timeSlots = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]

const currentLocaleObj = computed(() => locale.value === 'de' ? dateFnsDe : dateFnsEn)

const viewModeOptions = computed(() => [
  { id: 'week', label: t('lessons.viewWeek') },
  { id: 'day', label: t('lessons.viewDay') },
  { id: 'month', label: t('lessons.viewMonth') },
  { id: 'resource', label: t('lessons.viewResource') }
])

onMounted(async () => {
  store.fetchCalendar()
  loadDropdownData()
})

async function loadDropdownData() {
  try {
    const [tRes, rRes, sRes] = await Promise.all([
      api.get('/nachhilfe/teachers?per_page=100'),
      api.get('/nachhilfe/rooms'),
      api.get('/nachhilfe/subjects')
    ])
    const tData = tRes.data?.data || tRes.data || []
    const rData = rRes.data?.data || rRes.data || []
    const sData = sRes.data?.data || sRes.data || []

    teachers.value = Array.isArray(tData) ? tData : []
    rooms.value = Array.isArray(rData) ? rData : []
    subjects.value = Array.isArray(sData) ? sData : []
  } catch (e) {
    console.error('Error loading calendar filter dropdown data:', e)
  }
}

const formattedPeriodTitle = computed(() => {
  const opt = { locale: currentLocaleObj.value }
  if (store.viewMode === 'week' || store.viewMode === 'resource') {
    const formatted = format(store.selectedDate, 'd. MMM yyyy', opt)
    return locale.value === 'de' ? `Woche vom ${formatted}` : `Week of ${formatted}`
  } else if (store.viewMode === 'day') {
    return format(store.selectedDate, 'PPP', opt)
  }
  return format(store.selectedDate, 'MMMM yyyy', opt)
})

const weekDays = computed(() => {
  const start = startOfWeek(store.selectedDate, { weekStartsOn: 1 })
  const end = endOfWeek(store.selectedDate, { weekStartsOn: 1 })
  const interval = eachDayOfInterval({ start, end })

  return interval.map(date => {
    const dateStr = format(date, 'yyyy-MM-dd')
    const holiday = store.holidays.find(h => h.start_date <= dateStr && h.end_date >= dateStr)
    return {
      date,
      dateStr,
      dayName: format(date, 'EEE', { locale: currentLocaleObj.value }),
      dayNumber: format(date, 'd', { locale: currentLocaleObj.value }),
      isToday: isToday(date),
      isWeekend: isWeekend(date),
      holiday
    }
  })
})

const monthWeekdayHeaders = computed(() => {
  return weekDays.value.map(d => d.dayName)
})

const monthDays = computed(() => {
  const monthStart = startOfMonth(store.selectedDate)
  const monthEnd = endOfMonth(store.selectedDate)
  const start = startOfWeek(monthStart, { weekStartsOn: 1 })
  const end = endOfWeek(monthEnd, { weekStartsOn: 1 })
  const interval = eachDayOfInterval({ start, end })

  return interval.map(date => {
    const dateStr = format(date, 'yyyy-MM-dd')
    const holiday = store.holidays.find(h => h.start_date <= dateStr && h.end_date >= dateStr)
    return {
      date,
      dateStr,
      dayNumber: format(date, 'd', { locale: currentLocaleObj.value }),
      isSameMonth: isSameMonth(date, store.selectedDate),
      isToday: isToday(date),
      isWeekend: isWeekend(date),
      holiday
    }
  })
})

function switchViewMode(mode: string) {
  store.viewMode = mode as any
  store.fetchCalendar()
}

function clearFilters() {
  store.filterTeacherId = ''
  store.filterRoomId = ''
  store.filterSubjectId = ''
  store.filterStatus = ''
  store.fetchCalendar()
}

function handleSlotClick(dateStr: string, startTime: string) {
  emit('slot-click', { date: dateStr, startTime })
}

function getLessonsForDay(dateStr: string) {
  return store.lessons.filter(l => l.date === dateStr)
}

function getLessonsForSlot(dateStr: string, hour: number) {
  return store.lessons.filter(l => {
    if (l.date !== dateStr) return false
    const h = parseInt(l.start_time.substring(0, 2), 10)
    return h === hour
  })
}

function getLessonsForRoomDay(roomId: string, dateStr: string) {
  return store.lessons.filter(l => l.room_id === roomId && l.date === dateStr)
}

function getLessonStatusClasses(lesson: CalendarLesson): string {
  if (lesson.has_conflict) {
    return 'bg-rose-50 dark:bg-rose-950/60 text-rose-900 dark:text-rose-100 border-rose-400 dark:border-rose-700'
  }
  if (lesson.is_in_progress) {
    return 'bg-amber-50 dark:bg-amber-950/60 text-amber-900 dark:text-amber-100 border-amber-400 dark:border-amber-700'
  }
  if (lesson.status === 'completed') {
    return 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-100 border-emerald-300 dark:border-emerald-800'
  }
  if (lesson.status === 'cancelled') {
    return 'bg-gray-100 dark:bg-gray-900 text-gray-500 border-gray-300 dark:border-gray-700 line-through opacity-70'
  }
  // Default Scheduled
  return 'bg-blue-50 dark:bg-blue-950/60 text-blue-900 dark:text-blue-100 border-blue-300 dark:border-blue-800'
}

// Drag and Drop Rescheduling
let draggedLesson: CalendarLesson | null = null

function handleDragStart(lesson: CalendarLesson) {
  draggedLesson = lesson
}

async function handleDrop(_event: DragEvent, newDateStr: string, newStartTimeStr: string) {
  if (!draggedLesson) return

  const oldStartHour = parseInt(draggedLesson.start_time.substring(0, 2), 10)
  const oldEndHour = parseInt(draggedLesson.end_time.substring(0, 2), 10)
  const durationHours = Math.max(1, oldEndHour - oldStartHour)

  const newStartHour = parseInt(newStartTimeStr.substring(0, 2), 10)
  const newEndHour = newStartHour + durationHours
  const newEndTimeStr = `${newEndHour.toString().padStart(2, '0')}:00`

  const result = await store.rescheduleLessonOptimistic(
    draggedLesson.id,
    newDateStr,
    newStartTimeStr,
    newEndTimeStr
  )

  if (!result.success && result.message) {
    toastMessage.value = result.message
  }

  draggedLesson = null
}
</script>

<style scoped>
@reference "tailwindcss";

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600;
  border-radius: 4px;
}
</style>
