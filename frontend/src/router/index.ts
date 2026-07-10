import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import LoginView from '@/views/auth/LoginView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresAuth: false }
    },
    {
      path: '/',
      component: DashboardLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'Dashboard',
          component: () => import('@/views/DashboardView.vue'),
        },
        {
          path: 'packages',
          name: 'packages',
          component: () => import('@/views/PackagesView.vue')
        },
        {
          path: 'invoices',
          name: 'invoices',
          component: () => import('@/views/InvoicesView.vue')
        },
        {
          path: 'lessons',
          name: 'Lessons',
          component: () => import('@/views/LessonsView.vue'),
        },
        {
          path: 'students',
          name: 'Students',
          component: () => import('@/views/StudentsView.vue'),
        },
        {
          path: 'students/:id',
          name: 'StudentProfile',
          component: () => import('@/views/StudentProfileView.vue'),
        },
        {
          path: 'teachers',
          name: 'Teachers',
          component: () => import('@/views/TeachersView.vue'),
        },
        {
          path: 'teachers/:id',
          name: 'TeacherProfile',
          component: () => import('@/views/TeacherProfileView.vue'),
        },
        {
          path: 'users',
          name: 'Users',
          component: () => import('@/views/core/UsersView.vue'),
        },
        {
          path: 'roles',
          name: 'Roles',
          component: () => import('@/views/core/RolesView.vue'),
        },
        {
          path: 'audit-logs',
          name: 'AuditLogs',
          component: () => import('@/views/core/AuditLogsView.vue'),
        },
        {
          path: 'settings',
          name: 'Settings',
          component: () => import('@/views/SubjectsRoomsView.vue'),
        }
      ]
    }
  ]
})

// Navigation Guard for Authentication
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = !!authStore.token
  
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login' })
  } else if (to.name === 'login' && isAuthenticated) {
    next({ name: 'Dashboard' })
  } else {
    if (isAuthenticated && !authStore.user && to.name !== 'login') {
      const success = await authStore.fetchUser()
      if (!success) {
        return next({ name: 'login' })
      }
    }
    next()
  }
})

export default router
