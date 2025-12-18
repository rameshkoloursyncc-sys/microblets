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
      // Clear stored user data on 401 errors
      localStorage.removeItem('user')
      // Don't auto-reload, let the app handle the auth state
    }
    return Promise.reject(error)
  }
)

export default axios
