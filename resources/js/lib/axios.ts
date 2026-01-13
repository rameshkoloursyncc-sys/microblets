import axios from 'axios'

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Include credentials (cookies) with requests
axios.defaults.withCredentials = true

// Add response interceptor to handle authentication errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle session expiration specifically
    if (error.response?.status === 401) {
      // Check if this is a silent auth check (don't log errors for these)
      const isSilentAuth = error.config?.headers?.['X-Silent-Auth'] === 'true'
      
      if (!isSilentAuth) {
        console.log('401 Unauthorized detected')
      }
      
      // Check if this is a critical auth endpoint
      const criticalEndpoints = ['/api/login', '/api/register', '/api/logout']
      const isCriticalEndpoint = criticalEndpoints.some(endpoint => 
        error.config?.url?.includes(endpoint)
      )
      
      if (isCriticalEndpoint) {
        if (!isSilentAuth) {
          console.log('Critical auth endpoint failed, clearing session')
        }
        localStorage.removeItem('user')
      } else {
        if (!isSilentAuth) {
          console.log('Non-critical endpoint 401, keeping session for retry')
        }
        // For non-critical endpoints, don't clear the session immediately
        // Let the component handle the error and potentially retry
      }
      
      // Store that session may have expired for UI to handle (but not for silent checks)
      if (!isSilentAuth && error.response?.data?.error === 'session_expired') {
        localStorage.setItem('session_expired', 'true')
      }
    }
    
    // Handle network errors gracefully
    if (error.code === 'NETWORK_ERROR' || error.code === 'ECONNABORTED') {
      console.log('Network error detected, request may be retried')
    }
    
    return Promise.reject(error)
  }
)

// Add request interceptor to include user data as fallback
axios.interceptors.request.use((config) => {
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = (token as HTMLMetaElement).content
  }
  
  // Add user data as fallback authentication
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    try {
      const userData = JSON.parse(storedUser)
      if (userData && userData.id && userData.name && userData.role) {
        config.headers['X-Auth-User'] = storedUser
      }
    } catch (e) {
      // Invalid user data, remove it
      localStorage.removeItem('user')
    }
  }
  
  return config
})

export default axios
