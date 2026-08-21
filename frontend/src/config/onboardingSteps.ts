export type UserRole =
  | 'super_admin'
  | 'center_manager'
  | 'teacher'
  | 'receptionist'
  | 'student'
  | 'parent'

export type TourPlacement = 'top' | 'bottom' | 'left' | 'right' | 'center'
export type TargetBehavior = 'spotlight' | 'center' | 'skip'

export interface TourStep {
  id: string
  titleKey: string
  descriptionKey: string
  route?: string
  target?: string
  placement?: TourPlacement
  roles?: UserRole[]
  permissions?: string[]
  targetBehavior: TargetBehavior
  waitForTarget?: boolean
  required?: boolean
  badgeIcon?: string
}

export const mainProductTourSteps: TourStep[] = [
  {
    id: 'welcome',
    titleKey: 'onboarding.welcome.title',
    descriptionKey: 'onboarding.welcome.desc',
    targetBehavior: 'center',
    placement: 'center',
    badgeIcon: 'SparklesIcon',
  },
  {
    id: 'live_status',
    titleKey: 'onboarding.liveStatus.title',
    descriptionKey: 'onboarding.liveStatus.desc',
    route: '/',
    target: '[data-tour="live-status"]',
    targetBehavior: 'spotlight',
    placement: 'bottom',
    roles: ['super_admin', 'center_manager', 'teacher'],
    badgeIcon: 'MapPinIcon',
  },
  {
    id: 'students',
    titleKey: 'onboarding.students.title',
    descriptionKey: 'onboarding.students.desc',
    route: '/',
    target: '[data-tour="students-nav"]',
    targetBehavior: 'spotlight',
    placement: 'right',
    roles: ['super_admin', 'center_manager', 'receptionist'],
    badgeIcon: 'AcademicCapIcon',
  },
  {
    id: 'schedule',
    titleKey: 'onboarding.schedule.title',
    descriptionKey: 'onboarding.schedule.desc',
    route: '/',
    target: '[data-tour="schedule-nav"]',
    targetBehavior: 'spotlight',
    placement: 'right',
    badgeIcon: 'CalendarIcon',
  },
  {
    id: 'billing',
    titleKey: 'onboarding.billing.title',
    descriptionKey: 'onboarding.billing.desc',
    route: '/',
    target: '[data-tour="billing-nav"]',
    targetBehavior: 'spotlight',
    placement: 'right',
    roles: ['super_admin', 'center_manager'],
    permissions: ['manage billing'],
    badgeIcon: 'BanknotesIcon',
  },
  {
    id: 'messaging',
    titleKey: 'onboarding.messaging.title',
    descriptionKey: 'onboarding.messaging.desc',
    route: '/',
    target: '[data-tour="messaging-nav"]',
    targetBehavior: 'spotlight',
    placement: 'right',
    badgeIcon: 'ChatBubbleLeftRightIcon',
  },
  {
    id: 'user_guide',
    titleKey: 'onboarding.userGuide.title',
    descriptionKey: 'onboarding.userGuide.desc',
    route: '/',
    target: '[data-tour="guide-nav"]',
    targetBehavior: 'spotlight',
    placement: 'right',
    badgeIcon: 'BookOpenIcon',
  },
]
