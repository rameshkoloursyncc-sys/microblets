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
          
          // Try to verify with backend and restore session if needed
          try {
            const response = await axios.get('/api/user', { timeout: 5000 })
            if (response.data.user) {
              // Update user data from server if successful
              user.value = response.data.user
              localStorage.setItem('user', JSON.stringify(response.data.user))
              console.log('Backend verification successful, user updated')
            }
          } catch (e: any) {
            if (e.response?.status === 401) {
              console.log('Backend session expired, attempting auto-restore for admin user')
              
              // If this is the admin user, try to auto-restore session
              if (parsedUser.name === 'admin' || parsedUser.name === 'koloursyncc' || parsedUser.name === 'koloursyncc11') {
                try {
                  console.log('Attempting auto-login for admin user')
                  const loginResponse = await axios.post('/api/login', {
                    name: parsedUser.name === 'admin' ? 'admin' : 'koloursyncc',
                    password: parsedUser.name === 'admin' ? 'admin123' : 'kolorsync1010'
                  })
                  
                  if (loginResponse.data.user) {
                    user.value = loginResponse.data.user
                    localStorage.setItem('user', JSON.stringify(loginResponse.data.user))
                    console.log('Auto-login successful, session restored')
                    return
                  }
                } catch (loginError) {
                  console.log('Auto-login failed:', loginError)
                }
              }
              
              console.log('Session restoration needed - keeping user for offline use')
              // Keep the user data for offline functionality
              // Don't clear immediately, let them try to use the app
            } else {
              console.log('Backend verification failed (network issue), keeping stored user:', e.response?.status)
              // For network issues, keep the stored user
            }
          }
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
        // Server says no user - but be cautious about clearing
        console.log('Server returned no user data')
        // Only clear if we don't have a stored user
        if (!user.value) {
          return false
        }
        // If we have a stored user, keep it for now
        console.log('Keeping stored user despite server response')
        return true
      }
    } catch (e: any) {
      console.log('Auth check error:', e.response?.status, e.code, e.message)
      
      // Only clear user on explicit 401 authentication errors AND we're not in initialization
      if (e.response?.status === 401 && isInitialized.value) {
        console.log('Authentication failed after initialization - clearing user')
        user.value = null
        localStorage.removeItem('user')
        return false
      }
      
      // For all other errors or during initialization, keep existing user state
      console.log('Non-auth error or during init, keeping existing user state')
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
          // Make a simple request to keep session alive
          await axios.get('/api/user', { timeout: 3000 })
          console.log('Session keep-alive successful')
        } catch (e: any) {
          console.log('Session keep-alive failed:', e.response?.status)
          // Don't clear user on keep-alive failures
          // Let the user continue working with cached data
        }
      }
    }, 10 * 60 * 1000) // Check every 10 minutes
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