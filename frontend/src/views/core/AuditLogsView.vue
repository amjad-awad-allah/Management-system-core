<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
          {{ $t('settings.auditLogs') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ activeTab === 'system' ? $t('settings.systemLogsDesc') : $t('settings.auditLogsDesc') }}
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-3">
        <button
          @click="refreshActiveTab"
          :disabled="isLoading"
          class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-800 px-3.5 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-all cursor-pointer disabled:opacity-50"
        >
          <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
          {{ $t('settings.refresh') }}
        </button>

        <template v-if="activeTab === 'system'">
          <!-- Export Dropdown -->
          <div class="relative">
            <button
              @click="showExportMenu = !showExportMenu"
              :disabled="systemStore.isExporting"
              class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-all cursor-pointer disabled:opacity-50"
            >
              <ArrowDownTrayIcon class="w-4 h-4" />
              {{ systemStore.isExporting ? $t('settings.exportLogs') + '...' : $t('settings.exportLogs') }}
              <ChevronDownIcon class="w-3.5 h-3.5" />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="showExportMenu"
              v-click-outside="() => (showExportMenu = false)"
              class="absolute right-0 z-30 mt-2 w-56 origin-top-right rounded-xl bg-white dark:bg-gray-800 p-1.5 shadow-lg ring-1 ring-black/5 dark:ring-white/10 focus:outline-none"
            >
              <button
                @click="triggerExport('txt')"
                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-purple-50 dark:hover:bg-purple-900/30 hover:text-purple-600 dark:hover:text-purple-300 transition-colors cursor-pointer text-left"
              >
                <DocumentTextIcon class="w-4 h-4 text-gray-400" />
                {{ $t('settings.exportTxt') }}
              </button>
              <button
                @click="triggerExport('json')"
                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-purple-50 dark:hover:bg-purple-900/30 hover:text-purple-600 dark:hover:text-purple-300 transition-colors cursor-pointer text-left"
              >
                <CodeBracketIcon class="w-4 h-4 text-gray-400" />
                {{ $t('settings.exportJson') }}
              </button>
            </div>
          </div>

          <!-- Clear Logs Button -->
          <button
            @click="showClearModal = true"
            class="inline-flex items-center gap-2 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800/50 px-3.5 py-2 text-sm font-medium hover:bg-red-100 dark:hover:bg-red-900/40 transition-all cursor-pointer"
          >
            <TrashIcon class="w-4 h-4" />
            {{ $t('settings.clearLogs') }}
          </button>
        </template>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-800">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button
          @click="activeTab = 'system'"
          :class="[
            activeTab === 'system'
              ? 'border-purple-500 text-purple-600 dark:text-purple-400'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-700 hover:text-gray-700 dark:hover:text-gray-300',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold flex items-center gap-2 cursor-pointer transition-colors'
          ]"
        >
          <ExclamationTriangleIcon class="w-4 h-4" />
          {{ $t('settings.systemLogsTab') }}
          <span
            v-if="systemStore.statistics.total_errors_24h > 0"
            class="ml-1.5 rounded-full bg-red-100 dark:bg-red-900/40 px-2 py-0.5 text-xs font-semibold text-red-600 dark:text-red-400"
          >
            {{ systemStore.statistics.total_errors_24h }}
          </span>
        </button>

        <button
          @click="activeTab = 'audit'"
          :class="[
            activeTab === 'audit'
              ? 'border-purple-500 text-purple-600 dark:text-purple-400'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-700 hover:text-gray-700 dark:hover:text-gray-300',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold flex items-center gap-2 cursor-pointer transition-colors'
          ]"
        >
          <ShieldCheckIcon class="w-4 h-4" />
          {{ $t('settings.auditLogsTab') }}
        </button>
      </nav>
    </div>

    <!-- TAB 1: System & Error Logs -->
    <div v-if="activeTab === 'system'" class="space-y-6">
      <!-- 24-Hour Statistics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Errors Card -->
        <div class="bg-white/70 dark:bg-gray-800/60 backdrop-blur-xl rounded-2xl p-5 border border-red-200/50 dark:border-red-900/30 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              {{ $t('settings.totalErrors') }} ({{ $t('settings.last24Hours') }})
            </p>
            <div class="flex items-baseline gap-2 mt-1">
              <h3 class="text-2xl font-bold text-red-600 dark:text-red-400">
                {{ systemStore.statistics.total_errors_24h }}
              </h3>
              <span v-if="systemStore.statistics.critical_24h > 0" class="text-xs font-medium text-red-500">
                ({{ systemStore.statistics.critical_24h }} {{ $t('settings.criticalErrors').toLowerCase() }})
              </span>
            </div>
          </div>
          <div class="p-3 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl">
            <XCircleIcon class="w-6 h-6" />
          </div>
        </div>

        <!-- Warnings Card -->
        <div class="bg-white/70 dark:bg-gray-800/60 backdrop-blur-xl rounded-2xl p-5 border border-yellow-200/50 dark:border-yellow-900/30 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              {{ $t('settings.warnings') }} ({{ $t('settings.last24Hours') }})
            </p>
            <h3 class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
              {{ systemStore.statistics.warnings_24h }}
            </h3>
          </div>
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 rounded-xl">
            <ExclamationTriangleIcon class="w-6 h-6" />
          </div>
        </div>

        <!-- Info Events Card -->
        <div class="bg-white/70 dark:bg-gray-800/60 backdrop-blur-xl rounded-2xl p-5 border border-blue-200/50 dark:border-blue-900/30 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              {{ $t('settings.infoEvents') }} ({{ $t('settings.last24Hours') }})
            </p>
            <h3 class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
              {{ systemStore.statistics.info_24h }}
            </h3>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl">
            <InformationCircleIcon class="w-6 h-6" />
          </div>
        </div>

        <!-- Total Storage Card -->
        <div class="bg-white/70 dark:bg-gray-800/60 backdrop-blur-xl rounded-2xl p-5 border border-gray-200 dark:border-gray-800 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              {{ $t('settings.logStorage') }}
            </p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
              {{ systemStore.statistics.storage_formatted }}
            </h3>
          </div>
          <div class="p-3 bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 rounded-xl">
            <ServerIcon class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="bg-white/60 dark:bg-gray-800/50 backdrop-blur-xl p-4 rounded-2xl border border-gray-200/70 dark:border-gray-800 flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <!-- Timeframe Selector -->
          <div class="w-full sm:w-auto">
            <select
              v-model="systemStore.period"
              @change="systemStore.fetchLogs(1)"
              class="w-full sm:w-auto rounded-xl border-0 py-2 pl-3 pr-8 text-sm font-medium text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600"
            >
              <option value="24h">{{ $t('settings.periodOptions.24h') }}</option>
              <option value="7d">{{ $t('settings.periodOptions.7d') }}</option>
              <option value="30d">{{ $t('settings.periodOptions.30d') }}</option>
              <option value="all">{{ $t('settings.periodOptions.all') }}</option>
            </select>
          </div>

          <!-- Severity Level Selector -->
          <div class="w-full sm:w-auto">
            <select
              v-model="systemStore.level"
              @change="systemStore.fetchLogs(1)"
              class="w-full sm:w-auto rounded-xl border-0 py-2 pl-3 pr-8 text-sm font-medium text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600"
            >
              <option value="all">{{ $t('settings.levelOptions.all') }}</option>
              <option value="error">{{ $t('settings.levelOptions.error') }}</option>
              <option value="critical">{{ $t('settings.levelOptions.critical') }}</option>
              <option value="warning">{{ $t('settings.levelOptions.warning') }}</option>
              <option value="info">{{ $t('settings.levelOptions.info') }}</option>
              <option value="debug">{{ $t('settings.levelOptions.debug') }}</option>
            </select>
          </div>

          <!-- Search Input -->
          <div class="relative flex-1 min-w-[240px]">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              v-model="systemStore.search"
              @keyup.enter="systemStore.fetchLogs(1)"
              :placeholder="$t('settings.searchLogsPlaceholder')"
              class="w-full rounded-xl border-0 py-2 pl-9 pr-4 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-900 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-purple-600 placeholder:text-gray-400"
            />
          </div>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400 self-center">
          {{ systemStore.meta.total }} {{ systemStore.meta.total === 1 ? 'Eintrag' : 'Einträge' }}
        </div>
      </div>

      <!-- System Logs Table -->
      <div class="glass-panel rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm">
        <div v-if="systemStore.isLoading" class="p-12 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        </div>

        <div v-else-if="systemStore.logs.length === 0" class="p-12 text-center">
          <CheckCircleIcon class="w-12 h-12 text-green-500 mx-auto mb-3" />
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
            {{ $t('settings.noLogsFound') }}
          </h3>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Keine Fehler im gewählten Zeitraum registriert.
          </p>
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-800">
          <div
            v-for="log in systemStore.logs"
            :key="log.id"
            class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-800/40"
          >
            <!-- Log Entry Summary Row -->
            <div
              @click="toggleExpand(log.id)"
              class="p-4 sm:px-6 flex flex-col md:flex-row md:items-center justify-between gap-3 cursor-pointer select-none"
            >
              <div class="flex items-start gap-3 flex-1 min-w-0">
                <!-- Level Badge -->
                <span
                  :class="[
                    ['CRITICAL', 'ALERT', 'EMERGENCY'].includes(log.level)
                      ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 ring-rose-600/20 font-bold'
                      : log.level === 'ERROR'
                      ? 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-400 ring-red-600/20'
                      : log.level === 'WARNING'
                      ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 ring-amber-600/20'
                      : log.level === 'DEBUG'
                      ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-gray-600/20'
                      : 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 ring-blue-600/20',
                    'inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset shrink-0'
                  ]"
                >
                  {{ log.level }}
                </span>

                <!-- Message & Metadata -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate max-w-2xl">
                      {{ log.message }}
                    </p>
                    <span
                      v-if="log.exception_class"
                      class="text-xs px-2 py-0.5 rounded bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 font-mono"
                    >
                      {{ log.exception_class.split('\\').pop() }}
                    </span>
                  </div>

                  <!-- Details subtext: Route / User / Request ID -->
                  <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <span v-if="log.method && log.url" class="font-mono text-gray-700 dark:text-gray-300 font-medium">
                      {{ log.method }} {{ truncateUrl(log.url) }}
                    </span>
                    <span v-if="log.user_name">
                      User: {{ log.user_name }}
                    </span>
                    <span v-if="log.ip">
                      IP: {{ log.ip }}
                    </span>
                    <span v-if="log.request_id" class="font-mono text-gray-400">
                      Req: {{ log.request_id.substring(0, 10) }}...
                    </span>
                  </div>
                </div>
              </div>

              <!-- Timestamp & Accordion Toggle -->
              <div class="flex items-center justify-between md:justify-end gap-3 shrink-0">
                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                  {{ formatDate(log.timestamp, 'long') }}
                </span>
                <ChevronRightIcon
                  class="w-4 h-4 text-gray-400 transition-transform duration-200"
                  :class="{ 'rotate-90': expandedLogId === log.id }"
                />
              </div>
            </div>

            <!-- Expanded Accordion Details -->
            <div
              v-if="expandedLogId === log.id"
              class="px-4 pb-4 sm:px-6 bg-gray-50/50 dark:bg-gray-900/60 border-t border-gray-100 dark:border-gray-800 space-y-4 pt-3"
            >
              <!-- Request / Trace Information Row -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs bg-white dark:bg-gray-800/80 p-3 rounded-xl border border-gray-200/60 dark:border-gray-700/60">
                <div>
                  <span class="text-gray-400 block">Request-ID:</span>
                  <span class="font-mono font-medium text-gray-800 dark:text-gray-200 select-all">
                    {{ log.request_id || 'N/A' }}
                  </span>
                </div>
                <div>
                  <span class="text-gray-400 block">Correlation-ID:</span>
                  <span class="font-mono font-medium text-gray-800 dark:text-gray-200 select-all">
                    {{ log.correlation_id || log.request_id || 'N/A' }}
                  </span>
                </div>
                <div v-if="log.file">
                  <span class="text-gray-400 block">Datei & Zeile:</span>
                  <span class="font-mono text-gray-800 dark:text-gray-200">
                    {{ log.file }}:{{ log.line }}
                  </span>
                </div>
                <div v-if="log.channel">
                  <span class="text-gray-400 block">Channel / Env:</span>
                  <span class="text-gray-800 dark:text-gray-200">
                    {{ log.channel }} ({{ log.environment || 'local' }})
                  </span>
                </div>
              </div>

              <!-- Stack Trace Box -->
              <div v-if="log.trace" class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Stack Trace
                  </span>
                  <button
                    @click="copyText(log.trace)"
                    class="inline-flex items-center gap-1.5 text-xs text-purple-600 hover:text-purple-700 dark:text-purple-400 font-medium cursor-pointer"
                  >
                    <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                    {{ copiedId === log.id ? $t('settings.copied') : $t('settings.copyStackTrace') }}
                  </button>
                </div>
                <pre class="p-3 bg-gray-900 text-gray-100 rounded-xl text-xs font-mono overflow-x-auto max-h-72 leading-relaxed border border-gray-800 select-all">{{ log.trace }}</pre>
              </div>

              <!-- Context JSON Inspector -->
              <div v-if="log.context && Object.keys(log.context).length > 0" class="space-y-2">
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                  Context / Payload
                </span>
                <pre class="p-3 bg-gray-900 text-purple-300 rounded-xl text-xs font-mono overflow-x-auto max-h-48 leading-relaxed border border-gray-800 select-all">{{ JSON.stringify(log.context, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="systemStore.meta.last_page > 1"
          class="p-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/30"
        >
          <button
            @click="systemStore.fetchLogs(systemStore.meta.current_page - 1)"
            :disabled="systemStore.meta.current_page <= 1"
            class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-xs font-medium disabled:opacity-40 cursor-pointer text-gray-700 dark:text-gray-300"
          >
            Zurück
          </button>
          <span class="text-xs text-gray-500">
            Seite {{ systemStore.meta.current_page }} von {{ systemStore.meta.last_page }}
          </span>
          <button
            @click="systemStore.fetchLogs(systemStore.meta.current_page + 1)"
            :disabled="systemStore.meta.current_page >= systemStore.meta.last_page"
            class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 text-xs font-medium disabled:opacity-40 cursor-pointer text-gray-700 dark:text-gray-300"
          >
            Weiter
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 2: Activity & Database Audit Trail -->
    <div v-else class="space-y-6">
      <div class="glass-panel rounded-2xl overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm">
        <div v-if="auditStore.isLoading" class="p-12 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
          <thead class="bg-gray-50/50 dark:bg-gray-800/50">
            <tr>
              <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100 sm:pl-6">
                {{ $t('common.date') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('settings.userName') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('settings.actionType') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('settings.entity') }}
              </th>
              <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $t('settings.ipAddress') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-transparent">
            <tr
              v-for="log in auditStore.logs"
              :key="log.id"
              class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
            >
              <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 dark:text-gray-400 sm:pl-6">
                {{ formatDate(log.created_at, 'long') }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ log.user_name || 'System' }}
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm">
                <span
                  :class="[
                    log.event === 'created'
                      ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400'
                      : log.event === 'updated'
                      ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400'
                      : log.event === 'cleared'
                      ? 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-400'
                      : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400',
                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
                  ]"
                >
                  {{ log.event.toUpperCase() }}
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="font-medium text-gray-900 dark:text-gray-100">
                  {{ log.auditable_type ? log.auditable_type.split('\\').pop() : '-' }}
                </div>
                <div v-if="log.auditable_id" class="text-xs text-gray-400">
                  ID: {{ log.auditable_id.substring(0, 8) }}
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                {{ log.ip_address || 'N/A' }}
              </td>
            </tr>
            <tr v-if="auditStore.logs.length === 0">
              <td colspan="5" class="py-8 text-center text-sm text-gray-500">
                {{ $t('common.noData') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Clear Logs Confirmation Modal -->
    <div
      v-if="showClearModal"
      class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-700 space-y-4">
        <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
          <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
            <TrashIcon class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
              {{ $t('settings.clearLogsTitle') }}
            </h3>
            <p class="text-xs text-gray-500">
              Super Admin Aktion
            </p>
          </div>
        </div>

        <p class="text-sm text-gray-600 dark:text-gray-300">
          {{ $t('settings.clearLogsConfirm') }}
        </p>

        <div class="space-y-2">
          <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
            {{ $t('settings.clearLogsScope') }}:
          </label>
          <select
            v-model="clearScope"
            class="w-full rounded-xl border-0 py-2 pl-3 pr-8 text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 ring-1 ring-inset ring-gray-300 dark:ring-gray-700"
          >
            <option value="all">Alle Protokolle leeren</option>
            <option value="older_24h">Nur Logs älter als 24 Stunden</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-3">
          <button
            type="button"
            @click="showClearModal = false"
            class="px-4 py-2 rounded-xl text-sm font-medium border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="button"
            @click="executeClearLogs"
            :disabled="systemStore.isClearing"
            class="px-4 py-2 rounded-xl text-sm font-semibold bg-red-600 hover:bg-red-500 text-white shadow-sm transition-colors cursor-pointer disabled:opacity-50"
          >
            {{ systemStore.isClearing ? $t('common.loading') : $t('settings.clearLogs') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSystemLogStore } from '@/stores/systemLogStore'
import { useAuditStore } from '@/stores/auditStore'
import { useFormatters } from '@/composables/useFormatters'
import {
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  DocumentTextIcon,
  CodeBracketIcon,
  MagnifyingGlassIcon,
  CheckCircleIcon,
  XCircleIcon,
  InformationCircleIcon,
  ServerIcon,
  ClipboardDocumentIcon
} from '@heroicons/vue/24/outline'

const activeTab = ref<'system' | 'audit'>('system')
const systemStore = useSystemLogStore()
const auditStore = useAuditStore()
const { formatDate } = useFormatters()

const showExportMenu = ref(false)
const showClearModal = ref(false)
const clearScope = ref('all')
const expandedLogId = ref<string | null>(null)
const copiedId = ref<string | null>(null)

const vClickOutside = {
  mounted(el: any, binding: any) {
    el._clickOutside = (event: Event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event)
      }
    }
    document.addEventListener('click', el._clickOutside)
  },
  unmounted(el: any) {
    document.removeEventListener('click', el._clickOutside)
  }
}

const isLoading = ref(false)

onMounted(() => {
  systemStore.fetchLogs(1)
  auditStore.fetchLogs()
})

function refreshActiveTab() {
  if (activeTab.value === 'system') {
    systemStore.fetchLogs(systemStore.meta.current_page)
  } else {
    auditStore.fetchLogs()
  }
}

function toggleExpand(logId: string) {
  expandedLogId.value = expandedLogId.value === logId ? null : logId
}

function triggerExport(format: 'txt' | 'json') {
  showExportMenu.value = false
  systemStore.exportLogs(format)
}

async function executeClearLogs() {
  const success = await systemStore.clearLogs(clearScope.value)
  if (success) {
    showClearModal.value = false
    auditStore.fetchLogs() // refresh audit trail to see the clear record
  }
}

function copyText(text: string) {
  navigator.clipboard.writeText(text)
  copiedId.value = expandedLogId.value
  setTimeout(() => {
    copiedId.value = null
  }, 2000)
}

function truncateUrl(url: string): string {
  try {
    const parsed = new URL(url)
    return parsed.pathname + parsed.search
  } catch {
    return url.length > 50 ? url.substring(0, 50) + '...' : url
  }
}
</script>
