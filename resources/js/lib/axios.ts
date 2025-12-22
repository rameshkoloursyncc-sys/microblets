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
    if (error.response?.status === 401 && error.response?.data?.error === 'session_expired') {
      console.log('Session expired detected, user needs to re-login')
      // Don't automatically clear user data, let the auth system handle it
      // The user will see they need to login again
    }
    return Promise.reject(error)
  }
)

export default axios
