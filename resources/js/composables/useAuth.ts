import { ref, computed } from 'vue'
import axios from '@/lib/axios'

interface User {
  id: number
  name: string
  role: string
}

const user = ref<User | null>(null)
const isInitialized = ref(false)

export const useAuth = () => {
  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isUser = computed(() => user.value?.role === 'user')

  const initAuth = async () => {
    if (isInitialized.value) return
    
    try {
      isInitialized.value = true
      
      // Check localStorage for persisted user first
      const storedUser = localStorage.getItem('user')
      if (storedUser) {
        try {
          const parsedUser = JSON.parse(storedUser)
          user.value = parsedUser
          console.log('Restored user from localStorage:', parsedUser.name)
        } catch (e) {
          console.log('Invalid stored user data, clearing')
          localStorage.removeItem('user')
          user.value = null
        }
      }
      
      // Always try to verify with backend, but don't fail if it's down
      try {
        await checkAuth()
      } catch (e) {
        console.log('Backend verification failed, using stored user if available')
        // If we have a stored user and backend is unreachable, keep the user logged in
        if (storedUser && user.value) {
          console.log('Keeping user logged in despite backend error')
        }
      }
    } catch (e) {
      console.log('Session initialization failed:', e)
      // Don't clear user on initialization failure - keep stored user if exists
    }
  }

  const login = (userData: User) => {
    user.value = userData
    localStorage.setItem('user', JSON.stringify(userData))
  }

  const logout = async () => {
    try {
      await axios.post('/api/logout')
    } catch (e) {
      // Continue with logout even if API call fails
    }
    
    user.value = null
    localStorage.removeItem('user')
  }

  const checkAuth = async () => {
    try {
      const response = await axios.get('/api/user', { timeout: 10000 })
      if (response.data.user) {
        user.value = response.data.user
        localStorage.setItem('user', JSON.stringify(response.data.user))
        return true
      } else {
        // Only clear user if we get a valid response but no user
        user.value = null
        localStorage.removeItem('user')
        return false
      }
    } catch (e: any) {
      console.log('Auth check error:', e.response?.status, e.message)
      
      // If it's a clear authentication error (401/403), clear user
      if (e.response?.status === 401 || e.response?.status === 403) {
        user.value = null
        localStorage.removeItem('user')
        return false
      }
      
      // For network errors, timeouts, or server errors (500, 502, 503, etc.)
      // Keep the stored user and don't force logout
      if (!e.response || e.response.status >= 500 || e.code === 'ECONNABORTED') {
        console.log('Network/server error, keeping stored user')
        return !!user.value
      }
      
      // For other client errors, clear user
      user.value = null
      localStorage.removeItem('user')
      return false
    }
  }

  return {
    user: computed(() => user.value),
    isAuthenticated,
    isAdmin,
    isUser,
    initAuth,
    login,
    logout,
    checkAuth
  }
}