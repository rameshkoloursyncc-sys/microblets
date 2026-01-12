<template>
  <div class="transition-all duration-300" :class="props.sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
     <div class="z-30 bg-gray-50 dark:bg-gray-900 pb-4">

      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ title }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Click a cell to edit. Value = (rate × width ÷ 150) × meter
        </p>
      </div>

      <!-- Summary Stats -->
      <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Products</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ visibleProducts.length }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Meter</div>
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ totalMeter.toFixed(2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Value</div>
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">₹{{ totalValue.toFixed(2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Zero Meter Items</div>
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ zeroMeterCount }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-4 sticky top-14 bg-white dark:bg-gray-800 rounded-lg shadow-md p-3">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Search -->
          <input 
            v-model="searchTerm" 
            placeholder="Search section / width / meter" 
            class="px-3 py-1.5 text-sm border rounded bg-white dark:bg-gray-700 dark:text-white"
          />
          
          <!-- Quick Filter Buttons -->
          <button 
            @click="toggleZeroMeterFilter" 
            :class="showZeroMeterOnly ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm rounded hover:opacity-80 transition-colors"
          >
            {{ showZeroMeterOnly ? '✓ Zero Meter' : 'Zero Meter' }}
          </button>

          <!-- Date Range Filter -->
          <div class="flex items-center gap-1.5 ml-2">
            <label class="text-xs text-gray-600 dark:text-gray-400">From:</label>
            <input 
              v-model="dateFrom" 
              type="date" 
              class="px-2 py-1 border rounded bg-white dark:bg-gray-700 dark:text-white text-xs"
              :class="dateFrom ? 'border-blue-500' : ''"
            />
            <label class="text-xs text-gray-600 dark:text-gray-400">To:</label>
            <input 
              v-model="dateTo" 
              type="date" 
              class="px-2 py-1 border rounded bg-white dark:bg-gray-700 dark:text-white text-xs"
              :class="dateTo ? 'border-blue-500' : ''"
            />
            <button 
              v-if="dateFrom || dateTo"
              @click="clearDateFilter" 
              class="px-2 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600"
            >
              Clear
            </button>
          </div>
          
          <!-- JSON Import/Export Buttons -->
          <div class="ml-auto flex items-center gap-2">
            <button @click="showImportModal = true" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
              Import JSON
            </button>
            <button @click="downloadJSON" class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">
              Download JSON
            </button>
            <button @click="showCreateModal = true" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
              Create Product
            </button>
          </div>
        </div>
      </div>
     </div>
      <!-- Error State -->
      <div v-if="error && !loading" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <div>
            <p class="font-semibold text-red-800 dark:text-red-200">Error Loading Data</p>
            <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
          </div>
        </div>
        <button @click="fetchProducts" class="mt-2 px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">
          Retry
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Loading...</p>
      </div>
<!-- NEW -->
<div class="flex-1 bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
  <div class="overflow-y-auto max-h-[calc(100vh-400px)]">
    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
      <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase sticky top-0 z-20">
        <tr>
          <th class="py-3 px-3">Section</th>
                <th class="py-3 px-3">Width</th>
                <th class="py-3 px-3 text-center">Meter</th>
                <th class="py-3 px-3 text-right">Rate</th>
                <th class="py-3 px-3 text-right">Value</th>
                <th class="py-3 px-3">Remark</th>
                <th class="py-3 px-3 text-center">IN/OUT</th>
                <th class="py-3 px-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
   <tr v-for="p in visibleProducts" :key="p.id" class="border-t hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-section`">
                    <input v-model="editValue" @keyup.enter="saveCell(p, 'section')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'section')" class="cursor-pointer font-bold text-black dark:text-white">{{ p.section }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-width`">
                    <input v-model="editValue"  @keyup.enter="saveCell(p, 'width')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'width')" class="cursor-pointer font-bold text-black dark:text-white">{{ p.width }}</div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-meter`">
                    <input v-model.number="editValue" type="number" step="0.01" min="0"  @keyup.enter="saveCell(p, 'meter')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'meter')" class="cursor-pointer font-bold text-black dark:text-white">
                    <span :class="getMeterClass(p)" class="font-bold">{{ Number(p.meter).toFixed(2) }}</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-right">
                  <div v-if="editingCell === `${p.id}-rate`">
                    <input v-model.number="editValue" type="number" step="0.01"  @keyup.enter="saveCell(p, 'rate')" @keyup.esc="cancelEdit" class="w-24 p-1 border rounded text-right" />
                  </div>
                  <div v-else @click="startEdit(p, 'rate')" class="cursor-pointer">₹{{ Number(p.rate).toFixed(2) }}</div>
                </td>

                <td class="py-2 px-3 text-right font-medium text-green-600">₹{{ Number(p.value).toFixed(2) }}</td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-remark`">
                    <input v-model="editValue" @keyup.enter="saveCell(p, 'remark')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'remark')" class="cursor-pointer">{{ p.remark || '-' }}</div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button @click="showInOutModal(p, 'IN')" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                      IN
                    </button>
                    <button @click="showInOutModal(p, 'OUT')" class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                      OUT
                    </button>
                  </div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button @click="onDelete(p.id)" class="text-red-600 px-2 hover:text-red-800">Delete</button>
                    <button @click="showHistory(p)" class="text-blue-600 px-2 hover:text-blue-800">History</button>
                  </div>
                </td>
              </tr>
      </tbody>
    </table>
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

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-lg z-50">
          <h3 class="font-semibold mb-2">Create TPU Belt</h3>
          <div class="grid grid-cols-1 gap-2">
            <label>Section
              <input v-model="createForm.section" class="w-full p-2 border rounded" :placeholder="section || 'e.g., TS8M'" />
            </label>

            <label>Width
              <input v-model="createForm.width" class="w-full p-2 border rounded" placeholder="e.g., 150" />
            </label>

            <label>Meter (Inventory Quantity)
              <input v-model.number="createForm.meter" type="number" step="0.01" class="w-full p-2 border rounded" min="0" placeholder="Meter quantity in stock" />
            </label>

            <label>Rate
              <input v-model.number="createForm.rate" type="number" step="0.01" class="w-full p-2 border rounded" placeholder="Rate per unit" />
            </label>

            <label>Remark
              <textarea v-model="createForm.remark" class="w-full p-2 border rounded" rows="2"></textarea>
            </label>

            <div class="flex justify-end gap-2 mt-2">
              <button @click="showCreateModal = false" class="px-3 py-1">Cancel</button>
              <button @click="createProduct" class="px-3 py-1 bg-blue-600 text-white rounded" :disabled="loading">
                Create
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- IN/OUT Modal -->
      <div v-if="showInOutModalFlag" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showInOutModalFlag = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-md z-50">
          <h3 class="font-semibold mb-4">{{ inOutAction }} Operation</h3>
          
          <div v-if="selectedProduct" class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
            <div class="font-medium">{{ selectedProduct.section }} - {{ selectedProduct.width }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Current: {{ selectedProduct.meter }}</div>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium mb-1">Unit Type</label>
              <div class="flex gap-2">
                <label class="flex items-center">
                  <input v-model="inOutForm.unit_type" type="radio" value="meter" class="mr-2" />
                  Meter
                </label>
                <label class="flex items-center">
                  <input v-model="inOutForm.unit_type" type="radio" value="width" class="mr-2" />
                  Width
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Quantity</label>
              <input 
                v-model.number="inOutForm.quantity" 
                type="number" 
                step="0.01" 
                min="0.01"
                class="w-full p-2 border rounded" 
                placeholder="Enter quantity"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Remark (Optional)</label>
              <textarea v-model="inOutForm.remark" class="w-full p-2 border rounded" rows="2"></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-4">
            <button @click="showInOutModalFlag = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Cancel
            </button>
            <button 
              @click="performInOut" 
              class="px-4 py-2 text-white rounded hover:opacity-90"
              :class="inOutAction === 'IN' ? 'bg-green-600' : 'bg-red-600'"
              :disabled="loading || !inOutForm.quantity"
            >
              {{ inOutAction }}
            </button>
          </div>
        </div>
      </div>

      <!-- History Modal -->
      <div v-if="showHistoryModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showHistoryModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-3xl z-50 max-h-[80vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-4">
            <div>
              <h3 class="font-semibold text-lg">Transaction History</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400" v-if="selectedProduct">
                {{ selectedProduct.section }} - {{ selectedProduct.width }} ({{ selectedProduct.meter }})
              </p>
            </div>
            <button @click="showHistoryModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <span class="text-2xl">&times;</span>
            </button>
          </div>
          
          <div class="space-y-4">
            <div v-for="(transaction, index) in transactionHistory" :key="index" 
                 class="p-3 border rounded-lg" 
                 :class="{'bg-green-50 border-green-200': transaction.type === 'IN',
                         'bg-red-50 border-red-200': transaction.type === 'OUT',
                         'bg-blue-50 border-blue-200': transaction.type === 'EDIT'}">
              <div class="flex justify-between items-start">
                <div>
                  <span class="font-medium" :class="{
                    'text-green-700': transaction.type === 'IN',
                    'text-red-700': transaction.type === 'OUT',
                    'text-blue-700': transaction.type === 'EDIT'
                  }">{{ transaction.type }}</span>
                  <span class="text-sm text-gray-600 ml-2">
                    {{ new Date(transaction.created_at).toLocaleString() }}
                  </span>
                  <span v-if="transaction.user" class="text-sm text-gray-500 ml-2">
                    by {{ transaction.user.name }}
                  </span>
                </div>
                <div class="text-sm font-medium">
                  Meter: {{ transaction.stock_after }}
                </div>
              </div>
              <div class="mt-1 text-sm text-gray-600">
                {{ transaction.description }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Import JSON Modal -->
      <div v-if="showImportModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-2xl z-50">
          <h3 class="font-semibold mb-4">Import TPU Belts JSON</h3>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Expected JSON Format:</label>
            <pre class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-xs overflow-x-auto">{{ sampleJSONFormat }}</pre>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Paste JSON Data:</label>
            <textarea 
              v-model="importJSON" 
              class="w-full p-3 border rounded h-40 font-mono text-sm" 
              placeholder="Paste your JSON array here..."
            ></textarea>
          </div>

          <div class="mb-4">
            <label class="flex items-center gap-2">
              <input v-model="importMode" type="radio" value="append" />
              <span>Append to existing data</span>
            </label>
            <label class="flex items-center gap-2">
              <input v-model="importMode" type="radio" value="replace" />
              <span>Replace all existing data</span>
            </label>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="showImportModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Cancel
            </button>
            <button @click="importJSONData" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" :disabled="loading">
              Import
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useTpuBelts, type TpuBelt, type Transaction } from '../../composables/useTpuBelts'

const props = defineProps<{
  section?: string  // Optional: filter by specific section (TS8M, etc.)
  title?: string
  sidebarCollapsed?: boolean
  globalSearch?: string  // Universal search from sidebar
}>()

const {
  products,
  loading,
  error,
  fetchProducts,
  createProduct: apiCreateProduct,
  updateProduct: apiUpdateProduct,
  deleteProduct: apiDeleteProduct,
  bulkImport,
  inOutOperation,
  getTransactions,
} = useTpuBelts(props.section)

interface Notification { id: number; type: 'success'|'error'|'warning'; title: string; message: string }

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

const searchTerm = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const showZeroMeterOnly = ref(false)
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')
const savingCell = ref<string|null>(null)
const tableKey = ref(0) // Force re-render key

const showCreateModal = ref(false)
const createForm = ref({ 
  section: props.section || '',
  width: '', 
  meter: 0,
  rate: 0,
  remark: ''
})

// IN/OUT Modal
const showInOutModalFlag = ref(false)
const selectedProduct = ref<TpuBelt | null>(null)
const inOutAction = ref<'IN' | 'OUT'>('IN')
const inOutForm = ref({
  unit_type: 'meter' as 'width' | 'meter',
  quantity: 0,
  remark: ''
})

const showHistoryModal = ref(false)
const transactionHistory = ref<Transaction[]>([])

// Import/Export functionality
const showImportModal = ref(false)
const importJSON = ref('')
const importMode = ref('append')

const sampleJSONFormat = `Import Format (Simple):
[
  {
    "section": "5M",
    "width": 150,
    "meters": 31,
    "rate": 300,
    "remark": "Old Material"
  }
]

Download Format (Complete DB):
[
  {
    "id": 1,
    "section": "TS8M",
    "width": "150",
    "meter": 7.00,
    "in_meter": 0.00,
    "out_meter": 0.00,
    "rate": 500.00,
    "value": 2333.33,
    "remark": "Sample product",
    "sku": "TS8M-150-7.00M",
    "category": "TPU Belts",
    "created_by": null,
    "updated_by": null,
    "created_at": "2025-12-17T...",
    "updated_at": "2025-12-17T..."
  }
]`

const visibleProducts = computed(() => {
  let list = products.value.slice()
  
  // Search filter
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase().trim()
    
    // Check if search contains both section and width (space-separated)
    const searchParts = q.split(' ').filter(part => part.length > 0)
    
    if (searchParts.length >= 2) {
      // Combined search: exact match for section AND width
      const [sectionPart, widthPart] = searchParts
      list = list.filter(p => 
        p.section.toLowerCase() === sectionPart && 
        p.width.toLowerCase().includes(widthPart)
      )
    } else {
      // Single search term: match section OR width OR meter
      list = list.filter(p => 
        p.section.toLowerCase().includes(q) || 
        p.width.toLowerCase().includes(q) ||
        p.meter.toString().includes(q)
      )
    }
  }
  
  // Zero meter filter
  if (showZeroMeterOnly.value) {
    list = list.filter(p => p.meter === 0)
  }
  
  // Date filter
  if (dateFrom.value || dateTo.value) {
    list = list.filter(p => {
      const dateStr = p.updated_at || p.created_at
      if (!dateStr) return false
      
      const itemDate = new Date(dateStr)
      
      if (dateFrom.value) {
        const fromDate = new Date(dateFrom.value)
        fromDate.setHours(0, 0, 0, 0)
        if (itemDate < fromDate) return false
      }
      
      if (dateTo.value) {
        const toDate = new Date(dateTo.value)
        toDate.setHours(23, 59, 59, 999)
        if (itemDate > toDate) return false
      }
      
      return true
    })
  }
  
  return list
})

// Summary statistics
const totalMeter = computed(() => {
  return visibleProducts.value.reduce((sum, p) => sum + Number(p.meter), 0)
})

const totalValue = computed(() => {
  return visibleProducts.value.reduce((sum, p) => sum + Number(p.value), 0)
})

const zeroMeterCount = computed(() => {
  return visibleProducts.value.filter(p => p.meter === 0).length
})

const clearDateFilter = () => {
  dateFrom.value = ''
  dateTo.value = ''
}

const toggleZeroMeterFilter = () => {
  showZeroMeterOnly.value = !showZeroMeterOnly.value
}

const startEdit = (product: TpuBelt, field: keyof TpuBelt) => { 
  editingCell.value = `${product.id}-${String(field)}`
  editValue.value = String((product as any)[field] ?? '')
}

const cancelEdit = () => { 
  editingCell.value = null
  editValue.value = ''
  savingCell.value = null
}

const saveCell = async (product: TpuBelt, field: keyof TpuBelt) => {
  const cellId = `${product.id}-${String(field)}`
  
  // Prevent multiple saves for the same cell
  if (!editingCell.value || editingCell.value !== cellId || savingCell.value === cellId) {
    return
  }
  
  const val = ['meter', 'rate'].includes(field) ? Number(editValue.value) : editValue.value
  
  // Set saving state and clear editing state immediately to prevent double saves
  savingCell.value = cellId
  cancelEdit()
  
  try {
    await apiUpdateProduct(product.id, { [field]: val })
    showNotification('success', 'Updated', `Updated ${String(field)}`)
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Update failed')
  } finally {
    savingCell.value = null
  }
}

const getMeterClass = (p: TpuBelt) => { 
  if (p.meter <= 0) return 'text-red-600'
  return 'text-green-600'
}

const createProduct = async () => {
  try {
    await apiCreateProduct(createForm.value)
    showNotification('success', 'Created', 'TPU belt created successfully')
    showCreateModal.value = false
    createForm.value = { 
      section: props.section || '',
      width: '', 
      meter: 0,
      rate: 0,
      remark: ''
    }
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Creation failed')
  }
}

const onDelete = async (id: number) => { 
  if (!confirm('Delete TPU belt?')) return
  
  try {
    await apiDeleteProduct(id)
    showNotification('success', 'Deleted', 'TPU belt removed')
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Deletion failed')
  }
}

const showInOutModal = (product: TpuBelt, action: 'IN' | 'OUT') => {
  selectedProduct.value = product
  inOutAction.value = action
  inOutForm.value = {
    unit_type: 'meter',
    quantity: 0,
    remark: ''
  }
  showInOutModalFlag.value = true
}

const performInOut = async () => {
  if (!selectedProduct.value || !inOutForm.value.quantity) return
  
  // Prevent double clicks
  if (savingCell.value === 'modal-in-out') return
  
  savingCell.value = 'modal-in-out'

  try {
    const result = await inOutOperation({
      ids: [selectedProduct.value.id],
      action: inOutAction.value,
      quantity: inOutForm.value.quantity,
      unit_type: inOutForm.value.unit_type,
      remark: inOutForm.value.remark
    })
    
    // Force immediate data refresh
    await fetchProducts()
    
    // Force table re-render
    tableKey.value++
    
    // Update selected product with fresh data
    const updatedProduct = products.value.find(p => p.id === selectedProduct.value?.id)
    if (updatedProduct) {
      selectedProduct.value = updatedProduct
    }
    
    showNotification('success', `${inOutAction.value} Complete`, 
      `${inOutAction.value} ${inOutForm.value.quantity} ${inOutForm.value.unit_type} for ${selectedProduct.value.section}-${selectedProduct.value.width}`)
    
    showInOutModalFlag.value = false
    
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Operation failed')
  } finally {
    savingCell.value = null
  }
}

const showHistory = async (product: TpuBelt) => {
  selectedProduct.value = product
  try {
    transactionHistory.value = await getTransactions(product.id)
    showHistoryModal.value = true
  } catch (err: any) {
    showNotification('error', 'Error', 'Failed to load history')
  }
}

// Import JSON data
const importJSONData = async () => {
  if (!importJSON.value.trim()) {
    showNotification('error', 'Error', 'Please paste JSON data')
    return
  }

  try {
    const data = JSON.parse(importJSON.value)
    
    if (!Array.isArray(data)) {
      showNotification('error', 'Error', 'JSON must be an array')
      return
    }

    // Transform data to match our model structure
    const transformedData = data.map((item: any) => ({
      section: item.section,
      width: String(item.width),
      meter: Number(item.meters || item.meter), // Handle both "meters" and "meter"
      rate: Number(item.rate || 0),
      remark: item.remark || '',
    }))

    await bulkImport(transformedData, importMode.value as 'append' | 'replace')
    
    showNotification('success', 'Success', `Imported ${transformedData.length} products`)
    showImportModal.value = false
    importJSON.value = ''
    
  } catch (err: any) {
    console.error('Import error:', err)
    showNotification('error', 'Import Error', err.response?.data?.message || err.message || 'Invalid JSON format')
  }
}

// Download JSON data
const downloadJSON = () => {
  if (products.value.length === 0) {
    showNotification('warning', 'No Data', 'No products to download')
    return
  }

  // Export complete database records
  const exportData = products.value.map(product => ({
    id: product.id,
    section: product.section,
    width: product.width,
    meter: Number(product.meter),
    in_meter: Number(product.in_meter || 0),
    out_meter: Number(product.out_meter || 0),
    rate: Number(product.rate),
    value: Number(product.value),
    remark: product.remark,
    sku: product.sku,
    category: product.category,
    created_by: product.created_by,
    updated_by: product.updated_by,
    created_at: product.created_at,
    updated_at: product.updated_at
  }))

  // Create and download file
  const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `tpu-belts-${props.section || 'all'}-${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  showNotification('success', 'Downloaded', `Downloaded ${exportData.length} products`)
}

onMounted(async () => {
  console.log('TpuBeltTable mounted, section:', props.section, 'title:', props.title)
  try {
    await fetchProducts()
    console.log('TPU belts loaded:', products.value.length)
    
    if (props.globalSearch) {
      searchTerm.value = props.globalSearch
    }
  } catch (err) {
    console.error('Error loading TPU belts:', err)
  }
})

// Watch for section changes
watch(() => props.section, async (newSection) => {
  console.log('Section changed to:', newSection)
  await fetchProducts()
})

// Watch for globalSearch changes
watch(() => props.globalSearch, (newGlobalSearch) => {
  console.log('GlobalSearch changed to:', newGlobalSearch)
  if (newGlobalSearch) {
    searchTerm.value = newGlobalSearch
  } else {
    searchTerm.value = ''
  }
})
</script>