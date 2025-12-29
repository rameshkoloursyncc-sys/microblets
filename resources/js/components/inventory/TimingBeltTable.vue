<template>
  <div class="transition-all duration-300" :class="props.sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
      <div class="sticky top-14 z-30 bg-gray-50 dark:bg-gray-900 pb-4">

      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ title }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Timing Belt Inventory Management
        </p>
      </div>

      <!-- Summary Stats -->
      <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Products</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ visibleProducts.length }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Stock</div>
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ Number(totalStock || 0).toFixed(2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Value</div>
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">₹{{ Number(totalValue || 0).toFixed(2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Out of Stock</div>
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ outOfStockCount }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-md p-3">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Search -->
          <input 
            v-model="searchTerm" 
            placeholder="Search section / size" 
            class="px-3 py-1.5 text-sm border rounded bg-white dark:bg-gray-700 dark:text-white"
          />
          


          <!-- Quick Filter Buttons -->
          <button 
            @click="toggleOutOfStockFilter" 
            :class="showOutOfStockOnly ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm rounded hover:opacity-80 transition-colors"
          >
            {{ showOutOfStockOnly ? '✓ Out of Stock' : 'Out of Stock' }}
          </button>
          
          <!-- JSON Import/Export Buttons -->
          <div class="ml-auto flex items-center gap-2">
            <button @click="showImportModal = true" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
              Import Data
            </button>
            <button @click="downloadJSON" class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">
              Download JSON
            </button>
            <button @click="downloadExcel" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
              Download Excel
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

      <!-- Table -->
<div class="flex-1 bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
  <div class="overflow-y-auto max-h-[calc(100vh-400px)]">
    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
      <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase sticky top-0 z-20">
        <tr>
         <th class="py-3 px-3">Section</th>
                <th class="py-3 px-3">Size</th>
                <th class="py-3 px-3">TYPE 1 (FULL SLEEVE)</th>
                <th class="py-3 px-3 text-center">MM</th>
                <th class="py-3 px-3 text-center">Total MM</th>
                <th class="py-3 px-3 text-right">Value</th>
                <th class="py-3 px-3">Remark</th>
                <th class="py-3 px-3 text-center">IN/OUT</th>
                <th class="py-3 px-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
          <tr v-for="p in visibleProducts" :key="p?.id || 'unknown'" class="border-t hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-section`">
                    <input v-model="editValue" @blur="saveCell(p, 'section')" @keyup.enter="saveCell(p, 'section')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'section')" class="cursor-pointer font-bold text-black dark:text-white">{{ p?.section || '-' }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-size`">
                    <input v-model="editValue" @blur="saveCell(p, 'size')" @keyup.enter="saveCell(p, 'size')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'size')" class="cursor-pointer font-bold text-black dark:text-white">{{ p?.size || '-' }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-type`">
                    <select v-model="editValue" @blur="saveCell(p, 'type')" @keyup.enter="saveCell(p, 'type')" @keyup.esc="cancelEdit" class="w-full p-1 border font-bold rounded">
                      <option value="1 (FULL SLEEVE)">1 (FULL SLEEVE)</option>
                      <option value="2 (HALF SLEEVE)">2 (HALF SLEEVE)</option>
                    </select>
                  </div>
                  <div v-else @click="startEdit(p, 'type')" class="cursor-pointer font-bold text-black dark:text-white">{{ p?.type || '1 (FULL SLEEVE)' }}</div>
                </td>

                <!-- MM -->
                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p?.id}-mm`">
                    <input v-model.number="editValue" type="number" step="0.01" min="0" @blur="saveCell(p, 'mm')" @keyup.enter="saveCell(p, 'mm')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'mm')" class="cursor-pointer">
                    <span class="font-medium">{{ Number(p?.mm || 0).toFixed(2) }}mm</span>
                  </div>
                </td>

                <!-- Total MM -->
                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p?.id}-total_mm`">
                    <input v-model.number="editValue" type="number" step="0.01" min="0" @blur="saveCell(p, 'total_mm')" @keyup.enter="saveCell(p, 'total_mm')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'total_mm')" class="cursor-pointer">
                    <span :class="getStockClass(p)" class="font-medium">{{ Number(p?.total_mm || 0).toFixed(2) }}mm</span>
                  </div>
                </td>

                <!-- Value -->
                <td class="py-2 px-3 text-right font-medium text-green-600">₹{{ Number(p?.value || 0).toFixed(2) }}</td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-remark`">
                    <input v-model="editValue" @blur="saveCell(p, 'remark')" @keyup.enter="saveCell(p, 'remark')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'remark')" class="cursor-pointer">{{ p?.remark || '-' }}</div>
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
                    <button @click="onDelete(p?.id)" class="text-red-600 px-2 hover:text-red-800">Delete</button>
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
             :class="n && n.type === 'success' ? 'bg-green-100 text-green-800' : n && n.type === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
          <div class="font-semibold">{{ n?.title }}</div>
          <div class="text-sm">{{ n?.message }}</div>
        </div>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-lg z-50">
          <h3 class="font-semibold mb-2">Create Timing Belt</h3>
          <div class="grid grid-cols-1 gap-2">
            <label>Section
              <input v-model="createForm.section" class="w-full p-2 border rounded" placeholder="e.g., XL, L, H, 5M, 8M" />
            </label>

            <label>Size
              <input v-model="createForm.size" class="w-full p-2 border rounded" placeholder="e.g., 150, 200" />
            </label>

            <label>Type
              <select v-model="createForm.type" class="w-full p-2 border rounded">
                <option value="1 (FULL SLEEVE)">1 (FULL SLEEVE)</option>
                <option value="2 (HALF SLEEVE)">2 (HALF SLEEVE)</option>
              </select>
            </label>
            
            <label>MM (Individual piece length)
              <input v-model.number="createForm.mm" type="number" step="0.01" class="w-full p-2 border rounded" min="0" placeholder="Individual piece length in mm" />
            </label>
            
            <label>Total MM (Total inventory)
              <input v-model.number="createForm.total_mm" type="number" step="0.01" class="w-full p-2 border rounded" min="0" placeholder="Total inventory in mm" />
            </label>
            
            <label>Rate (per mm)
              <input v-model.number="createForm.rate" type="number" step="0.01" class="w-full p-2 border rounded" placeholder="Rate per mm" />
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
            <div class="font-medium">{{ selectedProduct?.section || 'Unknown' }} - {{ selectedProduct?.size || 'Unknown' }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              Current Stock: {{ selectedProduct?.total_mm || 0 }}mm
            </div>
          </div>

          <div class="space-y-3">
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
                {{ selectedProduct.section }} - {{ selectedProduct.size }}
              </p>
            </div>
            <button @click="showHistoryModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <span class="text-2xl">&times;</span>
            </button>
          </div>
          
          <div class="space-y-4">
            <div v-for="(transaction, index) in transactionHistory" :key="index" 
                 class="p-3 border rounded-lg" 
                 :class="{'bg-green-50 border-green-200': transaction?.type === 'IN',
                         'bg-red-50 border-red-200': transaction?.type === 'OUT',
                         'bg-blue-50 border-blue-200': transaction?.type === 'EDIT'}">
              <div class="flex justify-between items-start">
                <div>
                  <span class="font-medium" :class="{
                    'text-green-700': transaction?.type === 'IN',
                    'text-red-700': transaction?.type === 'OUT',
                    'text-blue-700': transaction?.type === 'EDIT'
                  }">{{ transaction?.type }}</span>
                  <span class="text-sm text-gray-600 ml-2">
                    {{ transaction?.created_at ? new Date(transaction.created_at).toLocaleString() : '' }}
                  </span>
                  <span v-if="transaction?.user" class="text-sm text-gray-500 ml-2">
                    by {{ transaction.user.name }}
                  </span>
                </div>
                <div class="text-sm font-medium">
                  Stock: {{ transaction?.stock_after }}
                </div>
              </div>
              <div class="mt-1 text-sm text-gray-600">
                {{ transaction?.description }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Import Data Modal -->
      <div v-if="showImportModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-4xl z-50 max-h-[90vh] overflow-y-auto">
          <h3 class="font-semibold mb-4">Import Timing Belts Data</h3>
          
          <!-- Import Type Selector -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Import Type:</label>
            <div class="flex gap-4">
              <label class="flex items-center">
                <input v-model="importType" type="radio" value="json" class="mr-2" />
                JSON Data
              </label>
              <label class="flex items-center">
                <input v-model="importType" type="radio" value="excel" class="mr-2" />
                Excel/CSV Data
              </label>
            </div>
          </div>

          <!-- JSON Import -->
          <div v-if="importType === 'json'" class="mb-4">
            <label class="block text-sm font-medium mb-2">Expected JSON Format:</label>
            <pre class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-xs overflow-x-auto mb-4">{{ sampleJSONFormat }}</pre>
            
            <label class="block text-sm font-medium mb-2">Paste JSON Data:</label>
            <textarea 
              v-model="importJSON" 
              class="w-full p-3 border rounded h-40 font-mono text-sm" 
              placeholder="Paste your JSON array here..."
            ></textarea>
          </div>

          <!-- Excel Import -->
          <div v-if="importType === 'excel'" class="mb-4">
            <label class="block text-sm font-medium mb-2">Expected Excel/CSV Format:</label>
            <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded text-xs overflow-x-auto mb-4">
              <table class="w-full text-xs">
                <thead>
                  <tr class="border-b">
                    <th class="text-left p-1">section</th>
                    <th class="text-left p-1">size</th>
                    <th class="text-left p-1">type</th>
                    <th class="text-left p-1">mm</th>
                    <th class="text-left p-1">total_mm</th>
                    <th class="text-left p-1">rate</th>
                    <th class="text-left p-1">remark</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="p-1">XL</td>
                    <td class="p-1">150</td>
                    <td class="p-1">1 (FULL SLEEVE)</td>
                    <td class="p-1">100.00</td>
                    <td class="p-1">1000.00</td>
                    <td class="p-1">2.50</td>
                    <td class="p-1">Sample</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <label class="block text-sm font-medium mb-2">Paste Excel/CSV Data:</label>
            <textarea 
              v-model="importExcel" 
              class="w-full p-3 border rounded h-40 font-mono text-sm" 
              placeholder="Paste your Excel data here (tab-separated or comma-separated)..."
            ></textarea>
            <p class="text-xs text-gray-500 mt-1">
              Copy from Excel and paste here. Headers should match the format above.
            </p>
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
            <button @click="importData" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700" :disabled="loading">
              Import
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useTimingBelts, type TimingBelt, type Transaction } from '../../composables/useTimingBelts'

const props = defineProps<{
  section?: string
  title?: string
  sidebarCollapsed?: boolean
  globalSearch?: string
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
  totalProducts,
  totalStock,
  totalValue,
  outOfStockCount
} = useTimingBelts(props.section)

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
const showOutOfStockOnly = ref(false)
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')

const showCreateModal = ref(false)
const createForm = ref({ 
  section: props.section || '',
  size: '', 
  type: '1 (FULL SLEEVE)',
  mm: 0,
  total_mm: 0,
  rate: 0,
  remark: ''
})

// IN/OUT Modal
const showInOutModalFlag = ref(false)
const selectedProduct = ref<TimingBelt | null>(null)
const inOutAction = ref<'IN' | 'OUT'>('IN')
const inOutForm = ref({
  quantity: 0,
  remark: ''
})

const showHistoryModal = ref(false)
const transactionHistory = ref<Transaction[]>([])

// Import/Export functionality
const showImportModal = ref(false)
const importJSON = ref('')
const importExcel = ref('')
const importType = ref('json')
const importMode = ref('append')

const sampleJSONFormat = `[
  {
    "section": "XL",
    "size": "150",
    "type": "1 (FULL SLEEVE)",
    "mm": 100.00,
    "total_mm": 1000.00,
    "rate": 2.50,
    "remark": "Sample timing belt"
  }
]`

const visibleProducts = computed(() => {
  let list = (products.value || []).slice()
  
  // Search filter
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase().trim()
    list = list.filter(p => 
      (p?.section || '').toLowerCase().includes(q) || 
      (p?.size || '').toLowerCase().includes(q)
    )
  }
  
  // Out of stock filter
  if (showOutOfStockOnly.value) {
    list = list.filter(p => {
      const currentStock = p?.total_mm || 0
      return currentStock <= 0
    })
  }
  
  return list.filter(p => p && p.id) // Ensure we only return valid products
})

const toggleOutOfStockFilter = () => {
  showOutOfStockOnly.value = !showOutOfStockOnly.value
}

const startEdit = (product: TimingBelt | null | undefined, field: keyof TimingBelt) => { 
  if (!product || !product.id) return
  editingCell.value = `${product.id}-${String(field)}`
  editValue.value = String((product as any)[field] ?? '')
}

const cancelEdit = () => { 
  editingCell.value = null
  editValue.value = ''
}

const saveCell = async (product: TimingBelt | null | undefined, field: keyof TimingBelt) => {
  if (!product || !product.id) {
    cancelEdit()
    return
  }
  
  const val = ['mm', 'total_mm', 'rate'].includes(field) ? Number(editValue.value) : editValue.value
  
  try {
    await apiUpdateProduct(product.id, { [field]: val })
    showNotification('success', 'Updated', `Updated ${String(field)}`)
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Update failed')
  }
  
  cancelEdit()
}

const getStockClass = (p: TimingBelt | null | undefined) => { 
  if (!p) return 'text-gray-400'
  const currentStock = p.total_mm || 0
  if (currentStock <= 0) return 'text-red-600'
  if (currentStock <= (p.reorder_level || 5)) return 'text-yellow-600'
  return 'text-blue-600'
}

const createProduct = async () => {
  try {
    await apiCreateProduct(createForm.value)
    showNotification('success', 'Created', 'Timing belt created successfully')
    showCreateModal.value = false
    createForm.value = { 
      section: props.section || '',
      size: '', 
      type: '1 (FULL SLEEVE)',
      mm: 0,
      total_mm: 0,
      rate: 0,
      remark: ''
    }
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Creation failed')
  }
}

const onDelete = async (id: number | undefined) => { 
  if (!id) return
  if (!confirm('Delete timing belt?')) return
  
  try {
    await apiDeleteProduct(id)
    showNotification('success', 'Deleted', 'Timing belt removed')
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Deletion failed')
  }
}

const showInOutModal = (product: TimingBelt | null | undefined, action: 'IN' | 'OUT') => {
  if (!product) return
  selectedProduct.value = product
  inOutAction.value = action
  inOutForm.value = {
    quantity: 0,
    remark: ''
  }
  showInOutModalFlag.value = true
}

const performInOut = async () => {
  if (!selectedProduct.value || !inOutForm.value.quantity) return

  try {
    const result = await inOutOperation({
      ids: [selectedProduct.value.id],
      action: inOutAction.value,
      quantity: inOutForm.value.quantity,
      remark: inOutForm.value.remark
    })
    
    showNotification('success', `${inOutAction.value} Complete`, 
      `${inOutAction.value} ${inOutForm.value.quantity} units for ${selectedProduct.value?.section}-${selectedProduct.value?.size}`)
    
    showInOutModalFlag.value = false
    
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Operation failed')
  }
}

const showHistory = async (product: TimingBelt | null | undefined) => {
  if (!product || !product.id) return
  selectedProduct.value = product
  try {
    transactionHistory.value = await getTransactions(product.id)
    showHistoryModal.value = true
  } catch (err: any) {
    showNotification('error', 'Error', 'Failed to load history')
  }
}

// Import JSON data
const importData = async () => {
  if (importType.value === 'json') {
    await importJSONData()
  } else {
    await importExcelData()
  }
}

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

    await bulkImport(data, importMode.value as 'append' | 'replace')
    
    showNotification('success', 'Success', `Imported ${data.length} products`)
    showImportModal.value = false
    importJSON.value = ''
    
  } catch (err: any) {
    console.error('Import error:', err)
    showNotification('error', 'Import Error', err.response?.data?.message || err.message || 'Invalid JSON format')
  }
}

const importExcelData = async () => {
  if (!importExcel.value.trim()) {
    showNotification('error', 'Error', 'Please paste Excel data')
    return
  }

  try {
    const lines = importExcel.value.trim().split('\n')
    if (lines.length < 2) {
      showNotification('error', 'Error', 'Excel data must have at least a header row and one data row')
      return
    }

    // Parse header row
    const headers = lines[0].split('\t').length > 1 ? 
      lines[0].split('\t').map(h => h.trim()) : 
      lines[0].split(',').map(h => h.trim())

    // Parse data rows
    const data = []
    for (let i = 1; i < lines.length; i++) {
      const values = lines[i].split('\t').length > 1 ? 
        lines[i].split('\t').map(v => v.trim()) : 
        lines[i].split(',').map(v => v.trim())
      
      if (values.length !== headers.length) continue

      const row: any = {}
      headers.forEach((header, index) => {
        const value = values[index]
        
        // Convert specific fields to appropriate types
        if (['mm', 'total_mm', 'rate'].includes(header)) {
          row[header] = value ? parseFloat(value) : null
        } else {
          row[header] = value || null
        }
      })

      // Validate required fields
      if (row.section && row.size) {
        data.push(row)
      }
    }

    if (data.length === 0) {
      showNotification('error', 'Error', 'No valid data rows found')
      return
    }

    await bulkImport(data, importMode.value as 'append' | 'replace')
    
    showNotification('success', 'Success', `Imported ${data.length} products from Excel data`)
    showImportModal.value = false
    importExcel.value = ''
    
  } catch (err: any) {
    console.error('Excel import error:', err)
    showNotification('error', 'Import Error', err.response?.data?.message || err.message || 'Failed to parse Excel data')
  }
}

// Download Excel data
const downloadExcel = () => {
  if (products.value.length === 0) {
    showNotification('warning', 'No Data', 'No products to download')
    return
  }

  // Create Excel-compatible CSV data
  const headers = [
    'section', 'size', 'type', 'mm', 'total_mm', 'in_mm', 'out_mm',
    'rate', 'value', 'reorder_level', 'remark', 'created_at', 'updated_at'
  ]

  const csvData = [
    headers.join(','),
    ...products.value.map(product => [
      product.section || '',
      product.size || '',
      product.type || '',
      product.mm || '',
      product.total_mm || '',
      product.in_mm || '',
      product.out_mm || '',
      product.rate || '',
      product.value || '',
      product.reorder_level || '',
      product.remark || '',
      product.created_at || '',
      product.updated_at || ''
    ].map(field => `"${field}"`).join(','))
  ].join('\n')

  const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `timing-belts-${props.section || 'all'}-${new Date().toISOString().split('T')[0]}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  showNotification('success', 'Downloaded', `Downloaded ${products.value.length} products as Excel file`)
}

// Download JSON data
const downloadJSON = () => {
  if (products.value.length === 0) {
    showNotification('warning', 'No Data', 'No products to download')
    return
  }

  const exportData = products.value.map(product => ({
    id: product.id,
    section: product.section,
    size: product.size,
    type: product.type,
    mm: product.mm,
    total_mm: product.total_mm,
    in_mm: product.in_mm,
    out_mm: product.out_mm,
    rate: product.rate,
    value: product.value,
    reorder_level: product.reorder_level,
    remark: product.remark,
    created_by: product.created_by,
    updated_by: product.updated_by,
    created_at: product.created_at,
    updated_at: product.updated_at
  }))

  const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `timing-belts-${props.section || 'all'}-${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  showNotification('success', 'Downloaded', `Downloaded ${exportData.length} products`)
}

onMounted(async () => {
  console.log('TimingBeltTable mounted, section:', props.section, 'title:', props.title)
  try {
    await fetchProducts()
    console.log('Timing belts loaded:', products.value.length)
    
    if (props.globalSearch) {
      searchTerm.value = props.globalSearch
    }
  } catch (err) {
    console.error('Error loading timing belts:', err)
  }
})

// Watch for section changes
watch(() => props.section, async (newSection) => {
  console.log('Section changed to:', newSection)
  await fetchProducts()
})

// Watch for globalSearch changes
watch(() => props.globalSearch, (newGlobalSearch) => {
  if (newGlobalSearch) {
    searchTerm.value = newGlobalSearch
  } else {
    searchTerm.value = ''
  }
})
</script>