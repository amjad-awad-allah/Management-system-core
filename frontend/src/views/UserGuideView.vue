<template>
  <div class="space-y-8 max-w-7xl mx-auto pb-20 px-2 sm:px-4">
    <!-- Header Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-purple-700 via-indigo-700 to-blue-600 p-8 sm:p-12 text-white shadow-2xl">
      <!-- Glow Gradients -->
      <div class="absolute -right-16 -top-16 h-80 w-80 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-20 right-1/3 h-64 w-64 rounded-full bg-purple-400/20 blur-2xl pointer-events-none"></div>
      
      <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
        <div class="max-w-2xl">
          <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold backdrop-blur-md mb-4 border border-white/20">
            <SparklesIcon class="w-4 h-4 text-yellow-300 animate-pulse" />
            <span>{{ $t('guide.subtitle') }}</span>
          </div>
          <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-white leading-tight">
            {{ $t('guide.title') }}
          </h1>
          <p class="mt-3 text-sm sm:text-base text-purple-100/90 leading-relaxed">
            Ausführliches Betriebshandbuch, Arbeitsanweisungen, Rechtsgrundlagen (BuT/Jobcenter § 28 SGB II) und Praxiswissen für Schulleitung, Verwaltung und Dozenten.
          </p>

          <!-- Interactive Search Bar -->
          <div class="mt-6 relative max-w-xl z-10">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
              <MagnifyingGlassIcon class="h-5 w-5" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="$t('guide.searchPlaceholder')"
              class="w-full rounded-2xl bg-white/95 dark:bg-gray-800/95 py-3.5 pr-16 text-sm text-gray-900 dark:text-white placeholder-gray-500 shadow-xl focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
              style="padding-left: 3rem !important; padding-inline-start: 3rem !important;"
            />
            <span v-if="searchQuery" @click="searchQuery = ''" class="absolute right-4 top-3.5 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
              {{ $t('common.cancel') }}
            </span>
          </div>

          <!-- Actions: Tour & Expand -->
          <div class="mt-4 flex flex-wrap items-center gap-3">
            <button
              @click="onboardingStore.restartTour()"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white/20 hover:bg-white/30 active:scale-95 text-white text-xs font-bold backdrop-blur-md border border-white/25 shadow-lg shadow-black/10 transition-all cursor-pointer"
            >
              <SparklesIcon class="w-4 h-4 text-yellow-300 animate-spin" style="animation-duration: 4s;" />
              <span>{{ $t('guide.launchTourButton') }}</span>
            </button>
            <button
              @click="toggleAllDetails"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-black/20 hover:bg-black/30 active:scale-95 text-white text-xs font-medium backdrop-blur-md border border-white/15 transition-all cursor-pointer"
            >
              <BookOpenIcon class="w-4 h-4" />
              <span>{{ showAllDetails ? 'Alle Details einklappen' : 'Alle Details ausklappen' }}</span>
            </button>
          </div>
        </div>

        <!-- Quick Stats & System Status Card -->
        <div class="flex flex-col gap-3 rounded-2xl bg-black/25 backdrop-blur-xl p-5 border border-white/15 text-xs shadow-inner min-w-[260px]">
          <div class="font-bold text-white flex items-center justify-between border-b border-white/10 pb-2">
            <span class="flex items-center gap-2">
              <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
              </span>
              {{ $t('guide.systemStatus') }}
            </span>
            <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full font-mono">{{ $t('guide.operational100') }}</span>
          </div>
          <div class="space-y-2 text-purple-100/80 pt-1">
            <div class="flex items-center justify-between">
              <span>Bundesland:</span>
              <span class="font-semibold text-emerald-300 font-mono">{{ settingsStore.centerBundeslandName }} ({{ settingsStore.centerBundesland }})</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Zeitzone & DST:</span>
              <span class="font-semibold text-emerald-300 font-mono">Europe/Berlin</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Automatische Erinnerungen:</span>
              <span class="font-semibold text-white">24h & 2h vor Beginn</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Honorarabrechnung:</span>
              <span class="font-semibold text-white">SHA-256 Versiegelt</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Daily Routines Quick Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="routine in localizedDailyRoutines"
        :key="routine.title"
        @click="selectRoutine(routine)"
        class="group cursor-pointer rounded-2xl bg-white dark:bg-gray-800/80 p-4 border border-gray-200 dark:border-gray-700/70 hover:border-purple-500 hover:shadow-lg transition-all"
      >
        <div class="flex items-center gap-3">
          <div :class="['p-2.5 rounded-xl text-white shadow-sm', routine.bgGradient]">
            <component :is="routine.icon" class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">{{ routine.timing }}</span>
            <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">{{ routine.title }}</h4>
          </div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed">
          {{ routine.summary }}
        </p>
      </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none border-b border-gray-200 dark:border-gray-800">
      <button
        v-for="cat in localizedCategories"
        :key="cat.id"
        @click="activeCategory = cat.id"
        :class="[
          activeCategory === cat.id
            ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20'
            : 'bg-white dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60 border border-gray-200/80 dark:border-gray-700/80',
          'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all cursor-pointer shrink-0'
        ]"
      >
        <component :is="cat.icon" class="w-4 h-4" />
        <span>{{ cat.title }}</span>
        <span
          :class="[
            activeCategory === cat.id ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
            'px-2 py-0.5 rounded-full text-[10px]'
          ]"
        >
          {{ getFilteredCount(cat.id) }}
        </span>
      </button>
    </div>

    <!-- Detailed Guide Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="guide in filteredGuides"
        :key="guide.id"
        class="rounded-3xl border border-gray-200/80 dark:border-gray-700/80 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between"
      >
        <div>
          <!-- Header: Icon & Category Badge -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 shadow-inner">
              <component :is="guide.icon" class="h-6 w-6" />
            </div>
            <span class="inline-flex items-center rounded-full bg-purple-100 dark:bg-purple-900/50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:text-purple-300">
              {{ guide.categoryName }}
            </span>
          </div>

          <!-- Title & Operational Goal -->
          <h3 class="text-base font-bold text-gray-900 dark:text-white">
            {{ guide.title }}
          </h3>
          <p class="mt-1 text-xs font-semibold text-purple-600 dark:text-purple-400">
            🎯 {{ guide.goal }}
          </p>

          <!-- Prerequisites (Voraussetzungen) -->
          <div v-if="guide.prerequisites && guide.prerequisites.length > 0" class="mt-3 bg-gray-50 dark:bg-gray-900/40 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800">
            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block mb-1">
              Voraussetzungen:
            </span>
            <ul class="space-y-1">
              <li v-for="(req, rIdx) in guide.prerequisites" :key="rIdx" class="flex items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                <span class="text-emerald-500 font-bold">✓</span>
                <span>{{ req }}</span>
              </li>
            </ul>
          </div>

          <!-- Step-by-Step Instructions -->
          <div class="mt-4 space-y-2">
            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider block">
              Arbeitsanweisung:
            </span>
            <div
              v-for="(step, idx) in guide.steps"
              :key="idx"
              class="flex items-start gap-2.5 text-xs text-gray-700 dark:text-gray-300 leading-relaxed"
            >
              <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/50 text-[10px] font-bold text-purple-700 dark:text-purple-300 mt-0.5">
                {{ idx + 1 }}
              </span>
              <span>{{ step }}</span>
            </div>
          </div>

          <!-- Expandable Deep-Dive Details -->
          <div v-if="showAllDetails || expandedCardIds.has(guide.id)" class="mt-4 space-y-3 pt-3 border-t border-gray-100 dark:border-gray-800">
            <!-- Regulatory / Compliance Box -->
            <div v-if="guide.compliance" class="rounded-xl bg-blue-50/80 dark:bg-blue-950/30 p-3 border border-blue-200/60 dark:border-blue-900/40">
              <div class="flex items-start gap-2">
                <ScaleIcon class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                <div>
                  <span class="text-[10px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider block">Rechts- & BuT-Richtlinie:</span>
                  <p class="text-[11px] text-blue-900 dark:text-blue-200 leading-relaxed mt-0.5">
                    {{ guide.compliance }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Edge Cases & Troubleshooting -->
            <div v-if="guide.edgeCases && guide.edgeCases.length > 0" class="rounded-xl bg-gray-50 dark:bg-gray-900/50 p-3 border border-gray-200/60 dark:border-gray-700/60">
              <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider block mb-1">
                🛠️ Ausnahmefälle & Praxisregeln:
              </span>
              <ul class="space-y-1.5">
                <li v-for="(edge, eIdx) in guide.edgeCases" :key="eIdx" class="text-[11px] text-gray-600 dark:text-gray-300 leading-relaxed flex items-start gap-1.5">
                  <span class="text-purple-500 font-bold">•</span>
                  <span>{{ edge }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Toggle Details Accordion Button -->
          <div v-if="!showAllDetails" class="mt-3">
            <button
              @click="toggleCardDetails(guide.id)"
              class="text-[11px] font-bold text-purple-600 dark:text-purple-400 hover:text-purple-700 flex items-center gap-1 cursor-pointer"
            >
              <span>{{ expandedCardIds.has(guide.id) ? 'Weniger Details' : '+ Recht & Ausnahmefälle anzeigen' }}</span>
            </button>
          </div>

          <!-- Pro Tip Box -->
          <div v-if="guide.tip" class="mt-4 rounded-xl bg-amber-50/80 dark:bg-amber-900/20 p-3 border border-amber-200/60 dark:border-amber-700/40">
            <div class="flex items-start gap-2">
              <LightBulbIcon class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
              <p class="text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed font-medium">
                {{ guide.tip }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer Direct Route Button -->
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
          <span class="text-[11px] font-medium text-gray-400">
            {{ guide.badge }}
          </span>
          <router-link
            v-if="guide.route"
            :to="guide.route"
            class="inline-flex items-center gap-1 text-xs font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 transition-colors"
          >
            <span>{{ $t('students.viewDetails') }}</span>
            <span>&rarr;</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- FAQ Section: Deep Educational ERP Q&A -->
    <div class="rounded-3xl bg-gradient-to-b from-gray-50 to-white dark:from-gray-900/60 dark:to-gray-800/40 p-8 sm:p-10 border border-gray-200 dark:border-gray-700 shadow-sm mt-12">
      <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
          <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/40 px-3 py-1 rounded-full">
            Praxiswissen & Rechtliches
          </span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mt-3">
            {{ $t('guide.faqTitle') }}
          </h2>
          <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-xl mx-auto leading-relaxed">
            Praxisrelevante Antworten zu Jobcenter-BuT-Abrechnungen, Stornierungsregeln, DSGVO-Aushängen und Honorarversiegelungen.
          </p>

          <!-- Expand All / Collapse All FAQs -->
          <div class="mt-4 flex justify-center gap-3">
            <button
              @click="expandAllFaqs"
              class="text-xs font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400 cursor-pointer"
            >
              Alle Antworten öffnen
            </button>
            <span class="text-gray-300 dark:text-gray-700">•</span>
            <button
              @click="collapseAllFaqs"
              class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 cursor-pointer"
            >
              Alle schließen
            </button>
          </div>
        </div>

        <div class="space-y-3">
          <div
            v-for="(faq, idx) in activeFaqs"
            :key="idx"
            class="rounded-2xl border border-gray-200/80 dark:border-gray-700/80 bg-white dark:bg-gray-800 overflow-hidden transition-all shadow-xs"
          >
            <button
              @click="toggleFaq(idx)"
              class="w-full px-6 py-4 text-left flex items-center justify-between gap-4 cursor-pointer focus:outline-none select-none hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
            >
              <div class="flex items-center gap-3 flex-1">
                <span v-if="faq.category" class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 px-2 py-0.5 rounded-md shrink-0">
                  {{ faq.category }}
                </span>
                <span class="text-sm font-bold text-gray-900 dark:text-white leading-snug">{{ faq.q }}</span>
              </div>
              <ChevronDownIcon
                class="w-5 h-5 text-gray-400 transition-transform duration-200 shrink-0"
                :class="{ 'rotate-180 text-purple-600': isFaqOpen(idx) }"
              />
            </button>
            <div
              v-show="isFaqOpen(idx)"
              class="px-6 pb-5 text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed border-t border-gray-100 dark:border-gray-700/60 pt-3.5 bg-gray-50/30 dark:bg-gray-900/20"
            >
              {{ faq.a }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useOnboardingStore } from '@/stores/onboardingStore'
import { useSettingsStore } from '@/stores/settingsStore'
import { localizedGuides, localizedFaqs } from '@/i18n/data/guideContent'
import {
  SparklesIcon,
  MagnifyingGlassIcon,
  CalendarDaysIcon,
  BellAlertIcon,
  CurrencyEuroIcon,
  CurrencyDollarIcon,
  UserGroupIcon,
  AcademicCapIcon,
  ChevronDownIcon,
  PrinterIcon,
  CheckCircleIcon,
  LightBulbIcon,
  SunIcon,
  MoonIcon,
  ShieldCheckIcon,
  ScaleIcon,
  BookOpenIcon,
} from '@heroicons/vue/24/outline'

const { t, locale } = useI18n()
const onboardingStore = useOnboardingStore()
const settingsStore = useSettingsStore()
const searchQuery = ref('')
const activeCategory = ref('all')
const showAllDetails = ref(false)
const expandedCardIds = ref<Set<string>>(new Set())

const currentLocale = computed(() => (locale.value === 'en' ? 'en' : 'de'))

const localizedCategories = computed(() => [
  { id: 'all', title: t('guide.categories.all'), icon: SparklesIcon },
  { id: 'calendar', title: t('guide.categories.calendar'), icon: CalendarDaysIcon },
  { id: 'attendance', title: t('guide.categories.attendance'), icon: CheckCircleIcon },
  { id: 'reminders', title: t('guide.categories.reminders'), icon: BellAlertIcon },
  { id: 'payrolls', title: t('guide.categories.payrolls'), icon: CurrencyEuroIcon },
  { id: 'invoices', title: t('guide.categories.invoices'), icon: CurrencyDollarIcon },
  { id: 'students', title: t('guide.categories.students'), icon: UserGroupIcon },
  { id: 'teachers', title: t('guide.categories.teachers'), icon: AcademicCapIcon },
  { id: 'reports', title: t('guide.categories.reports'), icon: PrinterIcon },
  { id: 'admin', title: t('guide.categories.admin'), icon: ShieldCheckIcon },
])

const localizedDailyRoutines = computed(() => [
  {
    timing: t('guide.routines.morning.timing'),
    title: t('guide.routines.morning.title'),
    summary: t('guide.routines.morning.summary'),
    icon: SunIcon,
    bgGradient: 'bg-gradient-to-r from-amber-500 to-orange-500',
    categoryTarget: 'calendar'
  },
  {
    timing: t('guide.routines.midday.timing'),
    title: t('guide.routines.midday.title'),
    summary: t('guide.routines.midday.summary'),
    icon: CheckCircleIcon,
    bgGradient: 'bg-gradient-to-r from-emerald-500 to-teal-500',
    categoryTarget: 'attendance'
  },
  {
    timing: t('guide.routines.evening.timing'),
    title: t('guide.routines.evening.title'),
    summary: t('guide.routines.evening.summary'),
    icon: MoonIcon,
    bgGradient: 'bg-gradient-to-r from-indigo-500 to-purple-600',
    categoryTarget: 'reminders'
  },
  {
    timing: t('guide.routines.monthly.timing'),
    title: t('guide.routines.monthly.title'),
    summary: t('guide.routines.monthly.summary'),
    icon: CurrencyEuroIcon,
    bgGradient: 'bg-gradient-to-r from-purple-600 to-pink-600',
    categoryTarget: 'payrolls'
  },
])

function selectRoutine(routine: any) {
  activeCategory.value = routine.categoryTarget
}

function toggleCardDetails(guideId: string) {
  if (expandedCardIds.value.has(guideId)) {
    expandedCardIds.value.delete(guideId)
  } else {
    expandedCardIds.value.add(guideId)
  }
}

function toggleAllDetails() {
  showAllDetails.value = !showAllDetails.value
}

const currentGuides = computed(() => {
  return localizedGuides[currentLocale.value] || localizedGuides.de
})

const activeFaqs = computed(() => {
  return localizedFaqs[currentLocale.value] || localizedFaqs.de
})

const openFaqIndices = ref<Set<number>>(new Set([0])) // first FAQ open by default

function toggleFaq(index: number) {
  if (openFaqIndices.value.has(index)) {
    openFaqIndices.value.delete(index)
  } else {
    openFaqIndices.value.add(index)
  }
}

function isFaqOpen(index: number): boolean {
  return openFaqIndices.value.has(index)
}

function expandAllFaqs() {
  openFaqIndices.value = new Set(activeFaqs.value.map((_, i) => i))
}

function collapseAllFaqs() {
  openFaqIndices.value = new Set()
}

function getFilteredCount(catId: string): number {
  if (catId === 'all') return currentGuides.value.length
  return currentGuides.value.filter(g => g.categoryId === catId).length
}

const filteredGuides = computed(() => {
  return currentGuides.value.filter(g => {
    const matchesCat = activeCategory.value === 'all' || g.categoryId === activeCategory.value
    const q = searchQuery.value.toLowerCase().trim()
    if (!q) return matchesCat

    const matchesSearch = 
      g.title.toLowerCase().includes(q) ||
      g.goal.toLowerCase().includes(q) ||
      g.steps.some(s => s.toLowerCase().includes(q)) ||
      (g.compliance && g.compliance.toLowerCase().includes(q)) ||
      (g.edgeCases && g.edgeCases.some(e => e.toLowerCase().includes(q))) ||
      (g.prerequisites && g.prerequisites.some(p => p.toLowerCase().includes(q))) ||
      (g.tip && g.tip.toLowerCase().includes(q))

    return matchesCat && matchesSearch
  })
})
</script>
