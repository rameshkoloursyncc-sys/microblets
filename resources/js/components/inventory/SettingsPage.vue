<template>
  <div class="transition-all duration-300" :class="props.sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          System Settings
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Manage rates, seed data, and system configurations for all belt types
        </p>
      </div>

      <!-- Belt Type Selector -->
      <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Select Belt Type
        </label>
        <select 
          v-model="selectedBeltType" 
          @change="onBeltTypeChange"
          class="w-full p-3 border rounded-lg bg-white dark:bg-gray-700 dark:text-white text-lg"
        >
          <option value="vee">Vee Belts</option>
          <option value="cogged">Cogged Belts</option>
          <option value="poly">Poly Belts</option>
          <option value="tpu">TPU Belts</option>
        </select>
      </div>

      <!-- Rate Formula Management -->
      <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          {{ beltTypeConfig[selectedBeltType].name }} Rate Formula Management
        </h2>
        
        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
          <h3 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Formula Structure:</h3>
          <div class="text-sm text-blue-700 dark:text-blue-300">
            <div v-if="selectedBeltType === 'poly'">
              <strong>Poly Belts:</strong> rate_per_rib = (ribs ÷ divisor) × multiplier<br>
              <em>Example: PK section with 4 ribs, divisor 25.4, multiplier 0.59 → (4÷25.4)×0.59 = 0.093</em>
            </div>
            <div v-else>
              <strong>{{ beltTypeConfig[selectedBeltType].name }}:</strong> rate = (size ÷ divisor) × multiplier<br>
              <em>Examples:</em><br>
              • <em>A section: size 1000, divisor 1, multiplier 1.05 → (1000÷1)×1.05 = 1050</em><br>
              • <em>3V section: size 1000, divisor 10, multiplier 1.50 → (1000÷10)×1.50 = 150</em>
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="section in currentSections" :key="section" class="border rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ section }} Section Formula
            </label>
            
            <div class="mb-2 text-xs text-gray-600 dark:text-gray-400">
              <span v-if="selectedBeltType === 'poly'">ribs ÷ divisor × multiplier</span>
              <span v-else>size ÷ divisor × multiplier</span>
            </div>
            
            <!-- Divisor input (for all sections) -->
            <div class="mb-2">
              <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                {{ selectedBeltType === 'poly' ? 'Divisor (ribs):' : 'Divisor (size):' }}
              </label>
              <input 
                v-model.number="divisors[section]" 
                type="number" 
                step="0.1" 
                min="0.1"
                class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white text-sm"
                :placeholder="currentDefaultDivisors[section] || (selectedBeltType === 'poly' ? '25.4' : '1')"
              />
            </div>
            
            <!-- Multiplier input -->
            <div class="mb-2">
              <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                {{ selectedBeltType === 'poly' ? 'Multiplier:' : 'Multiplier:' }}
              </label>
              <input 
                v-model.number="formulas[section]" 
                type="number" 
                step="0.01" 
                min="0"
                class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white"
                :placeholder="currentDefaultFormulas[section] || '0'"
              />
            </div>
            
            <button 
              @click="updateSectionFormula(section)" 
              class="w-full px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
              :disabled="loading"
            >
              Update Formula
            </button>
            
            <div class="mt-2 text-xs text-gray-500">
              Products: {{ sectionCounts[section] || 0 }}
              <br>Current: {{ getCurrentFormulaText(section) }}
            </div>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <button 
            @click="updateAllFormulas" 
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
            :disabled="loading"
          >
            Update All Formulas
          </button>
          <button 
            @click="resetFormulasToDefaults" 
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
            :disabled="loading"
          >
            Reset to Defaults
          </button>
          <button 
            @click="recalculateAllRates" 
            class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700"
            :disabled="loading"
          >
            Recalculate All Rates
          </button>
        </div>
      </div>

      <!-- Data Seeding -->
      <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          {{ beltTypeConfig[selectedBeltType].name }} Data Seeding
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="section in currentSections" :key="`seed-${section}`" class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-medium text-gray-900 dark:text-white">{{ section }} Section</h3>
              <span class="text-sm text-gray-500">{{ sectionCounts[section] || 0 }} products</span>
            </div>
            
            <div class="space-y-2">
              <button 
                @click="seedSection(section)" 
                class="w-full px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                :disabled="loading || !hasJsonFile(section)"
              >
                {{ hasJsonFile(section) ? 'Seed from JSON' : 'No JSON File' }}
              </button>
              
              <button 
                @click="clearSection(section)" 
                class="w-full px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                :disabled="loading"
              >
                Clear Section
              </button>
            </div>
          </div>
        </div>

        <div class="mt-6 border-t pt-4">
          <h3 class="font-medium text-gray-900 dark:text-white mb-3">Bulk Operations</h3>
          <div class="flex gap-2 flex-wrap">
            <button 
              @click="seedAllSections" 
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
              :disabled="loading"
            >
              Seed All Sections
            </button>
            <button 
              @click="clearAllData" 
              class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
              :disabled="loading"
            >
              Clear All Data
            </button>
            <button 
              @click="exportAllData" 
              class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700"
              :disabled="loading"
            >
              Export All Data
            </button>
          </div>
        </div>
      </div>

      <!-- System Information -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">System Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
            <div class="text-2xl font-bold text-blue-600">{{ totalProducts }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Total Products</div>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
            <div class="text-2xl font-bold text-green-600">{{ totalValue.toFixed(2) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Total Value (₹)</div>
          </div>
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
            <div class="text-2xl font-bold text-purple-600">{{ activeSections }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Active Sections</div>
          </div>
        </div>
      </div>

      <!-- Notifications -->
      <div class="fixed right-4 top-4 space-y-3 z-50">
        <div v-for="n in notifications" :key="n.id" class="rounded shadow p-3 max-w-sm"
             :class="n.type === 'success' ? 'bg-green-100 text-green-800' : n.type === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
          <div class="font-semibold">{{ n.title }}</div>
          <div class="text-sm">{{ n.message }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps<{
  sidebarCollapsed?: boolean
}>()

interface Notification { id: number; type: 'success'|'error'|'warning'; title: string; message: string }

const loading = ref(false)
const notifications = ref<Notification[]>([])
let notificationId = 0

const showNotification = (type: Notification['type'], title: string, message: string, timeout = 5000) => { 
  const id = ++notificationId
  notifications.value.push({ id, type, title, message })
  if (timeout > 0) setTimeout(() => removeNotification(id), timeout)
}

const removeNotification = (id: number) => { 
  notifications.value = notifications.value.filter(n => n.id !== id)
}

// Selected belt type
const selectedBeltType = ref('tpu')

// Belt type configurations
const beltTypeConfig = {
  vee: {
    name: 'Vee Belts',
    apiEndpoint: '/api/vee-belts',
    sections: ['A', 'B', 'C', 'D', 'E', 'SPA', 'SPB', 'SPC', 'SPZ', '3V', '5V', '8V'],
    formulaType: 'size_divide_multiply', // rate = (size ÷ divisor) × multiplier
    defaultFormulas: {
      'A': 1.05, 'B': 1.15, 'C': 1.25, 'D': 1.35, 'E': 1.45,
      'SPA': 1.10, 'SPB': 1.20, 'SPC': 1.30, 'SPZ': 1.00,
      '3V': 1.50, '5V': 1.87, '8V': 2.50
    },
    defaultDivisors: {
      'A': 1, 'B': 1, 'C': 1, 'D': 1, 'E': 1,
      'SPA': 1, 'SPB': 1, 'SPC': 1, 'SPZ': 1,
      '3V': 10, '5V': 10, '8V': 10
    },
    sectionsWithDivisor: ['A', 'B', 'C', 'D', 'E', 'SPA', 'SPB', 'SPC', 'SPZ', '3V', '5V', '8V'],
    jsonFiles: {
      'A': 'AProducts.json', 'B': 'BProducts.json', 'C': 'CProducts.json',
      'D': 'DProducts.json', 'E': 'EProducts.json', 'SPA': 'SPAProducts.json',
      'SPB': 'SPBProducts.json', 'SPC': 'SPCProducts.json', 'SPZ': 'SPZProducts.json',
      '3V': '3VProducts.json', '5V': '5VProducts.json', '8V': '8VProducts.json'
    }
  },
  cogged: {
    name: 'Cogged Belts',
    apiEndpoint: '/api/cogged-belts',
    sections: ['AX', 'BX', 'CX', 'XPA', 'XPB', 'XPC', 'XPZ', '3VX', '5VX'],
    formulaType: 'size_divide_multiply', // rate = (size ÷ divisor) × multiplier
    defaultFormulas: {
      'AX': 1.95, 'BX': 3.45, 'CX': 5.68,
      'XPA': 1.85, 'XPB': 2.95, 'XPC': 4.25, 'XPZ': 1.45,
      '3VX': 2.15, '5VX': 3.25
    },
    defaultDivisors: {
      'AX': 1, 'BX': 1, 'CX': 1,
      'XPA': 1, 'XPB': 1, 'XPC': 1, 'XPZ': 1,
      '3VX': 10, '5VX': 10
    },
    sectionsWithDivisor: ['AX', 'BX', 'CX', 'XPA', 'XPB', 'XPC', 'XPZ', '3VX', '5VX'],
    jsonFiles: {
      'AX': 'AXProducts.json', 'BX': 'BXProducts.json', 'CX': 'CXProducts.json',
      'XPA': 'XPAProducts.json', 'XPB': 'XPBProducts.json', 'XPC': 'XPCProducts.json',
      'XPZ': 'XPZProducts.json', '3VX': '3VXProducts.json', '5VX': '5VXProducts.json'
    }
  },
  poly: {
    name: 'Poly Belts',
    apiEndpoint: '/api/poly-belts',
    sections: ['PJ', 'PK', 'PL', 'PM', 'PH', 'DPL', 'DPK'],
    formulaType: 'ribs_divide_multiply', // rate_per_rib = ribs ÷ divisor × multiplier
    defaultFormulas: {
      'PJ': 0.36, 'PK': 0.59, 'PL': 0.85, 'PM': 1.25, 'PH': 1.85,
      'DPL': 1.15, 'DPK': 0.89
    },
    defaultDivisors: {
      'PJ': 25.4, 'PK': 25.4, 'PL': 25.4, 'PM': 25.4, 'PH': 25.4,
      'DPL': 25.4, 'DPK': 25.4
    },
    sectionsWithDivisor: ['PJ', 'PK', 'PL', 'PM', 'PH', 'DPL', 'DPK'],
    jsonFiles: {
      'PK': 'PKProducts.json', 'PL': 'PLProducts.json'
    }
  },
  tpu: {
    name: 'TPU Belts',
    apiEndpoint: '/api/tpu-belts',
    sections: ['5M', '8M', '8M RPP', 'S8M', '14M', 'XL', 'L', 'H', 'AT5', 'AT10', 'T10', 'AT20'],
    formulaType: 'size_divide_multiply', // rate = (size ÷ divisor) × multiplier
    defaultFormulas: {
      '5M': 2.50, '8M': 3.20, '8M RPP': 3.50, 'S8M': 4.00, '14M': 5.50,
      'XL': 2.80, 'L': 3.00, 'H': 3.20, 'AT5': 2.00, 'AT10': 3.80, 'T10': 4.00, 'AT20': 6.50
    },
    defaultDivisors: {
      '5M': 1, '8M': 1, '8M RPP': 1, 'S8M': 1, '14M': 1,
      'XL': 1, 'L': 1, 'H': 1, 'AT5': 1, 'AT10': 1, 'T10': 1, 'AT20': 1
    },
    sectionsWithDivisor: ['5M', '8M', '8M RPP', 'S8M', '14M', 'XL', 'L', 'H', 'AT5', 'AT10', 'T10', 'AT20'],
    jsonFiles: {
      '5M': 'TPU5MProducts.json', '8M': 'TPU8MProducts.json', 'S8M': 'TPUS8MProducts.json',
      'H': 'TPUHProducts.json', 'AT10': 'TPUAT10Products.json', 'T10': 'TPUT10Products.json',
      'AT20': 'TPUAT20Products.json'
    }
  }
}

// Current formulas (editable)
const formulas = ref<Record<string, number>>({})
const divisors = ref<Record<string, number>>({})

// Section counts and statistics
const sectionCounts = ref<Record<string, number>>({})
const totalProducts = ref(0)
const totalValue = ref(0)

// Computed properties for current belt type
const currentSections = computed(() => beltTypeConfig[selectedBeltType.value].sections)
const currentDefaultFormulas = computed(() => beltTypeConfig[selectedBeltType.value].defaultFormulas)
const currentDefaultDivisors = computed(() => beltTypeConfig[selectedBeltType.value].defaultDivisors)
const currentSectionsWithDivisor = computed(() => beltTypeConfig[selectedBeltType.value].sectionsWithDivisor)
const currentJsonFiles = computed(() => beltTypeConfig[selectedBeltType.value].jsonFiles)
const currentApiEndpoint = computed(() => beltTypeConfig[selectedBeltType.value].apiEndpoint)
const currentFormulaType = computed(() => beltTypeConfig[selectedBeltType.value].formulaType)

const activeSections = computed(() => {
  return Object.values(sectionCounts.value).filter(count => count > 0).length
})

const hasJsonFile = (section: string) => {
  return section in currentJsonFiles.value
}

// Check if section has divisor (now all sections have divisors)
const hasDivisor = (section: string) => {
  return true // All sections now have configurable divisors
}

// Get current formula text for display
const getCurrentFormulaText = (section: string) => {
  const multiplier = formulas.value[section] || currentDefaultFormulas.value[section] || 0
  const divisor = divisors.value[section] || currentDefaultDivisors.value[section] || (selectedBeltType.value === 'poly' ? 25.4 : 1)
  
  if (currentFormulaType.value === 'ribs_divide_multiply') {
    return `ribs÷${divisor}×${multiplier}`
  } else {
    return `size÷${divisor}×${multiplier}`
  }
}

// Belt type change handler
const onBeltTypeChange = () => {
  // Reset formulas and divisors to defaults for new belt type
  formulas.value = { ...currentDefaultFormulas.value }
  divisors.value = { ...currentDefaultDivisors.value }
  // Load statistics for new belt type
  loadStatistics()
}

// Load statistics
const loadStatistics = async () => {
  try {
    // Get all products without pagination for statistics
    const response = await axios.get(`${currentApiEndpoint.value}?per_page=10000`)
    
    // Handle different response formats
    let products = []
    if (selectedBeltType.value === 'tpu') {
      // TPU belts return simple array
      products = response.data
    } else {
      // Other belt types return paginated response
      products = response.data.data || []
    }
    
    // Reset counts
    sectionCounts.value = {}
    totalProducts.value = products.length
    
    // Calculate total value based on belt type
    if (selectedBeltType.value === 'poly') {
      // Poly belts use ribs × rate_per_rib
      totalValue.value = products.reduce((sum: number, p: any) => sum + Number(p.value), 0)
    } else {
      // Other belt types use standard value calculation
      totalValue.value = products.reduce((sum: number, p: any) => sum + Number(p.value), 0)
    }
    
    // Count by section
    products.forEach((product: any) => {
      sectionCounts.value[product.section] = (sectionCounts.value[product.section] || 0) + 1
    })
    
  } catch (err: any) {
    console.error('Statistics loading error:', err)
    showNotification('error', 'Error', `Failed to load statistics: ${err.response?.data?.message || err.message}`)
  }
}

// Update formula for specific section
const updateSectionFormula = async (section: string) => {
  if (!formulas.value[section] || formulas.value[section] <= 0) {
    showNotification('error', 'Invalid Formula', 'Formula multiplier must be greater than 0')
    return
  }
  
  // Validate divisor (now required for all sections)
  if (!divisors.value[section] || divisors.value[section] <= 0) {
    showNotification('error', 'Invalid Divisor', 'Divisor must be greater than 0')
    return
  }
  
  loading.value = true
  try {
    let formulaString = ''
    const divisor = divisors.value[section] || currentDefaultDivisors.value[section] || (selectedBeltType.value === 'poly' ? 25.4 : 1)
    
    if (currentFormulaType.value === 'ribs_divide_multiply') {
      formulaString = `ribs/${divisor}*${formulas.value[section]}`
    } else {
      formulaString = `size/${divisor}*${formulas.value[section]}`
    }
    
    // Update rate formula in database
    const response = await axios.post('/api/rate-formulas/update', {
      category: selectedBeltType.value === 'vee' ? 'vee_belts' : 
                selectedBeltType.value === 'cogged' ? 'cogged_belts' :
                selectedBeltType.value === 'poly' ? 'poly_belts' : 'tpu_belts',
      section,
      formula: formulaString
    })
    
    showNotification('success', 'Formula Updated', `Updated formula for ${section} section`)
    
    // Recalculate rates for this section
    await recalculateSectionRates(section)
    
  } catch (err: any) {
    showNotification('error', 'Update Failed', err.response?.data?.message || 'Failed to update formula')
  } finally {
    loading.value = false
  }
}

// Update all formulas
const updateAllFormulas = async () => {
  loading.value = true
  try {
    for (const section of currentSections.value) {
      if (formulas.value[section] && formulas.value[section] > 0) {
        await updateSectionFormula(section)
      }
    }
    showNotification('success', 'All Formulas Updated', 'Updated formulas for all sections')
  } finally {
    loading.value = false
  }
}

// Reset to default formulas
const resetFormulasToDefaults = () => {
  formulas.value = { ...currentDefaultFormulas.value }
  divisors.value = { ...currentDefaultDivisors.value }
  showNotification('success', 'Reset Complete', 'Formulas and divisors reset to default values')
}

// Recalculate rates for specific section
const recalculateSectionRates = async (section: string) => {
  try {
    await axios.post(`${currentApiEndpoint.value}/recalculate-section-rates`, {
      section
    })
    await loadStatistics()
  } catch (err: any) {
    console.error('Recalculation error:', err)
  }
}

// Recalculate all rates based on current formulas
const recalculateAllRates = async () => {
  if (!confirm('Recalculate all rates based on current formulas? This will update all product rates.')) return
  
  loading.value = true
  try {
    await axios.post(`${currentApiEndpoint.value}/recalculate-all-rates`)
    showNotification('success', 'Rates Recalculated', 'All rates recalculated based on formulas')
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Recalculation Failed', err.response?.data?.message || 'Failed to recalculate rates')
  } finally {
    loading.value = false
  }
}

// Seed specific section
const seedSection = async (section: string) => {
  if (!hasJsonFile(section)) {
    showNotification('error', 'No JSON File', `No JSON file available for ${section} section`)
    return
  }
  
  loading.value = true
  try {
    // Call the backend to seed from JSON file
    const response = await axios.post(`${currentApiEndpoint.value}/seed-section`, {
      section,
      filename: currentJsonFiles.value[section]
    })
    
    showNotification('success', 'Seeding Complete', `Seeded ${section} section from JSON`)
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Seeding Failed', err.response?.data?.message || 'Failed to seed section')
  } finally {
    loading.value = false
  }
}

// Clear specific section
const clearSection = async (section: string) => {
  if (!confirm(`Are you sure you want to clear all ${section} products?`)) return
  
  loading.value = true
  try {
    await axios.delete(`${currentApiEndpoint.value}/clear-section/${section}`)
    showNotification('success', 'Section Cleared', `Cleared all ${section} products`)
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Clear Failed', err.response?.data?.message || 'Failed to clear section')
  } finally {
    loading.value = false
  }
}

// Seed all sections
const seedAllSections = async () => {
  if (!confirm('Seed all sections from JSON files?')) return
  
  loading.value = true
  try {
    for (const section of Object.keys(currentJsonFiles.value)) {
      await seedSection(section)
    }
    showNotification('success', 'All Sections Seeded', 'Seeded all available sections')
  } finally {
    loading.value = false
  }
}

// Clear all data
const clearAllData = async () => {
  const beltTypeName = beltTypeConfig[selectedBeltType.value].name
  if (!confirm(`Are you sure you want to clear ALL ${beltTypeName} data?`)) return
  
  loading.value = true
  try {
    await axios.delete(`${currentApiEndpoint.value}/clear-all`)
    showNotification('success', 'All Data Cleared', `Cleared all ${beltTypeName} data`)
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Clear Failed', err.response?.data?.message || 'Failed to clear data')
  } finally {
    loading.value = false
  }
}

// Export all data
const exportAllData = async () => {
  try {
    const response = await axios.get(`${currentApiEndpoint.value}?per_page=10000`)
    
    // Handle different response formats
    let data = []
    if (selectedBeltType.value === 'tpu') {
      data = response.data
    } else {
      data = response.data.data || []
    }
    
    const beltTypeName = beltTypeConfig[selectedBeltType.value].name.toLowerCase().replace(' ', '-')
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${beltTypeName}-export-${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    
    showNotification('success', 'Export Complete', `Exported ${data.length} products`)
    
  } catch (err: any) {
    showNotification('error', 'Export Failed', 'Failed to export data')
  }
}

onMounted(() => {
  // Initialize with default formulas and divisors for TPU belts
  formulas.value = { ...currentDefaultFormulas.value }
  divisors.value = { ...currentDefaultDivisors.value }
  loadStatistics()
})
</script>