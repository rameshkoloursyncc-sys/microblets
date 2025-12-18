<template>
  <div class="transition-all duration-300" :class="props.sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          User Management
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Create and manage user accounts
        </p>
      </div>

      <!-- Create User Form -->
      <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Create New User
        </h2>
        
        <form @submit.prevent="createUser" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Username
              </label>
              <input
                v-model="newUser.name"
                type="text"
                required
                class="w-full p-3 border rounded-lg bg-white dark:bg-gray-700 dark:text-white"
                placeholder="Enter username"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Password
              </label>
              <input
                v-model="newUser.password"
                type="password"
                required
                minlength="6"
                class="w-full p-3 border rounded-lg bg-white dark:bg-gray-700 dark:text-white"
                placeholder="Enter password"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Role
              </label>
              <select
                v-model="newUser.role"
                class="w-full p-3 border rounded-lg bg-white dark:bg-gray-700 dark:text-white"
              >
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          
          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? 'Creating...' : 'Create User' }}
          </button>
        </form>
      </div>

      <!-- Users List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Existing Users
          </h2>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  ID
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Username
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Role
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Created
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <!-- Default Admin Row -->
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  0
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  koloursyncc
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    Admin (Default)
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  System Default
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  Cannot Delete
                </td>
              </tr>
              
              <!-- Database Users -->
              <tr v-for="user in users" :key="user.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ user.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ user.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="user.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'"
                  >
                    {{ user.role }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ formatDate(user.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="deleteUser(user.id)"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Notifications -->
      <div class="fixed right-4 top-4 space-y-3 z-50">
        <div v-for="n in notifications" :key="n.id" class="rounded shadow p-3 max-w-sm"
             :class="n.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
          <div class="font-semibold">{{ n.title }}</div>
          <div class="text-sm">{{ n.message }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '@/lib/axios'

const props = defineProps<{
  sidebarCollapsed?: boolean
}>()

interface User {
  id: number
  name: string
  role: string
  created_at: string
}

interface Notification {
  id: number
  type: 'success' | 'error'
  title: string
  message: string
}

const users = ref<User[]>([])
const loading = ref(false)
const notifications = ref<Notification[]>([])
let notificationId = 0

const newUser = ref({
  name: '',
  password: '',
  role: 'user'
})

const showNotification = (type: Notification['type'], title: string, message: string) => {
  const id = ++notificationId
  notifications.value.push({ id, type, title, message })
  setTimeout(() => {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }, 5000)
}

const loadUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    users.value = response.data.users
  } catch (err: any) {
    showNotification('error', 'Error', 'Failed to load users')
  }
}

const createUser = async () => {
  loading.value = true
  try {
    await axios.post('/api/users', newUser.value)
    showNotification('success', 'Success', 'User created successfully')
    
    // Reset form
    newUser.value = {
      name: '',
      password: '',
      role: 'user'
    }
    
    // Reload users
    await loadUsers()
  } catch (err: any) {
    const message = err.response?.data?.message || 'Failed to create user'
    showNotification('error', 'Error', message)
  } finally {
    loading.value = false
  }
}

const deleteUser = async (id: number) => {
  if (!confirm('Are you sure you want to delete this user?')) return
  
  try {
    await axios.delete(`/api/users/${id}`)
    showNotification('success', 'Success', 'User deleted successfully')
    await loadUsers()
  } catch (err: any) {
    const message = err.response?.data?.message || 'Failed to delete user'
    showNotification('error', 'Error', message)
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  loadUsers()
})
</script>