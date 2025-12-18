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
          
          // Always keep the stored user during initialization
          // Don't verify with backend during init to avoid logout on refresh
          console.log('Keeping stored user without backend verification')
        } catch (e) {
          console.log('Invalid stored user data, clearing')
          localStorage.removeItem('user')
          user.value = null
        }
      } else {
        // No stored user, check if there's a server session
        try {
          await checkAuthSilent()
        } catch (e) {
          console.log('No stored user and no server session')
        }
      }
    } catch (e) {
      console.log('Session initialization failed:', e)
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
      const response = await axios.get('/api/user', { timeout: 5000 })
      if (response.data.user) {
        // Update user data from server
        user.value = response.data.user
        localStorage.setItem('user', JSON.stringify(response.data.user))
        return true
      } else {
        // Server says no user - only clear if we're sure
        console.log('Server returned no user data')
        return false
      }
    } catch (e: any) {
      console.log('Auth check error:', e.response?.status, e.code, e.message)
      
      // Only clear user on explicit authentication errors
      if (e.response?.status === 401) {
        console.log('Authentication failed - clearing user')
        user.value = null
        localStorage.removeItem('user')
        return false
      }
      
      // For all other errors (network, timeout, server errors, etc.)
      // Keep the existing user state and don't force logout
      console.log('Non-auth error, keeping existing user state')
      return !!user.value
    }
  }

  const checkAuthSilent = async () => {
    try {
      const response = await axios.get('/api/user', { timeout: 5000 })
      if (response.data.user) {
        // Update user data from server
        user.value = response.data.user
        localStorage.setItem('user', JSON.stringify(response.data.user))
        return true
      } else {
        // Server says no user - but don't clear stored user during init
        console.log('Server returned no user data (silent check)')
        return false
      }
    } catch (e: any) {
      console.log('Silent auth check error:', e.response?.status, e.code)
      
      // During initialization, don't clear user on any errors
      // Let the user try to use the app with stored credentials
      return false
    }
  }

  // Periodic session check to keep session alive
  const startSessionKeepAlive = () => {
    setInterval(async () => {
      if (user.value) {
        try {
          await checkAuthSilent()
        } catch (e) {
          console.log('Session keep-alive failed:', e)
        }
      }
    }, 5 * 60 * 1000) // Check every 5 minutes
  }

  return {
    user: computed(() => user.value),
    isAuthenticated,
    isAdmin,
    isUser,
    initAuth,
    login,
    logout,
    checkAuth,
    startSessionKeepAlive
  }
}