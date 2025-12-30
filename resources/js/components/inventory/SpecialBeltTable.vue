<template>
  <div class="transition-all duration-300" :class="props.sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ title }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Special belts inventory management - pieces-based stock tracking
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
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ totalStock }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Value</div>
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">₹{{ totalValue.toFixed(2) }}</div>
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
            placeholder="Search section / size / type" 
            class="px-3 py-1.5 text-sm border rounded bg-white dark:bg-gray-700 dark:text-white"
          />
          
          <!-- Type Filter -->
          <select v-model="typeFilter" class="px-3 py-1.5 text-sm border rounded bg-white dark:bg-gray-700 dark:text-white">
            <option value="">All Types</option>
            <option value="Special">Special</option>
            <option value="Banded Cogged">Banded Cogged</option>
            <option value="Hybrid">Hybrid</option>
            <option value="Coating">Coating</option>
          </select>

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
      <div v-else class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
              <tr>
                <th class="py-3 px-3">Section</th>
                <th class="py-3 px-3">Size</th>
                <th class="py-3 px-3">Type</th>
                <th class="py-3 px-3 text-center">Balance Stock</th>
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
                    <input v-model="editValue" @blur="saveCell(p, 'section')" @keyup.enter="saveCell(p, 'section')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'section')" class="cursor-pointer">{{ p.section }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-size`">
                    <input v-model="editValue" @blur="saveCell(p, 'size')" @keyup.enter="saveCell(p, 'size')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'size')" class="cursor-pointer">{{ p.size }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-type`">
                    <select v-model="editValue" @blur="saveCell(p, 'type')" @keyup.enter="saveCell(p, 'type')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded">
                      <option value="Special">Special</option>
                      <option value="Banded Cogged">Banded Cogged</option>
                      <option value="Hybrid">Hybrid</option>
                      <option value="Coating">Coating</option>
                    </select>
                  </div>
                  <div v-else @click="startEdit(p, 'type')" class="cursor-pointer">
                    <span class="px-2 py-1 text-xs rounded" :class="getTypeClass(p.type)">
                      {{ p.type }}
                    </span>
                  </div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-balance_stock`">
                    <input v-model.number="editValue" type="number" min="0" @blur="saveCell(p, 'balance_stock')" @keyup.enter="saveCell(p, 'balance_stock')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'balance_stock')" class="cursor-pointer">
                    <span :class="getStockClass(p)" class="font-medium">{{ p.balance_stock }}</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-right">
                  <div v-if="editingCell === `${p.id}-rate`">
                    <input v-model.number="editValue" type="number" step="0.01" @blur="saveCell(p, 'rate')" @keyup.enter="saveCell(p, 'rate')" @keyup.esc="cancelEdit" class="w-24 p-1 border rounded text-right" />
                  </div>
                  <div v-else @click="startEdit(p, 'rate')" class="cursor-pointer">₹{{ Number(p.rate).toFixed(2) }}</div>
                </td>

                <td class="py-2 px-3 text-right font-medium text-green-600">₹{{ Number(p.value).toFixed(2) }}</td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-remark`">
                    <input v-model="editValue" @blur="saveCell(p, 'remark')" @keyup.enter="saveCell(p, 'remark')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
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
          <h3 class="font-semibold mb-2">Create Special Belt</h3>
          <div class="grid grid-cols-1 gap-2">
            <label>Section
              <input v-model="createForm.section" class="w-full p-2 border rounded" placeholder="e.g., Conical C, Harvester, RAX" />
            </label>

            <label>Size
              <input v-model="createForm.size" class="w-full p-2 border rounded" placeholder="e.g., 150, 200" />
            </label>

            <label>Type
              <select v-model="createForm.type" class="w-full p-2 border rounded">
                <option value="">Select Type</option>
                <option value="Special">Special</option>
                <option value="Banded Cogged">Banded Cogged</option>
                <option value="Hybrid">Hybrid</option>
                <option value="Coating">Coating</option>
              </select>
            </label>

            <label>Balance Stock (Pieces)
              <input v-model.number="createForm.balance_stock" type="number" class="w-full p-2 border rounded" min="0" placeholder="Stock quantity in pieces" />
            </label>

            <label>Rate
              <input v-model.number="createForm.rate" type="number" step="0.01" class="w-full p-2 border rounded" placeholder="Rate per piece" />
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
            <div class="font-medium">{{ selectedProduct.section }} - {{ selectedProduct.size }} ({{ selectedProduct.type }})</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Current Stock: {{ selectedProduct.balance_stock }} pieces</div>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium mb-1">Quantity (Pieces)</label>
              <input 
                v-model.number="inOutForm.quantity" 
                type="number" 
                min="1"
                class="w-full p-2 border rounded" 
                placeholder="Enter number of pieces"
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
                {{ selectedProduct.section }} - {{ selectedProduct.size }} ({{ selectedProduct.type }})
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
                  Stock: {{ transaction.stock_after }}
                </div>
              </div>
              <div class="mt-1 text-sm text-gray-600">
                {{ transaction.description }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Import Data Modal -->
      <div v-if="showImportModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-4xl z-50 max-h-[90vh] overflow-y-auto">
          <h3 class="font-semibold mb-4">Import Special Belts Data</h3>
          
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
                    <th class="text-left p-1">balance_stock</th>
                    <th class="text-left p-1">rate</th>
                    <th class="text-left p-1">remark</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="p-1">Conical C</td>
                    <td class="p-1">150</td>
                    <td class="p-1">Special</td>
                    <td class="p-1">25</td>
                    <td class="p-1">450.00</td>
                    <td class="p-1">Sample</td>
                  </tr>
                  <tr>
                    <td class="p-1">Harvester</td>
                    <td class="p-1">200</td>
                    <td class="p-1">Banded Cogged</td>
                    <td class="p-1">15</td>
                    <td class="p-1">650.00</td>
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
import { useSpecialBelts, type SpecialBelt, type Transaction } from '../../composables/useSpecialBelts'

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
} = useSpecialBelts(props.section)

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
const typeFilter = ref('')
const showOutOfStockOnly = ref(false)
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')
const savingCell = ref<string|null>(null)

const showCreateModal = ref(false)
const createForm = ref({ 
  section: props.section || '',
  size: '', 
  type: '',
  balance_stock: 0,
  rate: 0,
  remark: ''
})

// IN/OUT Modal
const showInOutModalFlag = ref(false)
const selectedProduct = ref<SpecialBelt | null>(null)
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
    "section": "Conical C",
    "size": "150",
    "type": "Special",
    "balance_stock": 25,
    "rate": 450.00,
    "remark": "Sample special belt"
  },
  {
    "section": "Harvester",
    "size": "200", 
    "type": "Banded Cogged",
    "balance_stock": 15,
    "rate": 650.00,
    "remark": "Sample harvester belt"
  }
]`

const visibleProducts = computed(() => {
  let list = products.value.slice()
  
  // Search filter
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase().trim()
    list = list.filter(p => 
      p.section.toLowerCase().includes(q) || 
      p.size.toLowerCase().includes(q) ||
      p.type.toLowerCase().includes(q)
    )
  }
  
  // Type filter
  if (typeFilter.value) {
    list = list.filter(p => p.type === typeFilter.value)
  }
  
  // Out of stock filter
  if (showOutOfStockOnly.value) {
    list = list.filter(p => p.balance_stock <= 0)
  }
  
  return list
})

const toggleOutOfStockFilter = () => {
  showOutOfStockOnly.value = !showOutOfStockOnly.value
}

const getTypeClass = (type: string) => {
  switch (type) {
    case 'Special': return 'bg-blue-100 text-blue-800'
    case 'Banded Cogged': return 'bg-green-100 text-green-800'
    case 'Hybrid': return 'bg-purple-100 text-purple-800'
    case 'Coating': return 'bg-orange-100 text-orange-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const startEdit = (product: SpecialBelt, field: keyof SpecialBelt) => { 
  editingCell.value = `${product.id}-${String(field)}`
  editValue.value = String((product as any)[field] ?? '')
}

const cancelEdit = () => { 
  editingCell.value = null
  editValue.value = ''
  savingCell.value = null
}

const saveCell = async (product: SpecialBelt, field: keyof SpecialBelt) => {
  const cellId = `${product.id}-${String(field)}`
  
  // Prevent multiple saves for the same cell
  if (!editingCell.value || editingCell.value !== cellId || savingCell.value === cellId) {
    return
  }
  
  const val = ['balance_stock', 'rate'].includes(field) ? Number(editValue.value) : editValue.value
  
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

const getStockClass = (p: SpecialBelt) => { 
  if (p.balance_stock <= 0) return 'text-red-600'
  if (p.balance_stock <= p.reorder_level) return 'text-yellow-600'
  return 'text-blue-600'
}

const createProduct = async () => {
  try {
    await apiCreateProduct(createForm.value)
    showNotification('success', 'Created', 'Special belt created successfully')
    showCreateModal.value = false
    createForm.value = { 
      section: props.section || '',
      size: '', 
      type: '',
      balance_stock: 0,
      rate: 0,
      remark: ''
    }
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Creation failed')
  }
}

const onDelete = async (id: number) => { 
  if (!confirm('Delete special belt?')) return
  
  try {
    await apiDeleteProduct(id)
    showNotification('success', 'Deleted', 'Special belt removed')
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Deletion failed')
  }
}

const showInOutModal = (product: SpecialBelt, action: 'IN' | 'OUT') => {
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
  
  // Prevent double clicks
  if (savingCell.value === 'modal-in-out') return
  
  savingCell.value = 'modal-in-out'

  try {
    const result = await inOutOperation({
      ids: [selectedProduct.value.id],
      action: inOutAction.value,
      quantity: inOutForm.value.quantity,
      remark: inOutForm.value.remark
    })
    
    showNotification('success', `${inOutAction.value} Complete`, 
      `${inOutAction.value} ${inOutForm.value.quantity} pieces for ${selectedProduct.value.section}-${selectedProduct.value.size}`)
    
    showInOutModalFlag.value = false
    
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Operation failed')
  } finally {
    savingCell.value = null
  }
}

const showHistory = async (product: SpecialBelt) => {
  selectedProduct.value = product
  try {
    transactionHistory.value = await getTransactions(product.id)
    showHistoryModal.value = true
  } catch (err: any) {
    showNotification('error', 'Error', 'Failed to load history')
  }
}

// Import data
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
        if (['rate'].includes(header)) {
          row[header] = value ? parseFloat(value) : 0
        } else if (['balance_stock'].includes(header)) {
          row[header] = value ? parseInt(value) : 0
        } else {
          row[header] = value || ''
        }
      })

      // Validate required fields
      if (row.section && row.size && row.type) {
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
    'section', 'size', 'type', 'balance_stock', 'in_stock', 'out_stock',
    'reorder_level', 'rate', 'value', 'remark', 'created_at', 'updated_at'
  ]

  const csvData = [
    headers.join(','),
    ...products.value.map(product => [
      product.section || '',
      product.size || '',
      product.type || '',
      product.balance_stock || '',
      product.in_stock || '',
      product.out_stock || '',
      product.reorder_level || '',
      product.rate || '',
      product.value || '',
      product.remark || '',
      product.created_at || '',
      product.updated_at || ''
    ].map(field => `"${field}"`).join(','))
  ].join('\n')

  const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `special-belts-${props.section || 'all'}-${new Date().toISOString().split('T')[0]}.csv`
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
    balance_stock: product.balance_stock,
    in_stock: product.in_stock,
    out_stock: product.out_stock,
    reorder_level: product.reorder_level,
    rate: product.rate,
    value: product.value,
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
  link.download = `special-belts-${props.section || 'all'}-${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  showNotification('success', 'Downloaded', `Downloaded ${exportData.length} products`)
}

onMounted(async () => {
  console.log('SpecialBeltTable mounted, section:', props.section, 'title:', props.title)
  try {
    await fetchProducts()
    console.log('Special belts loaded:', products.value.length)
    
    if (props.globalSearch) {
      searchTerm.value = props.globalSearch
    }
  } catch (err) {
    console.error('Error loading special belts:', err)
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