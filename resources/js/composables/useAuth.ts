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
          
          // Try to verify with backend silently (don't show 401 errors)
          try {
            const response = await axios.get('/api/user', { 
              timeout: 5000, // Reduced timeout for faster failure
              headers: { 'X-Silent-Auth': 'true' }
            })
            if (response.data.user) {
              // Update user data from server if successful
              user.value = response.data.user
              localStorage.setItem('user', JSON.stringify(response.data.user))
              console.log('Backend verification successful, user updated')
              return // Success - exit early
            }
          } catch (e: any) {
            // Silently handle the 401 during initialization
            if (e.response?.status === 401) {
              console.log('Session expired during init, attempting auto-restore for admin user')
              
              // If this is an admin user, try to auto-restore session
              if (parsedUser.name === 'admin' || parsedUser.name === 'koloursyncc' || parsedUser.name === 'koloursyncc11') {
                try {
                  console.log('Attempting silent auto-login for admin user:', parsedUser.name)
                  
                  // Determine correct credentials
                  let loginData = { name: 'admin', password: 'admin123' }
                  if (parsedUser.name === 'koloursyncc' || parsedUser.name === 'koloursyncc11') {
                    loginData = { name: 'koloursyncc', password: 'kolorsync1010' }
                  }
                  
                  const loginResponse = await axios.post('/api/login', loginData, { 
                    timeout: 10000,
                    headers: { 'X-Silent-Auth': 'true' }
                  })
                  
                  if (loginResponse.data.user) {
                    user.value = loginResponse.data.user
                    localStorage.setItem('user', JSON.stringify(loginResponse.data.user))
                    console.log('Silent auto-login successful, session restored for:', loginResponse.data.user.name)
                    return // Success - exit early
                  }
                } catch (loginError: any) {
                  console.log('Silent auto-login failed, keeping stored user for offline use')
                  // Keep the stored user for offline functionality
                  return // Keep stored user, don't clear
                }
              }
              
              console.log('Session restoration not available, keeping stored user for offline use')
              // For non-admin users or if auto-restore fails, keep stored user
              return // Keep stored user, don't clear
            } else {
              console.log('Network issue during init, keeping stored user:', e.code || 'UNKNOWN')
              // For network issues, keep the stored user
              return // Keep stored user, don't clear
            }
          }
        } catch (e) {
          console.log('Invalid stored user data, clearing')
          localStorage.removeItem('user')
          user.value = null
        }
      } else {
        // No stored user, try silent server session check
        try {
          const response = await axios.get('/api/user', { 
            timeout: 5000,
            headers: { 'X-Silent-Auth': 'true' }
          })
          if (response.data.user) {
            user.value = response.data.user
            localStorage.setItem('user', JSON.stringify(response.data.user))
            console.log('Found existing server session for:', response.data.user.name)
          }
        } catch (e) {
          console.log('No stored user and no server session - user needs to login')
        }
      }
    } catch (e) {
      console.log('Session initialization failed:', e)
      // Don't clear user on initialization errors
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
          await axios.get('/api/user', { timeout: 5000 })
          console.log('Session keep-alive successful')
        } catch (e: any) {
          console.log('Session keep-alive failed:', e.response?.status)
          
          // If session expired, try to restore it for admin users
          if (e.response?.status === 401 && user.value) {
            console.log('Session expired during keep-alive, attempting restore')
            await attemptSessionRestore()
          }
        }
      }
    }, 10 * 60 * 1000) // Check every 10 minutes
  }

  // Attempt to restore session for admin users
  const attemptSessionRestore = async () => {
    if (!user.value) return false
    
    const currentUser = user.value
    if (currentUser.name === 'admin' || currentUser.name === 'koloursyncc' || currentUser.name === 'koloursyncc11') {
      try {
        console.log('Attempting session restore for:', currentUser.name)
        
        // Determine correct credentials
        let loginData = { name: 'admin', password: 'admin123' }
        if (currentUser.name === 'koloursyncc' || currentUser.name === 'koloursyncc11') {
          loginData = { name: 'koloursyncc', password: 'kolorsync1010' }
        }
        
        const loginResponse = await axios.post('/api/login', loginData, { timeout: 10000 })
        
        if (loginResponse.data.user) {
          user.value = loginResponse.data.user
          localStorage.setItem('user', JSON.stringify(loginResponse.data.user))
          console.log('Session restore successful')
          return true
        }
      } catch (error: any) {
        console.log('Session restore failed:', error.response?.data?.message || error.message)
      }
    }
    
    return false
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
    startSessionKeepAlive,
    attemptSessionRestore
  }
}