<template>
<div>
  <div v-if="store.currentStudent" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-x-6">
      <div class="h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-2xl font-bold text-purple-700 dark:text-purple-300">
        {{ store.currentStudent.first_name[0] }}{{ store.currentStudent.last_name[0] }}
      </div>
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
          {{ store.currentStudent.first_name }} {{ store.currentStudent.last_name }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Parent: {{ store.currentStudent.parent_name }} | Phone: {{ store.currentStudent.parent_phone_1 }}
        </p>
      </div>
      <div class="ml-auto flex gap-3">
        <button @click="openEditSlideOver" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors">
          Edit Profile
        </button>
        <button @click="confirmDeleteStudent" class="bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 dark:border-red-900 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 px-4 py-2 rounded-xl text-sm font-medium shadow-sm transition-colors">
          Delete
        </button>
      </div>
    </div>

    <!-- Tabs Header -->
    <div class="border-b border-gray-200 dark:border-gray-800 mt-6">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button 
          @click="activeTab = 'overview'" 
          :class="[activeTab === 'overview' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          Overview
        </button>
        <button 
          @click="activeTab = 'timeline'" 
          :class="[activeTab === 'timeline' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Timeline
        </button>
        <button 
          @click="activeTab = 'invoices'" 
          :class="[activeTab === 'invoices' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Invoices
        </button>
        <button 
          @click="activeTab = 'documents'" 
          :class="[activeTab === 'documents' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 cursor-pointer transition-colors']"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
          </svg>
          Documents & Reports
        </button>
      </nav>
    </div>

    <!-- Overview Tab -->
    <div v-if="activeTab === 'overview'" class="space-y-6 mt-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Details Card -->
        <div class="glass-panel rounded-2xl p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Details</h3>
          <dl class="divide-y divide-gray-100 dark:divide-gray-800">
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-300">Billing Type</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-400 sm:col-span-2 sm:mt-0">{{ store.currentStudent.billing_type }}</dd>
            </div>
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-300">Status</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-400 sm:col-span-2 sm:mt-0">
                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                  {{ store.currentStudent.status }}
                </span>
              </dd>
            </div>
            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-300">DOB</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-400 sm:col-span-2 sm:mt-0">{{ store.currentStudent.date_of_birth }}</dd>
            </div>
          </dl>
        </div>

        <!-- Subjects -->
        <div class="glass-panel rounded-2xl p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enrolled Subjects</h3>
          <div v-if="!store.currentStudent.subjects || store.currentStudent.subjects.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
            No subjects assigned.
          </div>
          <div v-else class="flex flex-wrap gap-2">
            <span v-for="subject in store.currentStudent.subjects" :key="subject.id" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
              {{ subject.name }}
            </span>
          </div>
        </div>

        <!-- Teachers -->
        <div class="glass-panel rounded-2xl p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">My Teachers</h3>
          <div v-if="!store.currentStudent.teachers || store.currentStudent.teachers.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
            No teachers assigned.
          </div>
          <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
            <li v-for="teacher in store.currentStudent.teachers" :key="teacher.id" class="py-3 flex items-center justify-between group cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-lg px-2 -mx-2 transition-colors" @click="$router.push(`/teachers/${teacher.id}`)">
              <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-300 font-bold text-sm">
                  {{ teacher.name[0] }}
                </div>
                <span class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-purple-600 transition-colors">{{ teacher.name }}</span>
              </div>
              <svg class="w-5 h-5 text-gray-400 group-hover:text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
          </ul>
        </div>

        <!-- Hour Approvals & Vouchers -->
        <div class="glass-panel rounded-2xl p-6 lg:col-span-3">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Hour Approvals & Vouchers (Bewilligung)</h3>
            <button 
              @click="openAddPackageSlideOver"
              class="rounded-lg bg-purple-600 px-3 py-1.5 text-center text-xs font-semibold text-white hover:bg-purple-500 transition-colors cursor-pointer"
            >
              Add Hour Approval
            </button>
          </div>
          
          <div v-if="!store.currentStudent.packages || store.currentStudent.packages.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
            No hour approvals or vouchers registered yet.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
              <thead>
                <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  <th class="pb-3">Subject</th>
                  <th class="pb-3">Funding</th>
                  <th class="pb-3">Reference No.</th>
                  <th class="pb-3 text-center">Remaining / Total Hours</th>
                  <th class="pb-3">Status</th>
                  <th class="pb-3">Expires At</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50 text-sm">
                <tr v-for="pkg in store.currentStudent.packages" :key="pkg.id" class="text-gray-700 dark:text-gray-300">
                  <td class="py-3 font-semibold">{{ pkg.subject }}</td>
                  <td class="py-3 capitalize">{{ pkg.funding_source === 'jobcenter' ? 'Jobcenter (BuT)' : 'Private' }}</td>
                  <td class="py-3 font-mono text-xs">{{ pkg.voucher_reference || 'N/A' }}</td>
                  <td class="py-3 text-center font-bold">
                    <span class="text-purple-600 dark:text-purple-400">{{ pkg.remaining_hours }}</span>
                    <span class="text-gray-400"> / {{ pkg.total_hours }} hrs</span>
                  </td>
                  <td class="py-3">
                    <span :class="[
                      'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                      pkg.status === 'active' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/20 dark:text-green-400' : '',
                      pkg.status === 'pending_approval' ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20 dark:bg-orange-900/20 dark:text-orange-400' : '',
                      pkg.status === 'exhausted' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/20 dark:text-red-400' : '',
                      pkg.status === 'rejected' ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-400' : '',
                      pkg.status === 'expired' ? 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20 dark:bg-gray-800 dark:text-gray-400' : '',
                    ]">
                      {{ 
                        pkg.status === 'active' ? 'Active' :
                        pkg.status === 'pending_approval' ? 'Pending Approval (Antrag gestellt)' :
                        pkg.status === 'exhausted' ? 'Exhausted' :
                        pkg.status === 'rejected' ? 'Rejected (Abgelehnt)' :
                        pkg.status === 'expired' ? 'Expired (Abgelaufen)' : pkg.status 
                      }}
                    </span>
                  </td>
                  <td class="py-3 text-gray-500">{{ pkg.expires_at || 'Never' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Calendar View for Student's Lessons -->
      <div class="glass-panel p-6 rounded-2xl flex-1 min-h-[500px] flex flex-col relative">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Student Schedule</h2>
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

    <!-- Timeline Tab -->
    <div v-else-if="activeTab === 'timeline'" class="space-y-6 mt-6">
      <div class="glass-panel rounded-2xl p-6 space-y-6">
        <!-- Category Filters -->
        <div class="flex flex-wrap gap-2 items-center border-b border-gray-100 dark:border-gray-800 pb-4">
          <button 
            v-for="filter in timelineFilters" 
            :key="filter.value"
            @click="setTimelineFilter(filter.value)"
            :class="[
              timelineFilter === filter.value 
                ? 'bg-purple-600 text-white shadow-sm' 
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
              'px-4 py-2 rounded-xl text-xs font-semibold cursor-pointer transition-all flex items-center gap-1.5'
            ]"
          >
            <span>{{ filter.label }}</span>
          </button>
        </div>

        <!-- Loader -->
        <div v-if="isTimelineLoading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!timelineData.length" class="text-center py-12 text-gray-500 dark:text-gray-400">
          <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-base font-medium">No events recorded in this section currently.</p>
        </div>

        <!-- Timeline Feed -->
        <div v-else class="flow-root relative">
          <!-- Central Line -->
          <div class="absolute top-0 bottom-0 right-[25px] w-0.5 bg-gray-200 dark:bg-gray-800"></div>
          
          <ul class="space-y-8 relative">
            <li v-for="event in timelineData" :key="event.id" class="relative flex flex-row-reverse items-start gap-4">
              <!-- Event Box -->
              <div class="flex-1 bg-gray-50/50 dark:bg-gray-800/20 border border-gray-100 dark:border-gray-800/80 p-4 rounded-2xl hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start gap-2">
                  <div class="text-right">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ event.title }}</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">{{ event.description }}</p>
                  </div>
                  <div class="text-left shrink-0">
                    <span class="text-xs font-semibold text-gray-400 block">{{ event.date }}</span>
                    <span class="text-[10px] text-gray-500 block">{{ event.time }}</span>
                  </div>
                </div>

                <!-- Note/Change Specific details -->
                <div v-if="event.type === 'change' && event.metadata" class="mt-3 text-right">
                  <button 
                    @click="toggleEventDetails(event.id)"
                    class="text-[10px] font-bold text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1 cursor-pointer ml-auto"
                  >
                    <span>{{ showDetails[event.id] ? 'Hide Details' : 'Show System Edit Details' }}</span>
                    <svg :class="['w-3 h-3 transition-transform', showDetails[event.id] ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>

                  <div v-if="showDetails[event.id]" class="mt-2 text-[11px] bg-white dark:bg-gray-900 p-3 rounded-xl border border-gray-100 dark:border-gray-800/80 space-y-1.5 shadow-inner">
                    <div class="font-bold text-gray-500 border-b border-gray-100 dark:border-gray-800 pb-1 flex justify-between">
                      <span>Field</span>
                      <div class="flex gap-4">
                        <span class="w-24 text-center">Old Value</span>
                        <span class="w-24 text-center">New Value</span>
                      </div>
                    </div>
                    
                    <div v-for="(val, key) in event.metadata.new_values" :key="key" class="flex justify-between items-center py-0.5 last:border-0 border-b border-dashed border-gray-50 dark:border-gray-800/40">
                      <span class="font-semibold text-gray-600 dark:text-gray-400">{{ key }}</span>
                      <div class="flex gap-4">
                        <span class="w-24 text-center text-red-500 line-through truncate block">{{ formatValue(event.metadata.old_values?.[key]) }}</span>
                        <span class="w-24 text-center text-green-600 dark:text-green-400 font-bold truncate block">{{ formatValue(val) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Indicator Circle and Icon -->
              <div :class="[
                'w-[50px] h-[50px] rounded-full flex items-center justify-center border-4 border-white dark:border-gray-900 shrink-0 z-10 shadow-sm transition-transform hover:scale-110',
                event.color === 'purple' ? 'bg-purple-100 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400' : '',
                event.color === 'green' ? 'bg-green-100 text-green-600 dark:bg-green-950/40 dark:text-green-400' : '',
                event.color === 'emerald' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' : '',
                event.color === 'blue' ? 'bg-blue-100 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' : '',
                event.color === 'orange' ? 'bg-orange-100 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400' : '',
                event.color === 'gray' ? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' : '',
                event.color === 'red' ? 'bg-red-100 text-red-600 dark:bg-red-950/40 dark:text-red-400' : '',
                event.color === 'yellow' ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-950/40 dark:text-yellow-400' : '',
              ]">
                <!-- Dynamic Icons -->
                <!-- calendar -->
                <svg v-if="event.icon === 'calendar'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <!-- check-circle -->
                <svg v-if="event.icon === 'check-circle'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <!-- credit-card -->
                <svg v-if="event.icon === 'credit-card'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 00-3 3z" />
                </svg>
                <!-- archive -->
                <svg v-if="event.icon === 'archive'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <!-- chat-bubble -->
                <svg v-if="event.icon === 'chat-bubble'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <!-- document-text -->
                <svg v-if="event.icon === 'document-text'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
            </li>
          </ul>
        </div>

        <!-- Pagination -->
        <div v-if="timelineMeta.last_page > 1" class="flex justify-between items-center border-t border-gray-100 dark:border-gray-800 pt-4">
          <button 
            @click="changeTimelinePage(timelineMeta.current_page - 1)"
            :disabled="timelineMeta.current_page === 1"
            class="px-4 py-2 bg-gray-50 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 rounded-xl text-xs font-semibold cursor-pointer disabled:opacity-50 transition-colors"
          >
            Previous
          </button>
          <span class="text-xs text-gray-500 dark:text-gray-400">
            Page {{ timelineMeta.current_page }} of {{ timelineMeta.last_page }}
          </span>
          <button 
            @click="changeTimelinePage(timelineMeta.current_page + 1)"
            :disabled="timelineMeta.current_page === timelineMeta.last_page"
            class="px-4 py-2 bg-gray-50 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 rounded-xl text-xs font-semibold cursor-pointer disabled:opacity-50 transition-colors"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Invoices Tab -->
    <div v-else-if="activeTab === 'invoices'" class="glass-panel rounded-2xl p-6 mt-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Invoices</h3>
        <div v-if="!invoices.length" class="text-center text-gray-500 py-8">
          No invoices found.
        </div>
        <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
          <li v-for="invoice in invoices" :key="invoice.id" class="flex justify-between gap-x-6 py-4">
            <div class="flex min-w-0 gap-x-4">
              <div class="min-w-0 flex-auto">
                <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">{{ invoice.month }}</p>
                <p class="mt-1 truncate text-xs leading-5 text-gray-500">Status: {{ invoice.status }}</p>
              </div>
            </div>
            <div class="hidden sm:flex sm:flex-col sm:items-end">
              <p class="text-sm leading-6 text-gray-900 dark:text-white font-medium">€{{ invoice.total_amount }}</p>
              <button v-if="invoice.status === 'draft' || invoice.status === 'unpaid'" @click="markAsPaid(invoice)" class="mt-1 inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10 hover:bg-purple-100 transition-colors">
                Mark Paid
              </button>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Documents & Reports Tab -->
    <div v-else-if="activeTab === 'documents'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
      <!-- Documents List (col-span-2) -->
      <div class="glass-panel rounded-2xl p-6 lg:col-span-2 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Documents</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Manage official vouchers, extensions, contracts and invoices.</p>
          </div>
          <button 
            v-if="isAdmin"
            @click="openUploadModal"
            class="rounded-xl bg-purple-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-purple-500 transition-all cursor-pointer shadow-sm flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Upload Document
          </button>
        </div>

        <!-- Filter chips -->
        <div class="flex flex-wrap gap-2 pb-2">
          <button 
            v-for="cat in docCategories" 
            :key="cat.value"
            @click="activeDocFilter = cat.value"
            :class="[
              activeDocFilter === cat.value 
                ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 font-semibold' 
                : 'bg-gray-50 text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
              'px-3 py-1.5 rounded-lg text-xs transition-colors cursor-pointer'
            ]"
          >
            {{ cat.label }}
          </button>
        </div>

        <!-- Documents Table -->
        <div v-if="isDocsLoading" class="flex justify-center items-center h-48">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        </div>
        <div v-else-if="filteredDocuments.length === 0" class="text-center py-12 bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
          <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span class="text-sm text-gray-500 dark:text-gray-400">No documents found.</span>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead>
              <tr class="text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                <th class="pb-3">Title</th>
                <th class="pb-3">Category</th>
                <th class="pb-3">Doc Date</th>
                <th class="pb-3">Expiry Date</th>
                <th class="pb-3">Size</th>
                <th class="pb-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50 text-sm">
              <tr v-for="doc in filteredDocuments" :key="doc.id" class="text-gray-700 dark:text-gray-300 hover:bg-gray-50/50 dark:hover:bg-gray-800/10">
                <td class="py-3.5 font-semibold text-gray-900 dark:text-white">{{ doc.title }}</td>
                <td class="py-3.5">
                  <span :class="[
                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                    getCategoryClass(doc.category)
                  ]">
                    {{ getCategoryLabel(doc.category) }}
                  </span>
                </td>
                <td class="py-3.5 text-gray-500">{{ formatDate(doc.document_date) }}</td>
                <td class="py-3.5 text-gray-500">{{ doc.expires_at ? formatDate(doc.expires_at) : '-' }}</td>
                <td class="py-3.5 text-gray-500 text-xs">{{ formatBytes(doc.size) }}</td>
                <td class="py-3.5 text-right space-x-2">
                  <button 
                    @click="downloadDoc(doc)"
                    class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 font-semibold text-xs cursor-pointer inline-flex items-center gap-0.5"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download
                  </button>
                  <button 
                    v-if="isAdmin"
                    @click="deleteDoc(doc)"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-semibold text-xs cursor-pointer"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Export Generator & Reports (col-span-1) -->
      <div class="glass-panel rounded-2xl p-6 space-y-6 self-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reports & Export</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Generate daily verification sheets for Jobcenter.</p>
        </div>

        <form @submit.prevent="generateStundennachweis" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Target Month</label>
            <input 
              type="month" 
              v-model="reportMonth" 
              required
              class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Subject Filter (Optional)</label>
            <select 
              v-model="reportSubject"
              class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
            >
              <option value="">All Subjects</option>
              <option v-for="sub in store.currentStudent.subjects" :key="sub.id" :value="sub.id">
                {{ sub.name }}
              </option>
            </select>
          </div>

          <button 
            type="submit"
            :disabled="isExporting"
            class="w-full rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 py-3 text-center text-sm font-semibold text-white transition-all shadow-md cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <svg v-if="isExporting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            {{ isExporting ? 'Generating PDF...' : 'Export Stundennachweis' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Upload Document Modal -->
    <div v-if="isUploadModalOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm dark:bg-black dark:bg-opacity-80"></div>
      <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-6 space-y-4 border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="modal-title">Upload Student Document</h3>
              <button @click="closeUploadModal" class="text-gray-400 hover:text-gray-500 cursor-pointer">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="submitUpload" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Title</label>
                <input 
                  type="text" 
                  v-model="uploadForm.title" 
                  required
                  placeholder="e.g. Jobcenter Bewilligungsbescheid 2026"
                  class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                <select 
                  v-model="uploadForm.category" 
                  required
                  class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                >
                  <option v-for="cat in uploadCategories" :key="cat.value" :value="cat.value">
                    {{ cat.label }}
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Document Date</label>
                  <input 
                    type="date" 
                    v-model="uploadForm.document_date" 
                    required
                    class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Expiry Date (Optional)</label>
                  <input 
                    type="date" 
                    v-model="uploadForm.expires_at" 
                    class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-purple-500 focus:ring-purple-500"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select File (PDF, PNG, JPG, Max 10MB)</label>
                <div class="mt-1 flex justify-center rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 px-6 pt-5 pb-6">
                  <div class="space-y-1 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                      <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                      <label class="relative cursor-pointer rounded-md bg-white dark:bg-gray-900 font-semibold text-purple-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-purple-600 focus-within:ring-offset-2 hover:text-purple-500">
                        <span>Upload a file</span>
                        <input type="file" ref="fileInput" @change="handleFileChange" required class="sr-only" />
                      </label>
                    </div>
                    <p class="text-xs text-gray-500">{{ selectedFileName || 'No file selected' }}</p>
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-3 border-t border-gray-100 dark:border-gray-800 pt-3">
                <button 
                  type="button" 
                  @click="closeUploadModal" 
                  class="rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                >
                  Cancel
                </button>
                <button 
                  type="submit"
                  :disabled="isUploading"
                  class="rounded-xl bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-500 cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                >
                  <svg v-if="isUploading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  Submit
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <LessonDetailSlideOver ref="lessonDetailSlideOver" @edit-lesson="handleEditLesson" />
    <ScheduleLessonSlideOver ref="scheduleLessonSlideOver" />
    <EditStudentSlideOver ref="editSlideOver" />
    <ConfirmModal ref="confirmModal" />
    <AddPackageSlideOver ref="addPackageSlideOver" />
  </div>
  
  <div v-else-if="store.isLoading" class="flex justify-center items-center h-64">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
  </div>
</div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStudentsStore } from '@/stores/studentsStore'
import { useLessonsStore, type Lesson } from '@/stores/lessonsStore'
import { useAuthStore } from '@/stores/authStore'
import EditStudentSlideOver from '@/components/students/EditStudentSlideOver.vue'
import LessonDetailSlideOver from '@/components/lessons/LessonDetailSlideOver.vue'
import ScheduleLessonSlideOver from '@/components/lessons/ScheduleLessonSlideOver.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import Calendar from '@/components/calendar/Calendar.vue'
import AddPackageSlideOver from '@/components/students/AddPackageSlideOver.vue'
import api from '@/api'

const route = useRoute()
const router = useRouter()
const store = useStudentsStore()
const lessonsStore = useLessonsStore()
const authStore = useAuthStore()

const editSlideOver = ref<InstanceType<typeof EditStudentSlideOver> | null>(null)
const lessonDetailSlideOver = ref<InstanceType<typeof LessonDetailSlideOver> | null>(null)
const scheduleLessonSlideOver = ref<InstanceType<typeof ScheduleLessonSlideOver> | null>(null)
const confirmModal = ref<InstanceType<typeof ConfirmModal> | null>(null)
const addPackageSlideOver = ref<InstanceType<typeof AddPackageSlideOver> | null>(null)

function openAddPackageSlideOver() {
  addPackageSlideOver.value?.open()
}

const activeTab = ref('overview')
const invoices = ref<any[]>([])

const isAdmin = computed(() => {
  return authStore.user?.roles?.some((r: any) => ['Admin', 'Super Admin'].includes(typeof r === 'string' ? r : r.name))
})

// Documents data
const documents = ref<any[]>([])
const isDocsLoading = ref(false)
const activeDocFilter = ref('all')

const docCategories = [
  { label: 'All', value: 'all' },
  { label: 'Vouchers (Antrag)', value: 'application' },
  { label: 'Extensions (Verlängerung)', value: 'extension' },
  { label: 'Contracts (Vertrag)', value: 'contract' },
  { label: 'Invoices (Rechnung)', value: 'invoice' },
  { label: 'Attendance Sheets', value: 'attendance_sheet' },
  { label: 'Other', value: 'other' }
]

const uploadCategories = [
  { label: 'Voucher (Antrag)', value: 'application' },
  { label: 'Extension (Verlängerung)', value: 'extension' },
  { label: 'Contract (Vertrag)', value: 'contract' },
  { label: 'Invoice (Rechnung)', value: 'invoice' },
  { label: 'Attendance Sheet', value: 'attendance_sheet' },
  { label: 'Other', value: 'other' }
]

const filteredDocuments = computed(() => {
  if (activeDocFilter.value === 'all') return documents.value
  return documents.value.filter(doc => doc.category === activeDocFilter.value)
})

async function fetchDocuments(studentId: string) {
  isDocsLoading.value = true
  try {
    const res = await api.get(`/nachhilfe/students/${studentId}/documents`)
    documents.value = res.data
  } catch (err) {
    console.error('Failed to load documents', err)
  } finally {
    isDocsLoading.value = false
  }
}

// Upload Modal & Form
const isUploadModalOpen = ref(false)
const isUploading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const selectedFileName = ref('')

const uploadForm = ref({
  title: '',
  category: 'application',
  document_date: new Date().toISOString().split('T')[0],
  expires_at: ''
})

function openUploadModal() {
  selectedFile.value = null
  selectedFileName.value = ''
  uploadForm.value = {
    title: '',
    category: 'application',
    document_date: new Date().toISOString().split('T')[0],
    expires_at: ''
  }
  isUploadModalOpen.value = true
}

function closeUploadModal() {
  isUploadModalOpen.value = false
}

function handleFileChange(event: any) {
  const files = event.target.files
  if (files && files.length > 0) {
    selectedFile.value = files[0]
    selectedFileName.value = files[0].name
  }
}

async function submitUpload() {
  if (!selectedFile.value || !route.params.id) return
  isUploading.value = true
  try {
    const formData = new FormData()
    formData.append('title', uploadForm.value.title)
    formData.append('category', uploadForm.value.category)
    formData.append('document_date', uploadForm.value.document_date)
    if (uploadForm.value.expires_at) {
      formData.append('expires_at', uploadForm.value.expires_at)
    }
    formData.append('file', selectedFile.value)

    await api.post(`/nachhilfe/students/${route.params.id}/documents`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    closeUploadModal()
    fetchDocuments(route.params.id as string)
  } catch (err) {
    console.error('Upload failed', err)
  } finally {
    isUploading.value = false
  }
}

// Download & Delete
async function downloadDoc(doc: any) {
  try {
    const response = await api.get(`/nachhilfe/documents/${doc.id}/download`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', doc.title + '.' + getFileExtension(doc.mime_type))
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (err) {
    console.error('Download failed', err)
  }
}

function getFileExtension(mimeType: string): string {
  const map: Record<string, string> = {
    'application/pdf': 'pdf',
    'image/png': 'png',
    'image/jpeg': 'jpg',
    'image/jpg': 'jpg',
  }
  return map[mimeType] ?? 'bin'
}

async function deleteDoc(doc: any) {
  if (!confirm(`Are you sure you want to delete "${doc.title}"?`)) return
  try {
    await api.delete(`/nachhilfe/documents/${doc.id}`)
    if (route.params.id) {
      fetchDocuments(route.params.id as string)
    }
  } catch (err) {
    console.error('Delete failed', err)
  }
}

// Stundennachweis Export
const reportMonth = ref(new Date().toISOString().substring(0, 7))
const reportSubject = ref('')
const isExporting = ref(false)

async function generateStundennachweis() {
  if (!route.params.id) return
  isExporting.value = true
  try {
    let url = `/nachhilfe/students/${route.params.id}/stundennachweis?month=${reportMonth.value}`
    if (reportSubject.value) {
      url += `&subject_id=${reportSubject.value}`
    }
    const response = await api.get(url, {
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.setAttribute('download', `Stundennachweis_${store.currentStudent?.last_name || 'Student'}_${reportMonth.value}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (err) {
    console.error('Export failed', err)
  } finally {
    isExporting.value = false
  }
}

// Metadata formatting helpers
function getCategoryLabel(category: string): string {
  const map: Record<string, string> = {
    application: 'Voucher (Antrag)',
    extension: 'Extension',
    contract: 'Contract',
    invoice: 'Invoice',
    attendance_sheet: 'Attendance Sheet',
    other: 'Other'
  }
  return map[category] ?? category
}

function getCategoryClass(category: string): string {
  const map: Record<string, string> = {
    application: 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400',
    extension: 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-300',
    contract: 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-300',
    invoice: 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400',
    attendance_sheet: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/30 dark:text-emerald-400',
    other: 'bg-gray-50 text-gray-600 ring-gray-550/10 dark:bg-gray-800 dark:text-gray-400'
  }
  return map[category] ?? 'bg-gray-50 text-gray-600 ring-gray-550/10'
}

function formatDate(dateStr: string): string {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch (e) {
    return dateStr
  }
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const timelineData = ref<any[]>([])
const timelineMeta = ref<any>({ current_page: 1, last_page: 1 })
const timelineFilter = ref('all')
const isTimelineLoading = ref(false)
const showDetails = ref<Record<string, boolean>>({})

const timelineFilters = [
  { label: 'All', value: 'all' },
  { label: 'Lessons', value: 'lesson' },
  { label: 'Attendance', value: 'attendance' },
  { label: 'Payments', value: 'payment' },
  { label: 'Packages', value: 'package' },
  { label: 'Notes', value: 'note' },
  { label: 'System Changes', value: 'change' },
]

async function fetchTimeline(page = 1) {
  if (!route.params.id) return
  isTimelineLoading.value = true
  try {
    const params: Record<string, any> = { page }
    if (timelineFilter.value !== 'all') {
      params.type = timelineFilter.value
    }
    const res = await api.get(`/nachhilfe/students/${route.params.id}/timeline`, { params })
    timelineData.value = res.data.data
    timelineMeta.value = res.data.meta
  } catch (err) {
    console.error('Failed to load timeline', err)
  } finally {
    isTimelineLoading.value = false
  }
}

function setTimelineFilter(value: string) {
  timelineFilter.value = value
  fetchTimeline(1)
}

function changeTimelinePage(page: number) {
  fetchTimeline(page)
}

function toggleEventDetails(eventId: string) {
  showDetails.value[eventId] = !showDetails.value[eventId]
}

function formatValue(value: any): string {
  if (value === null || value === undefined) return 'None'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

async function fetchInvoices(id: string) {
  try {
    const res = await api.get(`/nachhilfe/invoices?student_id=${id}`)
    invoices.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load invoices', err)
  }
}

async function markAsPaid(invoice: any) {
  try {
    await api.patch(`/nachhilfe/invoices/${invoice.id}/status`, { status: 'paid' })
    if (store.currentStudent) {
      fetchInvoices(store.currentStudent.id)
    }
  } catch (err) {
    console.error('Failed to mark as paid', err)
  }
}

onMounted(() => {
  if (route.params.id) {
    store.fetchStudent(route.params.id as string)
    fetchInvoices(route.params.id as string)
    lessonsStore.fetchLessons({ student_id: route.params.id })
    if (activeTab.value === 'timeline') {
      fetchTimeline(1)
    } else if (activeTab.value === 'documents') {
      fetchDocuments(route.params.id as string)
    }
  }
})

watch(() => route.params.id, (newId) => {
  if (newId) {
    store.fetchStudent(newId as string)
    fetchInvoices(newId as string)
    lessonsStore.fetchLessons({ student_id: newId as string })
    if (activeTab.value === 'timeline') {
      fetchTimeline(1)
    } else if (activeTab.value === 'documents') {
      fetchDocuments(newId as string)
    }
  }
})

watch(activeTab, (newTab) => {
  if (newTab === 'timeline') {
    fetchTimeline(1)
  } else if (newTab === 'documents' && route.params.id) {
    fetchDocuments(route.params.id as string)
  }
})

function handleLessonClick(lesson: Lesson) {
  lessonDetailSlideOver.value?.open(lesson)
}

function handleEditLesson(lesson: Lesson) {
  scheduleLessonSlideOver.value?.open(lesson)
}

function openEditSlideOver() {
  if (store.currentStudent) {
    editSlideOver.value?.open(store.currentStudent)
  }
}

function confirmDeleteStudent() {
  const student = store.currentStudent
  if (!student) return
  
  confirmModal.value?.open(
    'Delete Student',
    `Are you sure you want to delete ${student.first_name} ${student.last_name}? This action cannot be undone.`,
    'Delete',
    'Cancel',
    async () => {
      const success = await store.deleteStudent(student.id)
      if (success) {
        router.push('/students')
      }
    }
  )
}
</script>
