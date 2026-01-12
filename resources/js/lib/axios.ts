import axios from 'axios'

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Include credentials (cookies) with requests
axios.defaults.withCredentials = true

// Add CSRF token interceptor
axios.interceptors.request.use((config) => {
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = (token as HTMLMetaElement).content
  }
  return config
})

// Add response interceptor to handle authentication errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle session expiration specifically
    if (error.response?.status === 401) {
      console.log('Session expired detected, user needs to re-login')
      
      // Only redirect to login if this is a critical auth failure
      // For now, let the components handle the error gracefully
      if (error.response?.data?.error === 'session_expired') {
        // Store that session expired for UI to handle
        localStorage.setItem('session_expired', 'true')
      }
    }
    return Promise.reject(error)
  }
)

export default axios
