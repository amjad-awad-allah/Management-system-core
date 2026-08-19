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
            <span>Complete Staff Operating Manual & Help Center</span>
          </div>
          <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-white leading-tight">
            Nachhilfe Center Playbook
          </h1>
          <p class="mt-3 text-sm sm:text-base text-purple-100/90 leading-relaxed">
            Your 100% exhaustive guide covering every single feature: lesson scheduling, attendance, BuT vouchers, invoices, teacher payrolls, audit logs, room setup, and live mobile access.
          </p>

          <!-- Interactive Search Bar -->
          <div class="mt-6 relative max-w-xl z-10">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search anything (e.g. invoice, payroll, attendance, voucher, login code, audit, conflicts)..."
              class="w-full rounded-2xl bg-white/95 dark:bg-gray-800/95 py-3.5 pl-12 pr-4 text-sm text-gray-900 dark:text-white placeholder-gray-500 shadow-xl focus:outline-none focus:ring-2 focus:ring-purple-400 transition"
            />
            <span v-if="searchQuery" @click="searchQuery = ''" class="absolute right-4 top-3 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
              Clear
            </span>
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
              System Services
            </span>
            <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full font-mono">100% Operational</span>
          </div>
          <div class="space-y-2 text-purple-100/80 pt-1">
            <div class="flex items-center justify-between">
              <span>Database Engine:</span>
              <span class="font-semibold text-emerald-300 font-mono">MySQL Port 3306</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Backend API Server:</span>
              <span class="font-semibold text-emerald-300 font-mono">Port 8001 (Active)</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Live WebSockets (Reverb):</span>
              <span class="font-semibold text-emerald-300 font-mono">Port 8081 (Active)</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Automated Reminders:</span>
              <span class="font-semibold text-white">Every 5 Min (24h & 2h)</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Daily Routines Quick Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="routine in dailyRoutines"
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

    <!-- Category Tabs Navigation -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
      <button
        v-for="cat in categories"
        :key="cat.id"
        @click="activeCategory = cat.id"
        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
        :class="activeCategory === cat.id
          ? 'bg-purple-600 text-white shadow-md shadow-purple-500/20 scale-[1.02]'
          : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
      >
        <component :is="cat.icon" class="w-4 h-4" />
        {{ cat.title }}
        <span
          class="px-2 py-0.5 rounded-full text-[10px]"
          :class="activeCategory === cat.id ? 'bg-purple-800 text-purple-100' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
        >
          {{ getFilteredCount(cat.id) }}
        </span>
      </button>
    </div>

    <!-- Interactive Guides Cards Grid (13 Comprehensive Modules) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="guide in filteredGuides"
        :key="guide.id"
        class="flex flex-col justify-between rounded-3xl bg-white dark:bg-gray-800/90 p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:border-purple-500/60 transition-all duration-300 backdrop-blur-sm"
      >
        <div>
          <!-- Header Badge & Icon -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
              <component :is="guide.icon" class="h-6 w-6" />
            </div>
            <span class="inline-flex items-center rounded-full bg-purple-100 dark:bg-purple-900/50 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:text-purple-300">
              {{ guide.categoryName }}
            </span>
          </div>

          <!-- Title & Goal -->
          <h3 class="text-base font-bold text-gray-900 dark:text-white">
            {{ guide.title }}
          </h3>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
            {{ guide.goal }}
          </p>

          <!-- Step-by-Step Instructions -->
          <div class="mt-4 space-y-2.5">
            <div
              v-for="(step, idx) in guide.steps"
              :key="idx"
              class="flex items-start gap-3 text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800"
            >
              <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-600 text-[11px] font-bold text-white mt-0.5">
                {{ idx + 1 }}
              </span>
              <span class="leading-relaxed">{{ step }}</span>
            </div>
          </div>

          <!-- Pro Tip Box -->
          <div v-if="guide.tip" class="mt-3.5 flex items-start gap-2 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 p-2.5 rounded-xl text-[11px] leading-relaxed">
            <LightBulbIcon class="w-4 h-4 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
            <span><strong>Pro Tip:</strong> {{ guide.tip }}</span>
          </div>
        </div>

        <!-- Card Footer -->
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <router-link
            v-if="guide.route"
            :to="guide.route"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-600 dark:text-purple-400 hover:text-purple-700 transition"
          >
            Open Page
            <ArrowRightIcon class="w-3.5 h-3.5" />
          </router-link>
          <span v-else class="text-[11px] text-gray-400 font-medium">Automatic background service</span>

          <span v-if="guide.badge" class="text-[10px] bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-md font-semibold">
            {{ guide.badge }}
          </span>
        </div>
      </div>
    </div>

    <!-- Lesson Status Badges & Visual Reference Card -->
    <div class="rounded-3xl bg-gradient-to-r from-gray-900 via-purple-950 to-indigo-950 p-6 sm:p-8 text-white shadow-xl">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2.5 bg-purple-500/20 rounded-xl text-purple-300">
          <TagIcon class="w-6 h-6" />
        </div>
        <div>
          <h2 class="text-lg sm:text-xl font-bold">Understanding Lesson Statuses at a Glance</h2>
          <p class="text-xs text-purple-200/70">What each color and badge means on your calendar and reports</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white/10 rounded-2xl p-3.5 backdrop-blur-sm border border-white/10">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-500/30 text-blue-200 border border-blue-400/40">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
            Scheduled
          </span>
          <p class="mt-2 text-[11px] text-purple-100/80 leading-relaxed">Booked for a future date. Automatic reminders will trigger before it starts.</p>
        </div>

        <div class="bg-white/10 rounded-2xl p-3.5 backdrop-blur-sm border border-white/10">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/30 text-emerald-200 border border-emerald-400/40">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
            In Progress
          </span>
          <p class="mt-2 text-[11px] text-purple-100/80 leading-relaxed">Currently taking place right now in Europe/Berlin local time.</p>
        </div>

        <div class="bg-white/10 rounded-2xl p-3.5 backdrop-blur-sm border border-white/10">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-500/30 text-purple-200 border border-purple-400/40">
            <CheckCircleIcon class="w-3.5 h-3.5" />
            Completed
          </span>
          <p class="mt-2 text-[11px] text-purple-100/80 leading-relaxed">Attendance was marked. Hours deducted from student and counted for teacher pay.</p>
        </div>

        <div class="bg-white/10 rounded-2xl p-3.5 backdrop-blur-sm border border-white/10">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/30 text-rose-200 border border-rose-400/40">
            <XCircleIcon class="w-3.5 h-3.5" />
            Cancelled (Excused)
          </span>
          <p class="mt-2 text-[11px] text-purple-100/80 leading-relaxed">Cancelled in advance. No hours deducted from student; no teacher fee charged.</p>
        </div>

        <div class="bg-white/10 rounded-2xl p-3.5 backdrop-blur-sm border border-white/10">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/30 text-amber-200 border border-amber-400/40">
            <ExclamationTriangleIcon class="w-3.5 h-3.5" />
            Late Cancel / No-Show
          </span>
          <p class="mt-2 text-[11px] text-purple-100/80 leading-relaxed">Cancelled last-minute under center policy. Hours deducted and teacher paid.</p>
        </div>
      </div>
    </div>

    <!-- Practical FAQs & Troubleshooting Matrix -->
    <div class="rounded-3xl bg-white dark:bg-gray-800 p-6 sm:p-10 shadow-sm border border-gray-200 dark:border-gray-700">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl text-indigo-600 dark:text-indigo-400">
          <QuestionMarkCircleIcon class="w-6 h-6" />
        </div>
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Everyday Questions & Solutions Matrix</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400">Clear solutions to common situations encountered by center administrators</p>
        </div>
      </div>

      <div class="space-y-3.5">
        <div
          v-for="(faq, i) in faqs"
          :key="i"
          class="border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden"
        >
          <button
            @click="faq.open = !faq.open"
            class="w-full flex items-center justify-between p-4 text-left font-bold text-sm text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer"
          >
            <span class="flex items-center gap-2.5">
              <span class="w-2 h-2 rounded-full bg-purple-600"></span>
              {{ faq.q }}
            </span>
            <ChevronDownIcon
              class="w-4 h-4 text-gray-400 transition-transform duration-200"
              :class="{ 'rotate-180': faq.open }"
            />
          </button>
          <div
            v-if="faq.open"
            class="p-4 pt-1 text-xs text-gray-600 dark:text-gray-300 bg-gray-50/50 dark:bg-gray-900/20 leading-relaxed border-t border-gray-100 dark:border-gray-700"
          >
            {{ faq.a }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  SparklesIcon,
  MagnifyingGlassIcon,
  CalendarDaysIcon,
  BellAlertIcon,
  CurrencyEuroIcon,
  CurrencyDollarIcon,
  UserGroupIcon,
  AcademicCapIcon,
  ChatBubbleLeftRightIcon,
  ArrowRightIcon,
  QuestionMarkCircleIcon,
  ChevronDownIcon,
  PrinterIcon,
  CheckCircleIcon,
  LightBulbIcon,
  TagIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  SunIcon,
  MoonIcon,
  ClockIcon,
  ShieldCheckIcon,
  ClipboardDocumentListIcon,
  BuildingOfficeIcon,
  QrCodeIcon
} from '@heroicons/vue/24/outline'

const searchQuery = ref('')
const activeCategory = ref('all')

const categories = [
  { id: 'all', title: 'All Guides', icon: SparklesIcon },
  { id: 'calendar', title: 'Calendar & Lessons', icon: CalendarDaysIcon },
  { id: 'attendance', title: 'Attendance', icon: CheckCircleIcon },
  { id: 'reminders', title: 'Reminders', icon: BellAlertIcon },
  { id: 'payrolls', title: 'Teacher Payroll', icon: CurrencyEuroIcon },
  { id: 'invoices', title: 'Invoices & Billing', icon: CurrencyDollarIcon },
  { id: 'students', title: 'Students & Packages', icon: UserGroupIcon },
  { id: 'teachers', title: 'Teachers & Staff', icon: AcademicCapIcon },
  { id: 'reports', title: 'Reports & Printing', icon: PrinterIcon },
  { id: 'admin', title: 'Settings & Security', icon: ShieldCheckIcon },
]

const dailyRoutines = [
  {
    timing: 'Morning Routine',
    title: 'Start of the Day',
    summary: 'Check the calendar for today\'s scheduled lessons and print room door sheets for classroom doors.',
    icon: SunIcon,
    bgGradient: 'bg-gradient-to-r from-amber-500 to-orange-500',
    categoryTarget: 'calendar'
  },
  {
    timing: 'Midday Routine',
    title: 'Taking Attendance',
    summary: 'Click each finished lesson, mark students as Present or Absent, and confirm hour deductions.',
    icon: CheckCircleIcon,
    bgGradient: 'bg-gradient-to-r from-emerald-500 to-teal-500',
    categoryTarget: 'attendance'
  },
  {
    timing: 'Evening Routine',
    title: 'Tomorrow Check',
    summary: 'Verify that automatic 24h reminders were sent and resolve any potential schedule overlaps.',
    icon: MoonIcon,
    bgGradient: 'bg-gradient-to-r from-indigo-500 to-purple-600',
    categoryTarget: 'reminders'
  },
  {
    timing: 'Monthly Routine',
    title: 'Payrolls & Billing',
    summary: 'Review teacher hours for the month, click Approve Payroll to lock payments, and export PDF statements.',
    icon: CurrencyEuroIcon,
    bgGradient: 'bg-gradient-to-r from-purple-600 to-pink-600',
    categoryTarget: 'payrolls'
  },
]

function selectRoutine(routine: any) {
  activeCategory.value = routine.categoryTarget
}

const guides = [
  {
    id: 'how-to-book-lesson',
    categoryId: 'calendar',
    categoryName: 'Calendar & Lessons',
    title: 'How to Schedule a Lesson',
    goal: 'Book a private or group lesson with a teacher in an available room.',
    icon: CalendarDaysIcon,
    route: '/lessons',
    badge: 'Lessons Page',
    steps: [
      'Go to the Lessons page from the sidebar menu.',
      'Click "+ Schedule Lesson" at the top right, or click any open time box on the calendar.',
      'Select the Teacher, Room, Subject, and add the Student(s).',
      'Choose the date, start time, and duration, then click "Save Lesson".'
    ],
    tip: 'The system automatically checks if the teacher is available and if the room is free.'
  },
  {
    id: 'how-to-spot-conflicts',
    categoryId: 'calendar',
    categoryName: 'Calendar & Lessons',
    title: 'How Double-Booking Prevention Works',
    goal: 'Avoid room collisions and teacher schedule overlaps automatically.',
    icon: CalendarDaysIcon,
    route: '/lessons',
    badge: 'Automatic Protection',
    steps: [
      'Lessons with overlapping bookings display a bright red warning badge on the calendar.',
      'Hover over the lesson card to see who is double-booked (e.g. "Room 101 occupied by Math 10").',
      'Click the lesson to adjust the time, change the room, or reassign the instructor.'
    ],
    tip: 'The system prevents saving conflicting lessons and will explain the conflict clearly.'
  },
  {
    id: 'how-to-take-attendance',
    categoryId: 'attendance',
    categoryName: 'Attendance',
    title: 'How to Record Student Attendance',
    goal: 'Mark student participation and deduct hours from their active packages.',
    icon: CheckCircleIcon,
    route: '/lessons',
    badge: '1-Click Attendance',
    steps: [
      'Click on any scheduled lesson in the calendar.',
      'Click "Mark Attendance" in the side panel that opens.',
      'Select the status for each student: Present (attended), Absent, or Late.',
      'Click "Save Attendance".'
    ],
    tip: 'Marking Present automatically subtracts the exact lesson hours from the student\'s active package.'
  },
  {
    id: 'how-reminders-work',
    categoryId: 'reminders',
    categoryName: 'Reminders',
    title: 'How Automatic Reminders Work',
    goal: 'Students and teachers receive timely notifications before every lesson.',
    icon: BellAlertIcon,
    route: null,
    badge: '100% Automated',
    steps: [
      '24 Hours Before: The system automatically reminds the teacher, student, and parents.',
      '2 Hours Before: A quick final notification is sent right before the session starts.',
      'Notifications arrive in the top bell icon on screen and via WhatsApp/Messages.',
      'When you reschedule a lesson, old reminders are cancelled automatically and updated.'
    ],
    tip: 'Reminders are completely automatic and run every 5 minutes in the background.'
  },
  {
    id: 'how-to-approve-payrolls',
    categoryId: 'payrolls',
    categoryName: 'Teacher Payroll',
    title: 'How to Review & Approve Teacher Pay',
    goal: 'Calculate teacher hours and lock monthly payroll statements for payment.',
    icon: CurrencyEuroIcon,
    route: '/payrolls',
    badge: 'Payrolls Page',
    steps: [
      'Go to the Payrolls page from the sidebar menu.',
      'Select the Month and Year at the top to see completed teaching hours.',
      'Review total teaching hours and earnings for each teacher.',
      'Click "Approve Payroll" to permanently lock the statement.',
      'Click "Download PDF" to print or save the official payment summary.'
    ],
    tip: 'Once approved, lessons in that period are locked to prevent accidental editing.'
  },
  {
    id: 'how-invoices-work',
    categoryId: 'invoices',
    categoryName: 'Invoices & Billing',
    title: 'How to Generate & Manage Invoices',
    goal: 'Create student tuition invoices, record payments, and track billing status.',
    icon: CurrencyDollarIcon,
    route: '/invoices',
    badge: 'Invoices Page',
    steps: [
      'Go to the Invoices page from the sidebar menu.',
      'Click "+ Create Invoice", select the student, billing period, and invoice items.',
      'When payment is received via bank transfer or cash, toggle the status to "Paid".',
      'Download and send the official PDF invoice to parents.'
    ],
    tip: 'Unpaid invoices display a clear status indicator so you never lose track of outstanding fees.'
  },
  {
    id: 'how-to-manage-packages',
    categoryId: 'students',
    categoryName: 'Students & Packages',
    title: 'How to Manage Student Packages & BuT Vouchers',
    goal: 'Assign hourly packages, track voucher validity, and monitor remaining balances.',
    icon: UserGroupIcon,
    route: '/students',
    badge: 'Students Page',
    steps: [
      'Go to Students and click on any student to open their profile.',
      'Click the "Packages" tab to see active and past hourly contracts.',
      'Click "Add Package" to assign a new 10, 20, or custom hour package or BuT voucher.',
      'As lessons occur, remaining hours and expiration dates update automatically.'
    ],
    tip: 'A yellow warning badge will appear whenever a student has fewer than 2 hours remaining.'
  },
  {
    id: 'how-to-print-reports',
    categoryId: 'reports',
    categoryName: 'Reports & Printing',
    title: 'How to Print Schedules & Door Sheets',
    goal: 'Generate ready-to-print weekly teacher timetables and room signs.',
    icon: PrinterIcon,
    route: '/lessons',
    badge: 'PDF Exports',
    steps: [
      'Teacher Timetable: In the teacher profile or calendar, click "Export Timetable PDF".',
      'Room Door Sheet: Click "Export Room Door Sheet PDF" to print a daily sign for classroom doors.',
      'Hours Proof (Stundennachweis): In the student profile, export attendance proofs for parents or Jobcenter.'
    ],
    tip: 'Room door sheets hide student surnames to comply with German/EU GDPR privacy rules.'
  },
  {
    id: 'how-to-manage-teachers-rooms',
    categoryId: 'teachers',
    categoryName: 'Teachers & Staff',
    title: 'How to Setup Teachers, Hourly Rates & Rooms',
    goal: 'Create instructor profiles, hourly pay rates, and configure classrooms.',
    icon: AcademicCapIcon,
    route: '/teachers',
    badge: 'Teachers Page',
    steps: [
      'Go to Teachers to add new instructors and specify their hourly wage (e.g. €35.00/h).',
      'Set teacher availability for each day of the week to enable smart booking validation.',
      'Go to Settings -> Rooms to configure classroom names and student capacity limits.'
    ],
    tip: 'Teachers can be linked directly to user accounts so they can view their personal timetable.'
  },
  {
    id: 'how-to-use-chat',
    categoryId: 'messaging',
    categoryName: 'Chat & Messages',
    title: 'How to Message Students & Teachers',
    goal: 'Communicate in real-time with instructors, students, and groups.',
    icon: ChatBubbleLeftRightIcon,
    route: '/messaging',
    badge: 'Messages Page',
    steps: [
      'Go to Messages from the sidebar menu.',
      'Select any conversation or click "+" to start a new chat with a teacher or student.',
      'Type your message and press Enter for real-time delivery.',
      'Send quick evaluation surveys to students to gather feedback on their lessons.'
    ],
    tip: 'Teachers can only message students in their own classes to maintain privacy.'
  },
  {
    id: 'how-to-manage-roles-users',
    categoryId: 'admin',
    categoryName: 'Settings & Security',
    title: 'User Roles & Permission Control',
    goal: 'Manage administrative staff, teacher, and student user accounts.',
    icon: ShieldCheckIcon,
    route: '/roles',
    badge: 'Security Page',
    steps: [
      'Go to Roles from the sidebar to inspect role permissions (Admin, Teacher, Student, Parent).',
      'Go to Users to invite new staff members and assign appropriate roles.',
      'Staff members only see pages and actions permitted by their assigned role.'
    ],
    tip: 'Super Admins have full access, while teachers and receptionists have tailored dashboards.'
  },
  {
    id: 'how-to-check-audit-logs',
    categoryId: 'admin',
    categoryName: 'Settings & Security',
    title: 'How to Inspect Audit Logs',
    goal: 'Track who performed what action for complete accountability.',
    icon: ClipboardDocumentListIcon,
    route: '/audit-logs',
    badge: 'Audit Page',
    steps: [
      'Go to Audit Logs from the sidebar menu.',
      'Filter by user, date, or event type (Create, Update, Delete).',
      'Inspect before-and-after values for any changed lesson, invoice, or student record.'
    ],
    tip: 'Audit logs cannot be modified or deleted by anyone, providing an airtight history.'
  },
  {
    id: 'how-mobile-codes-work',
    categoryId: 'admin',
    categoryName: 'Settings & Security',
    title: 'Student & Teacher Mobile Quick Codes',
    goal: 'Provide easy, passwordless mobile access for students and instructors.',
    icon: QrCodeIcon,
    route: '/students',
    badge: 'Mobile Access',
    steps: [
      'Open any Student or Teacher profile.',
      'Click "View Login Code" to generate a simple 6-digit access code.',
      'Students and teachers enter this code on mobile devices to log in without memorizing passwords.'
    ],
    tip: 'You can regenerate or revoke login codes instantly at any time.'
  }
]

const faqs = ref([
  {
    q: 'What happens if I change the time or date of a lesson (Rescheduling)?',
    a: 'When you update a lesson\'s date or time, the system automatically cancels any old pending reminders for the previous time, and will automatically send fresh reminders when the new lesson time approaches. You don\'t need to do anything extra!',
    open: false,
  },
  {
    q: 'A student canceled at the last minute. How do I record it properly?',
    a: 'Click the lesson on the calendar, click "Update Status", and select "Cancelled". If the cancellation was late according to center policy, check "Charge Student" to deduct the hour, or leave it unchecked if excused by management.',
    open: false,
  },
  {
    q: 'Can an instructor or admin edit a lesson after its payroll has been approved?',
    a: 'No. To protect your financial accounts from accidental tampering, the system automatically locks all lessons that have been approved in a teacher payroll statement. They cannot be edited or deleted unless an admin explicitly reopens the payroll.',
    open: false,
  },
  {
    q: 'How do I know which lessons are happening right now in the center?',
    a: 'On the calendar page, any lesson that is currently taking place in Europe/Berlin local time will display a pulsing green "In Progress" badge so reception staff know exactly which rooms are currently active.',
    open: false,
  },
  {
    q: 'How do I generate an official Stundennachweis for BuT or the Jobcenter?',
    a: 'Open the student profile in the Students section, go to the Lessons tab, and click "Export Stundennachweis PDF". The system generates an official document containing only verified Present and Late lessons with teacher signatures.',
    open: false,
  },
  {
    q: 'What if a parent has multiple children enrolled in the center?',
    a: 'The system links siblings automatically via the parent\'s account. The parent receives unified reminders for all their children without receiving duplicate messages.',
    open: false,
  },
  {
    q: 'How do I set up custom subjects or adjust classroom capacities?',
    a: 'Go to Settings from the sidebar menu. In the Subjects & Rooms tabs, you can add new school subjects (e.g. Mathematics, German, English) and configure rooms with maximum student seat counts.',
    open: false,
  },
  {
    q: 'How do teachers view only their own students and timetable?',
    a: 'When a teacher logs in, the system automatically filters the calendar and student lists so they only see their assigned classes and students, protecting privacy across the whole tutoring center.',
    open: false,
  }
])

function getFilteredCount(catId: string): number {
  if (catId === 'all') return guides.length
  return guides.filter(g => g.categoryId === catId).length
}

const filteredGuides = computed(() => {
  return guides.filter(g => {
    const matchesCat = activeCategory.value === 'all' || g.categoryId === activeCategory.value
    const matchesSearch = !searchQuery.value || 
      g.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      g.goal.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      g.steps.some(s => s.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (g.tip && g.tip.toLowerCase().includes(searchQuery.value.toLowerCase()))
    return matchesCat && matchesSearch
  })
})
</script>
