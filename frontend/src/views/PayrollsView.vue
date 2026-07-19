<template>
  <div>
    <div class="space-y-6 h-full flex flex-col">
      <div class="flex items-center justify-between shrink-0">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Payrolls</h1>
        <button @click="openGenerateModal" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl font-medium shadow-sm transition-colors flex items-center gap-2 cursor-pointer">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Generate Payroll
        </button>
      </div>

      <!-- Filters bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 shrink-0 bg-white/50 dark:bg-gray-800/50 backdrop-blur-md p-4 rounded-2xl border border-gray-100 dark:border-gray-700/80 shadow-sm">
        <div class="flex flex-1 flex-wrap items-center gap-3">
          <!-- Search input -->
          <div class="relative w-full md:w-64">
            <input
              v-model="filterSearch"
              @input="onSearch"
              type="text"
              placeholder="Search by name..."
              class="block w-full rounded-xl border border-gray-350 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white pr-4 pl-10 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
              style="padding-left: 2.5rem !important;"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <!-- Date Filter Selector -->
          <select v-model="dateFilterType" @change="onDateFilterTypeChange" class="rounded-xl border border-gray-350 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="all">All Dates</option>
            <option value="single">Specific Day</option>
            <option value="range">Date Range</option>
          </select>

          <!-- Specific Day picker -->
          <input
            v-if="dateFilterType === 'single'"
            v-model="filterDate"
            @change="applyFilters"
            type="date"
            class="rounded-xl border border-gray-350 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
          />

          <!-- Range pickers -->
          <div v-if="dateFilterType === 'range'" class="flex items-center gap-2">
            <input
              v-model="filterFromDate"
              @change="applyFilters"
              type="date"
              class="rounded-xl border border-gray-350 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
            />
            <span class="text-xs text-gray-500">to</span>
            <input
              v-model="filterToDate"
              @change="applyFilters"
              type="date"
              class="rounded-xl border border-gray-350 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
            />
          </div>
        </div>

        <div>
          <button @click="resetFilters" class="text-sm font-medium text-purple-600 hover:text-purple-750 dark:text-purple-400 dark:hover:text-purple-300 underline cursor-pointer">
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="glass-panel rounded-2xl flex-1 flex flex-col overflow-hidden relative">
        <div v-if="isLoading" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm flex items-center justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50/50 dark:bg-gray-800/50 backdrop-blur-sm">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">ID</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Teacher</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Month</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Amount</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
              <tr v-for="payroll in payrolls" :key="payroll.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6 text-gray-900 dark:text-gray-100 font-mono">
                  <span>{{ payroll.id.substring(0, 8) }}...</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                  <div class="font-medium text-gray-900 dark:text-gray-100">{{ payroll.teacher?.name }}</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono">
                  {{ payroll.month }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-100">
                  <div class="font-bold font-mono">€{{ payroll.total_amount }}</div>
                  <div v-if="isEdited(payroll)" class="text-[10px] text-gray-400 dark:text-gray-500 font-normal">
                    Edited: {{ formatDateTime(payroll.updated_at!) }}
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                  <span :class="[
                    payroll.status === 'Paid' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : 
                    payroll.status === 'Void' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400' :
                    'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset tracking-wide'
                  ]">
                    {{ payroll.status }}
                  </span>
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-3">
                  <button @click="openEditModal(payroll)" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-400/30 dark:hover:bg-blue-900/50 transition-colors cursor-pointer">
                    Edit
                  </button>
                  <button @click="openPrintModal(payroll)" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-700/10 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-400/30 dark:hover:bg-purple-900/50 transition-colors cursor-pointer">
                    View/Print
                  </button>
                  <button @click="deletePayroll(payroll)" class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-700/10 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-400/30 dark:hover:bg-red-900/50 transition-colors cursor-pointer">
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="payrolls.length === 0 && !isLoading">
                <td colspan="6" class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                  No payrolls found.
                </td>
              </tr>
            </tbody>
          </table>
          
          <!-- Pagination -->
          <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-800" v-if="payrolls.length > 0">
             <span class="text-sm text-gray-500">Showing page {{ currentPage }} of {{ totalPages }}</span>
             <div class="space-x-2">
               <button :disabled="currentPage === 1" @click="fetchPayrolls(currentPage - 1)" class="px-3 py-1 text-sm border rounded text-gray-600 disabled:opacity-50 dark:text-gray-300 dark:border-gray-700 cursor-pointer">Previous</button>
               <button :disabled="currentPage === totalPages" @click="fetchPayrolls(currentPage + 1)" class="px-3 py-1 text-sm border rounded text-gray-600 disabled:opacity-50 dark:text-gray-300 dark:border-gray-700 cursor-pointer">Next</button>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 backdrop-blur-sm transition-opacity" @click="showEditModal = false"></div>

      <!-- Modal Content -->
      <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
          <div class="p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Edit Payroll ({{ editPayrollData.id.substring(0, 8) }}...)</h3>
            
            <form @submit.prevent="savePayroll">
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                  <select v-model="editPayrollData.status" class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="Draft">Draft</option>
                    <option value="Processing">Processing</option>
                    <option value="Paid">Paid</option>
                    <option value="Void">Void</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Amount (€)</label>
                  <input type="number" step="0.01" min="0" max="99999999.99" required v-model="editPayrollData.total_amount" class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
                </div>
              </div>
              
              <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="showEditModal = false" class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                <button type="submit" :disabled="isSaving" class="rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-4 py-2 text-sm font-semibold shadow-sm transition-colors">
                  <span v-if="isSaving">Saving...</span>
                  <span v-else>Save Changes</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    <!-- Generate Payroll Modal -->
    <div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 backdrop-blur-sm transition-opacity" @click="showGenerateModal = false"></div>

      <!-- Modal Content -->
      <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Generate Monthly Payroll</h3>
          
          <form @submit.prevent="generatePayroll">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Teacher</label>
                <select v-model="generatePayrollData.teacher_id" required class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                  <option value="" disabled>-- Select Teacher --</option>
                  <option v-for="teacher in teachersList" :key="teacher.id" :value="teacher.id">
                    {{ teacher.name }}
                  </option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Billing Month</label>
                <input type="month" required v-model="generatePayrollData.month" class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Amount (€) (Leave empty to auto-calculate)</label>
                <input type="number" step="0.01" min="0" max="99999999.99" v-model="generatePayrollData.total_amount" placeholder="e.g. 200.00" class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Initial Status (Optional)</label>
                <select v-model="generatePayrollData.status" class="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 sm:text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                  <option value="">Default (Draft)</option>
                  <option value="Draft">Draft</option>
                  <option value="Processing">Processing</option>
                  <option value="Paid">Paid</option>
                  <option value="Void">Void</option>
                </select>
              </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
              <button type="button" @click="showGenerateModal = false" class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
              <button type="submit" :disabled="isGenerating" class="rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white px-4 py-2 text-sm font-semibold shadow-sm transition-colors">
                <span v-if="isGenerating">Generating...</span>
                <span v-else>Generate</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- View & Print Modal -->
    <div v-if="showPrintModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 backdrop-blur-sm transition-opacity" @click="showPrintModal = false"></div>

      <!-- Modal Content -->
      <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full border border-gray-100 dark:border-gray-700 max-h-[90vh] flex flex-col">
        <!-- Modal Toolbar -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50 shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Payroll Payment Slip</h3>
            <div class="flex items-center gap-2">
              <button @click="triggerPrint" class="inline-flex items-center gap-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h6z" />
                </svg>
                Print Slip
              </button>
              <button @click="showPrintModal = false" class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-2 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Close</button>
            </div>
          </div>

          <!-- Screen view of Payroll -->
          <div class="p-8 overflow-y-auto custom-scrollbar bg-white dark:bg-gray-900 flex-1">
            <div v-if="isDetailsLoading" class="flex justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
            </div>
            <div v-else-if="activePayrollDetail" class="space-y-8 text-gray-900 dark:text-gray-100">
              <!-- Header -->
              <div class="flex justify-between items-start border-b border-gray-100 dark:border-gray-800 pb-6">
                <div>
                  <h4 class="text-2xl font-black text-purple-600 dark:text-purple-400">Nachhilfe Center</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Teacher Compensation Slip</p>
                </div>
                <div class="text-right">
                  <h4 class="text-xl font-bold uppercase tracking-wider text-gray-400">Payroll ID</h4>
                  <p class="text-md font-mono font-bold mt-1 text-purple-600 dark:text-purple-400">{{ activePayrollDetail.id }}</p>
                </div>
              </div>

              <!-- Metadata Grid -->
              <div class="grid grid-cols-2 gap-6 text-sm">
                <div>
                  <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider block font-medium">Teacher</span>
                  <div class="font-bold text-base mt-1 text-gray-800 dark:text-gray-100">{{ activePayrollDetail.teacher?.name }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Teacher ID: {{ activePayrollDetail.teacher?.id.substring(0, 8) }}...</div>
                </div>
                <div class="text-right">
                  <div class="space-y-1">
                    <div>
                      <span class="text-gray-400">Payroll Month: </span>
                      <span class="font-bold font-mono">{{ activePayrollDetail.month }}</span>
                    </div>
                    <div>
                      <span class="text-gray-400">Completed Lessons: </span>
                      <span class="font-bold">{{ activePayrollDetail.total_completed_lessons }}</span>
                    </div>
                    <div>
                      <span class="text-gray-400">Total Hours: </span>
                      <span class="font-bold font-mono">{{ activePayrollDetail.total_hours }} hrs</span>
                    </div>
                    <div>
                      <span class="text-gray-400">Status: </span>
                      <span class="font-bold uppercase tracking-wider text-xs px-2 py-0.5 rounded ml-1" :class="[
                        activePayrollDetail.status === 'Paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                        activePayrollDetail.status === 'Void' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                      ]">{{ activePayrollDetail.status }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Items Table -->
              <div>
                <table class="w-full text-left border-collapse text-sm">
                  <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase text-gray-400 font-bold">
                      <th class="py-2.5">Lesson Details</th>
                      <th class="py-2.5 text-center">Hours</th>
                      <th class="py-2.5 text-right">Hourly Rate</th>
                      <th class="py-2.5 text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr v-for="item in activePayrollDetail.items" :key="item.id">
                      <td class="py-3">
                        <div class="font-semibold text-gray-900 dark:text-white" v-if="item.lesson">
                          Lesson: {{ new Date(item.lesson.start_time).toLocaleDateString() }}
                        </div>
                        <div v-if="item.lesson" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                          Subject: {{ item.lesson.subject?.name }} | Room: {{ item.lesson.room?.name || 'N/A' }}
                        </div>
                      </td>
                      <td class="py-3 text-center font-mono">{{ item.hours }} hrs</td>
                      <td class="py-3 text-right font-mono">€{{ item.hourly_rate }}</td>
                      <td class="py-3 text-right font-mono font-semibold">€{{ item.amount }}</td>
                    </tr>
                    <tr v-if="!activePayrollDetail.items || activePayrollDetail.items.length === 0">
                      <td colspan="4" class="py-8 text-center text-gray-500">No lessons itemized in this payroll.</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Total summary block -->
              <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-855">
                <div class="w-64 text-right space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Total Payout Amount:</span>
                    <span class="font-bold text-lg font-mono text-purple-600 dark:text-purple-400 font-semibold">€{{ activePayrollDetail.total_amount }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>

    <!-- Printable Version (Teleported to body) -->
    <Teleport to="body" v-if="showPrintModal && activePayrollDetail && !isDetailsLoading">
      <div class="hidden print:block print-payroll-wrapper">
        <div class="print-payroll-sheet">
          <div class="header">
            <div>
              <div class="brand-title">Nachhilfe Center</div>
              <div class="brand-subtitle">Teacher Compensation Slip</div>
            </div>
            <div class="payroll-title-block">
              <div class="title">PAYOUT SLIP</div>
              <div class="id-str">ID: {{ activePayrollDetail.id }}</div>
            </div>
          </div>

          <div class="divider"></div>

          <div class="details-grid">
            <div class="recipient">
              <span class="label">Recipient / Teacher</span>
              <div class="value name">{{ activePayrollDetail.teacher?.name }}</div>
              <div class="value">Teacher ID: {{ activePayrollDetail.teacher?.id }}</div>
            </div>
            <div class="meta-info">
              <div class="meta-row">
                <span class="label">Payroll Month:</span>
                <span class="value font-mono">{{ activePayrollDetail.month }}</span>
              </div>
              <div class="meta-row">
                <span class="label">Completed Lessons:</span>
                <span class="value font-mono">{{ activePayrollDetail.total_completed_lessons }}</span>
              </div>
              <div class="meta-row">
                <span class="label">Total Hours:</span>
                <span class="value font-mono">{{ activePayrollDetail.total_hours }} hrs</span>
              </div>
              <div class="meta-row">
                <span class="label">Status:</span>
                <span class="value status-badge" :class="activePayrollDetail.status">{{ activePayrollDetail.status.toUpperCase() }}</span>
              </div>
            </div>
          </div>

          <div class="divider"></div>

          <table class="items-table">
            <thead>
              <tr>
                <th>Lesson Details</th>
                <th class="center">Hours</th>
                <th class="right">Hourly Rate</th>
                <th class="right">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in activePayrollDetail.items" :key="item.id">
                <td>
                  <div class="item-title" v-if="item.lesson">
                    Lesson: {{ new Date(item.lesson.start_time).toLocaleDateString() }}
                  </div>
                  <div class="item-sub" v-if="item.lesson">
                    Subject: {{ item.lesson.subject?.name }} | Room: {{ item.lesson.room?.name || 'N/A' }}
                  </div>
                </td>
                <td class="center font-mono">{{ item.hours }} hrs</td>
                <td class="right font-mono">€{{ item.hourly_rate }}</td>
                <td class="right font-mono bold">€{{ item.amount }}</td>
              </tr>
            </tbody>
          </table>

          <div class="divider"></div>

          <div class="totals-section">
            <div class="total-row">
              <span class="total-label">Total Payout Amount:</span>
              <span class="total-val font-mono">€{{ activePayrollDetail.total_amount }}</span>
            </div>
          </div>
          
          <div class="footer-msg">
            Thank you for your valuable contribution to Nachhilfe Center.
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useToastStore } from '@/stores/toastStore'

interface Teacher {
  id: string
  name: string
}

interface Payroll {
  id: string
  teacher_id: string
  month: string
  total_amount: number
  status: string
  created_at?: string
  updated_at?: string
  teacher?: Teacher
}

const payrolls = ref<Payroll[]>([])
const isLoading = ref(true)
const toast = useToastStore()
const currentPage = ref(1)
const totalPages = ref(1)

// Edit Modal State
const showEditModal = ref(false)
const isSaving = ref(false)
const editPayrollData = ref({
  id: '',
  status: 'Draft',
  total_amount: 0
})

// Generate Modal State
const showGenerateModal = ref(false)
const isGenerating = ref(false)
const generatePayrollData = ref({
  teacher_id: '',
  month: new Date().toISOString().substring(0, 7),
  total_amount: null as number | null,
  status: ''
})
const teachersList = ref<any[]>([])

// Detailed View/Print State
const showPrintModal = ref(false)
const isDetailsLoading = ref(false)
const activePayrollDetail = ref<any>(null)

// Filter States
const filterSearch = ref('')
const dateFilterType = ref('all') // 'all', 'single', 'range'
const filterDate = ref('')
const filterFromDate = ref('')
const filterToDate = ref('')

const fetchPayrolls = async (page = 1) => {
  isLoading.value = true
  try {
    const params: any = { page }
    
    if (filterSearch.value.trim()) {
      params.search = filterSearch.value.trim()
    }
    
    if (dateFilterType.value === 'single' && filterDate.value) {
      params.date = filterDate.value
    } else if (dateFilterType.value === 'range') {
      if (filterFromDate.value) params.from_date = filterFromDate.value
      if (filterToDate.value) params.to_date = filterToDate.value
    }
    
    const response = await api.get('/nachhilfe/payrolls', { params })
    payrolls.value = response.data.data
    currentPage.value = response.data.current_page
    totalPages.value = response.data.last_page
  } catch (error: any) {
    console.error(error)
    toast.error('Failed to load payrolls')
  } finally {
    isLoading.value = false
  }
}

const onSearch = () => {
  fetchPayrolls(1)
}

const onDateFilterTypeChange = () => {
  filterDate.value = ''
  filterFromDate.value = ''
  filterToDate.value = ''
  fetchPayrolls(1)
}

const applyFilters = () => {
  fetchPayrolls(1)
}

const resetFilters = () => {
  filterSearch.value = ''
  dateFilterType.value = 'all'
  filterDate.value = ''
  filterFromDate.value = ''
  filterToDate.value = ''
  fetchPayrolls(1)
}

const fetchTeachersForGenerate = async () => {
  try {
    const response = await api.get('/nachhilfe/teachers', {
      params: { per_page: 1000 }
    })
    teachersList.value = response.data.data
  } catch (error) {
    console.error(error)
  }
}

const openGenerateModal = async () => {
  generatePayrollData.value = {
    teacher_id: '',
    month: new Date().toISOString().substring(0, 7),
    total_amount: null,
    status: ''
  }
  showGenerateModal.value = true
  await fetchTeachersForGenerate()
}

const generatePayroll = async () => {
  if (!generatePayrollData.value.teacher_id || !generatePayrollData.value.month) {
    toast.error('Please select a teacher and a billing month')
    return
  }
  isGenerating.value = true
  try {
    const payload: any = {
      teacher_id: generatePayrollData.value.teacher_id,
      month: generatePayrollData.value.month
    }
    if (generatePayrollData.value.total_amount !== null && (generatePayrollData.value.total_amount as any) !== '') {
      payload.total_amount = generatePayrollData.value.total_amount
    }
    if (generatePayrollData.value.status) {
      payload.status = generatePayrollData.value.status
    }

    await api.post('/nachhilfe/payrolls', payload)
    toast.success('Payroll generated successfully')
    showGenerateModal.value = false
    fetchPayrolls(1)
  } catch (error: any) {
    console.error(error)
    const errorMsg = error.response?.data?.message || 'Failed to generate payroll'
    toast.error(errorMsg)
  } finally {
    isGenerating.value = false
  }
}

const openEditModal = (payroll: Payroll) => {
  editPayrollData.value = {
    id: payroll.id,
    status: payroll.status,
    total_amount: payroll.total_amount
  }
  showEditModal.value = true
}

const savePayroll = async () => {
  isSaving.value = true
  try {
    await api.put(`/nachhilfe/payrolls/${editPayrollData.value.id}`, {
      status: editPayrollData.value.status,
      total_amount: editPayrollData.value.total_amount
    })
    toast.success('Payroll updated successfully')
    showEditModal.value = false
    fetchPayrolls(currentPage.value)
  } catch (error: any) {
    console.error(error)
    toast.error('Failed to update payroll')
  } finally {
    isSaving.value = false
  }
}

const deletePayroll = async (payroll: Payroll) => {
  if (!confirm(`Are you sure you want to delete this payroll record?`)) {
    return
  }
  try {
    await api.delete(`/nachhilfe/payrolls/${payroll.id}`)
    toast.success('Payroll deleted successfully')
    fetchPayrolls(currentPage.value)
  } catch (error: any) {
    console.error(error)
    toast.error('Failed to delete payroll')
  }
}

const openPrintModal = async (payroll: Payroll) => {
  activePayrollDetail.value = null
  showPrintModal.value = true
  isDetailsLoading.value = true
  try {
    const res = await api.get(`/nachhilfe/payrolls/${payroll.id}`)
    activePayrollDetail.value = res.data
  } catch (error) {
    console.error(error)
    toast.error('Failed to load payroll details')
    showPrintModal.value = false
  } finally {
    isDetailsLoading.value = false
  }
}

const triggerPrint = () => {
  window.print()
}

const isEdited = (item: any) => {
  if (!item.created_at || !item.updated_at) return false
  const diff = new Date(item.updated_at).getTime() - new Date(item.created_at).getTime()
  return diff > 1000
}

const formatDateTime = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchPayrolls()
})
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

  .print-payroll-wrapper {
    display: block !important;
    width: 100% !important;
    background: white !important;
    padding: 30px !important;
    box-sizing: border-box !important;
  }
  
  .print-payroll-sheet {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    color: #111827;
  }
  
  .print-payroll-sheet .header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
  }
  
  .print-payroll-sheet .brand-title {
    font-size: 24px;
    font-weight: 900;
    color: #7c3aed; /* Purple-600 */
  }
  
  .print-payroll-sheet .brand-subtitle {
    font-size: 12px;
    color: #4b5563;
  }
  
  .print-payroll-sheet .payroll-title-block {
    text-align: right;
  }
  
  .print-payroll-sheet .payroll-title-block .title {
    font-size: 20px;
    font-weight: 800;
    color: #9ca3af;
    letter-spacing: 0.1em;
  }
  
  .print-payroll-sheet .payroll-title-block .id-str {
    font-size: 11px;
    font-weight: 650;
    font-family: monospace;
    margin-top: 4px;
    color: #4b5563;
  }
  
  .print-payroll-sheet .divider {
    border-top: 2px solid #e5e7eb;
    margin: 20px 0;
  }
  
  .print-payroll-sheet .details-grid {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
  }
  
  .print-payroll-sheet .details-grid .recipient {
    width: 50%;
  }
  
  .print-payroll-sheet .details-grid .meta-info {
    text-align: right;
  }
  
  .print-payroll-sheet .label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #6b7280;
    display: block;
    margin-bottom: 4px;
  }
  
  .print-payroll-sheet .value {
    font-size: 13px;
    font-weight: 600;
  }
  
  .print-payroll-sheet .value.name {
    font-size: 16px;
    font-weight: 800;
  }
  
  .print-payroll-sheet .meta-row {
    margin-bottom: 6px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
  }
  
  .print-payroll-sheet .status-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
  }
  
  .print-payroll-sheet .status-badge.Paid {
    background-color: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
  }
  
  .print-payroll-sheet .status-badge.Draft {
    background-color: #fffbeb;
    color: #92400e;
    border-color: #fef3c7;
  }
  
  .print-payroll-sheet .status-badge.Processing {
    background-color: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
  }
  
  .print-payroll-sheet .status-badge.Void {
    background-color: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
  }
  
  .print-payroll-sheet .items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
  }
  
  .print-payroll-sheet .items-table th {
    border-bottom: 2px solid #e5e7eb;
    padding: 10px 0;
    font-size: 11px;
    text-transform: uppercase;
    color: #6b7280;
    text-align: left;
  }
  
  .print-payroll-sheet .items-table th.center {
    text-align: center;
  }
  
  .print-payroll-sheet .items-table th.right {
    text-align: right;
  }
  
  .print-payroll-sheet .items-table td {
    border-bottom: 1px solid #f3f4f6;
    padding: 12px 0;
    font-size: 13px;
  }
  
  .print-payroll-sheet .items-table td.center {
    text-align: center;
  }
  
  .print-payroll-sheet .items-table td.right {
    text-align: right;
  }
  
  .print-payroll-sheet .items-table .item-title {
    font-weight: 700;
  }
  
  .print-payroll-sheet .items-table .item-sub {
    font-size: 11px;
    color: #6b7280;
    margin-top: 2px;
  }
  
  .print-payroll-sheet .totals-section {
    display: flex;
    justify-content: flex-end;
  }
  
  .print-payroll-sheet .total-row {
    display: flex;
    justify-content: space-between;
    width: 250px;
    font-size: 15px;
    font-weight: 800;
    border-top: 2px solid #e5e7eb;
    padding-top: 10px;
  }
  
  .print-payroll-sheet .footer-msg {
    margin-top: 60px;
    text-align: center;
    font-size: 11px;
    color: #9ca3af;
  }
}
</style>
