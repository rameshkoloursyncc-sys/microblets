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
    if (error.response?.status === 401) {
      // Only clear user data on 401 if it's not the initial auth check
      const isAuthCheck = error.config?.url?.includes('/api/user')
      if (!isAuthCheck) {
        console.log('Got 401 on protected route, clearing user')
        localStorage.removeItem('user')
        // Force page reload to show login
        window.location.reload()
      }
    }
    return Promise.reject(error)
  }
)

export default axios
