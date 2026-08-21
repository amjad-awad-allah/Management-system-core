import { createI18n } from 'vue-i18n'
import { supportedLocales, defaultLocale, fallbackLocale } from '@/config/locales'

// Import German Modules
import deCommon from './locales/de/common'
import deNav from './locales/de/nav'
import deDashboard from './locales/de/dashboard'
import deStudents from './locales/de/students'
import deLessons from './locales/de/lessons'
import deBilling from './locales/de/billing'
import dePayroll from './locales/de/payroll'
import deMessaging from './locales/de/messaging'
import deGuide from './locales/de/guide'
import deOnboarding from './locales/de/onboarding'
import deTeachers from './locales/de/teachers'
import deSettings from './locales/de/settings'

// Import English Modules
import enCommon from './locales/en/common'
import enNav from './locales/en/nav'
import enDashboard from './locales/en/dashboard'
import enStudents from './locales/en/students'
import enLessons from './locales/en/lessons'
import enBilling from './locales/en/billing'
import enPayroll from './locales/en/payroll'
import enMessaging from './locales/en/messaging'
import enGuide from './locales/en/guide'
import enOnboarding from './locales/en/onboarding'
import enTeachers from './locales/en/teachers'
import enSettings from './locales/en/settings'

export const messages = {
  de: {
    common: deCommon,
    nav: deNav,
    dashboard: deDashboard,
    students: deStudents,
    teachers: deTeachers,
    lessons: deLessons,
    billing: deBilling,
    payroll: dePayroll,
    messaging: deMessaging,
    guide: deGuide,
    onboarding: deOnboarding,
    settings: deSettings,
  },
  en: {
    common: enCommon,
    nav: enNav,
    dashboard: enDashboard,
    students: enStudents,
    teachers: enTeachers,
    lessons: enLessons,
    billing: enBilling,
    payroll: enPayroll,
    messaging: enMessaging,
    guide: enGuide,
    onboarding: enOnboarding,
    settings: enSettings,
  },
}

export const numberFormats = {
  de: {
    currency: {
      style: 'currency',
      currency: 'EUR',
      currencyDisplay: 'symbol',
    },
    decimal: {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
    percent: {
      style: 'percent',
      useGrouping: false,
    },
  },
  en: {
    currency: {
      style: 'currency',
      currency: 'EUR',
      currencyDisplay: 'symbol',
    },
    decimal: {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    },
    percent: {
      style: 'percent',
      useGrouping: false,
    },
  },
} as const

export const datetimeFormats = {
  de: {
    short: {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    },
    long: {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    },
    time: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    },
  },
  en: {
    short: {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
    },
    long: {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    },
    time: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    },
  },
} as const

// Read saved locale or fallback to default
const savedLocale = localStorage.getItem('app_locale')
const initialLocale =
  savedLocale && ['de', 'en'].includes(savedLocale)
    ? savedLocale
    : defaultLocale

export const i18n = createI18n({
  legacy: false,
  locale: initialLocale,
  fallbackLocale,
  messages,
  numberFormats,
  datetimeFormats,
})

export function setAppLocale(locale: string) {
  if (!['de', 'en'].includes(locale)) return

  ;(i18n.global.locale as any).value = locale
  localStorage.setItem('app_locale', locale)

  const config = supportedLocales.find((l) => l.code === locale)
  document.documentElement.lang = locale
  document.documentElement.dir = config ? config.dir : 'ltr'
}

// Initial document attributes
const initialConfig = supportedLocales.find((l) => l.code === initialLocale)
document.documentElement.lang = initialLocale
document.documentElement.dir = initialConfig ? initialConfig.dir : 'ltr'

export default i18n
