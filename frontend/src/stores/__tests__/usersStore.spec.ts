import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useUsersStore } from '../usersStore'
import api from '@/api'

// Mock the API module
vi.mock('@/api', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  }
}))

describe('Users Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches users successfully', async () => {
    const mockUsers = [
      { id: '1', name: 'User 1', email: 'test1@test.com', roles: [] },
      { id: '2', name: 'User 2', email: 'test2@test.com', roles: [] }
    ]
    
    // @ts-ignore
    api.get.mockResolvedValueOnce({ data: { data: mockUsers } })
    
    const store = useUsersStore()
    await store.fetchUsers()
    
    expect(store.users).toEqual(mockUsers)
    expect(api.get).toHaveBeenCalledWith('/users')
  })

  it('creates a user successfully', async () => {
    const newUser = { name: 'New User', email: 'new@test.com', password: 'password', roles: [] }
    const createdUser = { id: '3', ...newUser }
    
    // @ts-ignore
    api.post.mockResolvedValueOnce({ data: { data: createdUser } })
    // @ts-ignore - mock the subsequent fetchUsers call
    api.get.mockResolvedValueOnce({ data: { data: [createdUser] } })
    
    const store = useUsersStore()
    await store.createUser(newUser)
    
    // We expect the new user to be in the store
    expect(store.users).toContainEqual(createdUser)
    expect(api.post).toHaveBeenCalledWith('/users', newUser)
    expect(api.get).toHaveBeenCalledWith('/users')
  })

  it('deletes a user successfully', async () => {
    // Setup initial store state
    const store = useUsersStore()
    store.users = [
      { id: '1', name: 'User 1', email: 'test1@test.com', roles: [] },
      { id: '2', name: 'User 2', email: 'test2@test.com', roles: [] }
    ]
    
    // @ts-ignore
    api.delete.mockResolvedValueOnce({ data: {} })
    // @ts-ignore - mock the subsequent fetchUsers call
    api.get.mockResolvedValueOnce({ data: { data: [{ id: '2', name: 'User 2', email: 'test2@test.com', roles: [] }] } })
    
    await store.deleteUser('1')
    
    expect(store.users).toHaveLength(1)
    expect(store.users[0].id).toBe('2')
    expect(api.delete).toHaveBeenCalledWith('/users/1')
  })
})
