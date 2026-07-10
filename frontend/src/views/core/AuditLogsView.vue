<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Audit Logs</h1>
    </div>

    <!-- Logs Table -->
    <div class="glass-panel rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800">
      <div v-if="store.isLoading" class="p-12 flex justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      </div>
      
      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead class="bg-gray-50/50 dark:bg-gray-800/50">
          <tr>
            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">Date</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">User</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Event</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Resource</th>
            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">IP Address</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
          <tr v-for="log in store.logs" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 dark:text-gray-400 sm:pl-6">
              {{ new Date(log.created_at).toLocaleString() }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
              {{ log.user_name || 'System' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span :class="[
                log.event === 'created' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' :
                log.event === 'updated' ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400' :
                'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400',
                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
              ]">
                {{ log.event.toUpperCase() }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ log.auditable_type.split('\\').pop() }}</div>
              <div class="text-xs">ID: {{ log.auditable_id.substring(0, 8) }}</div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
              {{ log.ip_address || 'N/A' }}
            </td>
          </tr>
          <tr v-if="store.logs.length === 0">
            <td colspan="5" class="py-8 text-center text-sm text-gray-500">No audit logs found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuditStore } from '@/stores/auditStore'

const store = useAuditStore()

onMounted(() => {
  store.fetchLogs()
})
</script>
