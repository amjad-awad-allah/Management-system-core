<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header & Dynamic Greeting -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
          {{ greetingMessage }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Here is what's happening at the center today.
        </p>
      </div>
      
      <div class="flex flex-wrap items-center gap-3">
        <button @click="$router.push('/lessons')" class="inline-flex items-center rounded-lg bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:from-purple-500 hover:to-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-all transform hover:scale-105">
          <CalendarIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
          Schedule Lesson
        </button>
      </div>
    </div>

    <!-- Live Center Status (Premium Feature) -->
    <div class="bg-white/70 dark:bg-gray-900/70 backdrop-blur-lg border border-white/20 dark:border-gray-700/30 shadow-sm rounded-3xl p-6 relative overflow-hidden bg-gradient-to-br from-white/60 to-white/30 dark:from-gray-800/80 dark:to-gray-900/80 shadow-xl">
      <div class="absolute -top-24 -right-24 h-48 w-48 rounded-full bg-purple-500/20 blur-3xl"></div>
      <div class="absolute -bottom-24 -left-24 h-48 w-48 rounded-full bg-blue-500/20 blur-3xl"></div>
      
      <div class="relative z-10">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
            <span class="relative flex h-3 w-3 mr-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            Live Center Status
          </h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Live Rooms -->
          <div class="bg-white/40 dark:bg-gray-800/40 rounded-2xl p-4 border border-white/20 dark:border-gray-700/30">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
              <MapPinIcon class="h-4 w-4 mr-1 text-gray-500" />
              Rooms Status
            </h3>
            <div class="flex flex-wrap gap-2">
              <div v-for="room in store.liveStatus?.rooms || []" :key="room.id" 
                   :class="[
                     'px-3 py-1.5 rounded-lg text-xs font-medium border flex items-center gap-1.5 transition-all duration-300 hover:-translate-y-1 cursor-default',
                     room.is_occupied 
                      ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800' 
                      : 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800'
                   ]">
                <div :class="['h-1.5 w-1.5 rounded-full', room.is_occupied ? 'bg-red-500' : 'bg-green-500']"></div>
                {{ room.name }} ({{ room.capacity }})
              </div>
            </div>
          </div>
          
          <!-- Live Teachers -->
          <div class="bg-white/40 dark:bg-gray-800/40 rounded-2xl p-4 border border-white/20 dark:border-gray-700/30">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
              <UsersIcon class="h-4 w-4 mr-1 text-gray-500" />
              Teachers Status
            </h3>
            <div class="flex flex-wrap gap-2">
              <div v-for="teacher in store.liveStatus?.teachers || []" :key="teacher.id" 
                   :class="[
                     'px-3 py-1.5 rounded-lg text-xs font-medium border flex items-center gap-1.5 transition-all duration-300 hover:-translate-y-1 cursor-default',
                     teacher.is_teaching 
                      ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800' 
                      : 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800/50 dark:text-gray-300 dark:border-gray-700'
                   ]">
                <div :class="['h-1.5 w-1.5 rounded-full', teacher.is_teaching ? 'bg-blue-500' : 'bg-gray-400']"></div>
                {{ teacher.name }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Left Column -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Actionable Alerts (Premium Feature) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Depleted Packages Alert -->
          <div v-if="(store.depletedPackages || []).length > 0" class="group relative overflow-hidden rounded-2xl p-5 bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/10 border border-red-100 dark:border-red-900/30 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-red-500/10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center mb-3 relative z-10">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-500 mr-2" />
              <h3 class="text-base font-bold text-red-900 dark:text-red-200">Renewals Needed</h3>
            </div>
            <p class="text-xs text-red-600 dark:text-red-400 mb-3 relative z-10">{{ store.depletedPackages.length }} students have 0 remaining hours.</p>
            <div class="flex -space-x-2 overflow-hidden relative z-10">
              <div v-for="sp in store.depletedPackages.slice(0, 4)" :key="sp.id" class="inline-flex h-8 w-8 rounded-full ring-2 ring-white dark:ring-gray-900 bg-red-200 text-red-700 items-center justify-center text-xs font-bold" :title="sp.student?.first_name">
                {{ sp.student?.first_name?.[0] }}
              </div>
            </div>
            <button @click="$router.push('/students')" class="mt-4 text-xs font-semibold text-red-700 hover:text-red-800 dark:text-red-400 relative z-10">View All &rarr;</button>
          </div>
          
          <!-- Overdue Invoices Alert -->
          <div v-if="(store.recentInvoices || []).length > 0" class="group relative overflow-hidden rounded-2xl p-5 bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/10 border border-yellow-100 dark:border-yellow-900/30 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-yellow-500/10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center mb-3 relative z-10">
              <BanknotesIcon class="h-5 w-5 text-yellow-600 mr-2" />
              <h3 class="text-base font-bold text-yellow-900 dark:text-yellow-200">Unpaid Invoices</h3>
            </div>
            <p class="text-xs text-yellow-700 dark:text-yellow-500 mb-3 relative z-10">{{ store.recentInvoices.length }} recent invoices pending payment.</p>
            <p class="text-lg font-bold text-yellow-800 dark:text-yellow-300 relative z-10">€{{ store.stats.pending_balances?.toFixed(2) }}</p>
            <button @click="$router.push('/invoices')" class="mt-2 text-xs font-semibold text-yellow-700 hover:text-yellow-800 dark:text-yellow-400 relative z-10">Collect Now &rarr;</button>
          </div>
        </div>

        <!-- Revenue Chart -->
        <div class="bg-white/70 dark:bg-gray-900/70 backdrop-blur-lg border border-white/20 dark:border-gray-700/30 shadow-sm rounded-3xl p-6 hover:shadow-xl transition-shadow duration-300 relative overflow-hidden group">
          <div class="absolute inset-0 bg-gradient-to-r from-purple-500/5 to-blue-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 relative z-10">Revenue Overview (Last 6 Months)</h2>
          <div class="relative z-10">
            <RevenueChart />
          </div>
        </div>
      </div>

      <!-- Right Column: Vertical Timeline -->
      <div class="space-y-6">
        <div class="bg-white/70 dark:bg-gray-900/70 backdrop-blur-lg border border-white/20 dark:border-gray-700/30 shadow-sm rounded-3xl p-6 h-full min-h-[500px] border-t-4 border-t-purple-500 hover:shadow-xl transition-shadow duration-300 relative overflow-hidden">
          <div class="absolute right-0 top-0 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl"></div>
          
          <div class="flex items-center justify-between mb-8 relative z-10">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
              <ClockIcon class="h-5 w-5 mr-2 text-purple-500" />
              Today's Timeline
            </h2>
            <button @click="$router.push('/lessons')" class="text-xs font-medium bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-3 py-1.5 rounded-full hover:bg-purple-100 transition-colors">Full Schedule</button>
          </div>

          <div v-if="(store.upcomingLessons || []).length === 0" class="flex flex-col items-center justify-center py-16 text-center relative z-10">
            <div class="h-16 w-16 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mb-4">
              <CheckCircleIcon class="h-8 w-8 text-green-500" />
            </div>
            <p class="text-gray-900 dark:text-white font-medium">All clear!</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">No more lessons scheduled for today.</p>
          </div>

          <!-- Vertical Timeline implementation -->
          <div v-else class="relative z-10 pl-4 mt-4">
            <div class="absolute left-[23px] top-2 bottom-2 w-0.5 bg-gradient-to-b from-purple-500/50 via-gray-200 to-transparent dark:via-gray-700"></div>
            
            <ul class="space-y-8">
              <li v-for="lesson in store.upcomingLessons" :key="lesson.id" class="relative group">
                <!-- Timeline dot -->
                <div class="absolute -left-[27px] top-1 h-5 w-5 rounded-full border-4 border-white dark:border-gray-800 bg-purple-500 shadow-sm group-hover:scale-125 transition-transform duration-300"></div>
                
                <div class="pl-4">
                  <div class="flex items-baseline mb-1">
                    <span class="text-sm font-bold text-gray-900 dark:text-white mr-2">{{ lesson.start_time?.substring(0, 5) }}</span>
                    <span class="text-xs text-gray-500">- {{ lesson.end_time?.substring(0, 5) }}</span>
                  </div>
                  
                  <div class="bg-white/50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50 shadow-sm group-hover:shadow-md transition-all duration-300 group-hover:-translate-y-1">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ lesson.subject?.name }}</h4>
                    
                    <div class="mt-2 flex flex-col sm:flex-row sm:items-center text-xs text-gray-600 dark:text-gray-400 gap-2 sm:gap-4">
                      <span class="flex items-center">
                        <UsersIcon class="h-3.5 w-3.5 mr-1" />
                        {{ lesson.teacher?.name }}
                      </span>
                      <span class="flex items-center">
                        <MapPinIcon class="h-3.5 w-3.5 mr-1" />
                        {{ lesson.room?.name }}
                      </span>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useDashboardStore } from '@/stores/dashboardStore'
import RevenueChart from '@/components/dashboard/RevenueChart.vue'
import { 
  CalendarIcon, 
  BanknotesIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  CheckCircleIcon,
  MapPinIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'

const store = useDashboardStore()

const greetingMessage = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good Morning, Admin!'
  if (hour < 18) return 'Good Afternoon, Admin!'
  return 'Good Evening, Admin!'
})

onMounted(() => {
  store.fetchStats()
})
</script>
