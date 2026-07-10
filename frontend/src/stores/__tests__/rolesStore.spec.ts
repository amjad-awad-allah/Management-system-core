import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useRolesStore } from '../rolesStore'
import api from '@/api'

vi.mock('@/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  }
}))

describe('Roles Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches roles successfully', async () => {
    const mockRoles = [
      { id: '1', name: 'Super Admin', permissions: [] },
      { id: '2', name: 'Teacher', permissions: [] }
    ]
    
    // @ts-ignore
    api.get.mockResolvedValueOnce({ data: { data: mockRoles } })
    
    const store = useRolesStore()
    await store.fetchRoles()
    
    expect(store.roles).toEqual(mockRoles)
    expect(api.get).toHaveBeenCalledWith('/roles')
  })

  it('creates a role successfully', async () => {
    const newRole = { name: 'Manager', permissions: [] }
    const createdRole = { id: '3', ...newRole }
    
    // @ts-ignore
    api.post.mockResolvedValueOnce({ data: { data: createdRole } })
    // @ts-ignore
    api.get.mockResolvedValueOnce({ data: { data: [createdRole] } })
    
    const store = useRolesStore()
    await store.createRole(newRole)
    
    expect(store.roles).toContainEqual(createdRole)
    expect(api.post).toHaveBeenCalledWith('/roles', newRole)
  })
})
