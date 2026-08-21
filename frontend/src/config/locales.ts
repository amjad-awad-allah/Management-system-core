export interface LocaleConfig {
  code: string
  name: string
  nativeName: string
  dir: 'ltr' | 'rtl'
  flag: string
}

export const supportedLocales: LocaleConfig[] = [
  {
    code: 'de',
    name: 'German',
    nativeName: 'Deutsch',
    dir: 'ltr',
    flag: '🇩🇪',
  },
  {
    code: 'en',
    name: 'English',
    nativeName: 'English',
    dir: 'ltr',
    flag: '🇬🇧',
  },
  // Future plug-and-play extensions:
  // {
  //   code: 'ar',
  //   name: 'Arabic',
  //   nativeName: 'العربية',
  //   dir: 'rtl',
  //   flag: '🇸🇦',
  // },
  // {
  //   code: 'tr',
  //   name: 'Turkish',
  //   nativeName: 'Türkçe',
  //   dir: 'ltr',
  //   flag: '🇹🇷',
  // },
]

export const defaultLocale = 'de'
export const fallbackLocale = 'en'
