import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuditStore } from '../auditStore'
import api from '@/api'

vi.mock('@/api', () => ({
  default: {
    get: vi.fn(),
  }
}))

describe('Audit Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches audit logs successfully', async () => {
    const mockLogs = [
      { id: '1', event: 'created', user_name: 'Admin', auditable_type: 'User', auditable_id: '1' }
    ]
    
    // @ts-ignore
    api.get.mockResolvedValueOnce({ data: { data: mockLogs } })
    
    const store = useAuditStore()
    await store.fetchLogs()
    
    expect(store.logs).toEqual(mockLogs)
    expect(api.get).toHaveBeenCalledWith('/audit-logs')
  })
})
