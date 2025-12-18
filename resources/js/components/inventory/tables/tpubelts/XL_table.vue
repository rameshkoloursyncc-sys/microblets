<template>
  <div class="transition-all duration-300" :class="sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ SECTION_TITLE }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Click a cell to edit. Use category filter to view SPA or Timing Belt items.
        </p>
        
        <!-- Global Search Indicator -->
        <div v-if="props.globalSectionQuery || props.globalSizeQuery" class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                Combined Search Active:
                <span v-if="props.globalSectionQuery"> Section: "{{ props.globalSectionQuery }}"</span>
                <span v-if="props.globalSectionQuery && props.globalSizeQuery"> + </span>
                <span v-if="props.globalSizeQuery"> Size: "{{ props.globalSizeQuery }}"</span>
              </span>
            </div>
            <button 
              @click="clearGlobalSearch"
              class="text-blue-600 hover:text-blue-800 text-sm font-medium"
            >
              Clear Search
            </button>
          </div>
        </div>
      </div>

      <!-- Excel Upload Component -->
 <!--   <ExcelUpload @data-uploaded="data => processUploadedData(data, { replace: false })" /> -->

      <!-- JSON Paste Import -->
  <!--      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-4">
        <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Paste JSON Data</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
          Paste an array of objects with keys: section, size, balanceStock, rate, value (optional), reorder_level (optional).
        </p>
        <textarea
          v-model="jsonInput"
          rows="4"
          class="w-full p-2 border rounded bg-white dark:bg-gray-900 text-sm font-mono"
          placeholder='[{"section":"A","size":"18","balanceStock":0,"rate":18.9,"value":0}]'
        ></textarea>
        <div class="flex gap-2 mt-2">
          <button @click="importJson('append')" class="px-3 py-1 bg-blue-600 text-white rounded">Append</button>
          <button @click="importJson('replace')" class="px-3 py-1 bg-red-600 text-white rounded">Replace</button>
        </div>
      </div>   -->
      <!-- Filters -->
      <div class="mb-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <!-- Show full filters only when not in section-specific view -->
        <div v-if="!props.initialCategories" class="flex flex-wrap items-center gap-3">
          <select v-model="selectedCategory" class="p-2 border w-36 rounded bg-white dark:bg-gray-700">
            <option value="">All Categories</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
          <input v-model="searchTerm" placeholder="Search name / size" class="p-2 border rounded" />
        </div>

        <!-- Simplified filters for section-specific view -->
        <div v-else class="flex flex-wrap items-center gap-3">
          <input v-model="searchTerm" placeholder="Search name / size" class="p-2 border rounded" />
          
          <!-- Date Range Filter -->
          <div class="flex items-center gap-2">
            <label class="text-sm text-gray-700 dark:text-gray-200">From:</label>
            <input 
              v-model="dateFrom" 
              type="date" 
              class="p-2 border rounded bg-white dark:bg-gray-700"
            />
            <label class="text-sm text-gray-700 dark:text-gray-200">To:</label>
            <input 
              v-model="dateTo" 
              type="date" 
              class="p-2 border rounded bg-white dark:bg-gray-700"
            />
            <button 
              @click="clearDateFilter" 
              class="px-3 py-2 text-sm bg-gray-500 text-white rounded hover:bg-gray-600"
            >
              Clear
            </button>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded">Create Product</button>
          <button @click="openInoutModal" class="px-4 py-2 bg-indigo-600 text-white rounded">IN/OUT</button>
          <button @click="downloadJson" class="px-4 py-2 bg-green-600 text-white rounded">Download JSON</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
              <tr>
                <th class="py-3 px-3">WIDTH</th>
                <th class="py-3 px-3">METER</th>
                <th class="py-3 px-3 text-center">Balance Stock</th>
                <th class="py-3 px-3 text-center">Min Inventory</th>
                <th class="py-3 px-3 text-right">Rate</th>
                <th class="py-3 px-3 text-right">Value</th>
                <th class="py-3 px-3 text-center">IN</th>
                <th class="py-3 px-3 text-center">OUT</th>
                <th class="py-3 px-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in visibleProducts" :key="p.id" class="border-t hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-section`">
                    <input v-model="editValue" @blur="saveCell(p, 'section')" @keyup.enter="saveCell(p, 'section')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'section')" class="cursor-pointer">{{ p.section || p.name }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-size`">
                    <input v-model="editValue" @blur="saveCell(p, 'size')" @keyup.enter="saveCell(p, 'size')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'size')" class="cursor-pointer">{{ p.size }}</div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-stock`">
                    <input v-model.number="editValue" type="number" @blur="saveCell(p, 'stock')" @keyup.enter="saveCell(p, 'stock')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'stock')" class="cursor-pointer">
                    <span :class="getStockClass(p)">{{ p.stock }}</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-reorder_level`">
                    <input v-model.number="editValue" type="number" min="1" 
                           @blur="saveCell(p, 'reorder_level')" 
                           @keyup.enter="saveCell(p, 'reorder_level')" 
                           @keyup.esc="cancelEdit" 
                           class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'reorder_level')" class="cursor-pointer">{{ p.reorder_level }}</div>
                </td>

                <td class="py-2 px-3 text-right">
                  <div v-if="editingCell === `${p.id}-rate`">
                    <input v-model.number="editValue" type="number" step="0.01" @blur="saveCell(p, 'rate')" @keyup.enter="saveCell(p, 'rate')" @keyup.esc="cancelEdit" class="w-24 p-1 border rounded text-right" />
                  </div>
                  <div v-else @click="startEdit(p, 'rate')" class="cursor-pointer">₹{{ p.rate.toFixed(2) }}</div>
                </td>

                <td class="py-2 px-3 text-right">₹{{ p.value.toFixed(2) }}</td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-in_qty`">
                    <input v-model.number="editValue" type="number" @blur="saveCell(p, 'in_qty')" @keyup.enter="saveCell(p, 'in_qty')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'in_qty')" class="cursor-pointer">{{ p.in_qty ?? '' }}</div>
                </td>
                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-out_qty`">
                    <input v-model.number="editValue" type="number" @blur="saveCell(p, 'out_qty')" @keyup.enter="saveCell(p, 'out_qty')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'out_qty')" class="cursor-pointer">{{ p.out_qty ?? '' }}</div>
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

      <!-- History Modal -->
      <div v-if="showHistoryModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showHistoryModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-3xl z-50">
          <div class="flex justify-between items-center mb-4">
            <div>
              <h3 class="font-semibold text-lg">Transaction History</h3>
              <p class="text-sm text-gray-600" v-if="selectedProduct">
                {{ selectedProduct.name }} - {{ selectedProduct.size }}
              </p>
            </div>
            <button @click="showHistoryModal = false" class="text-gray-500 hover:text-gray-700">
              <span class="text-2xl">&times;</span>
            </button>
          </div>
          
          <div class="max-h-[60vh] overflow-y-auto">
            <div v-if="selectedProduct && transactionHistory.length" class="space-y-4">
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
                      {{ new Date(transaction.timestamp).toLocaleString() }}
                    </span>
                  </div>
                  <div class="text-sm font-medium">
                    Balance Stock: {{ transaction.stockAfter }}
                  </div>
                </div>
                <div class="mt-1 text-sm text-gray-600">
                  {{ transaction.description }}
                </div>
                <div class="mt-2 text-sm">
                  <span class="text-gray-600">Value after transaction: ₹{{ (transaction.stockAfter * transaction.rate).toFixed(2) }}</span>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-gray-600 py-8">
              No transaction history available
            </div>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-lg z-50">
          <h3 class="font-semibold mb-2">Create Product</h3>
          <div class="grid grid-cols-1 gap-2">
            <label>Section
              <select v-model="createForm.category" class="w-full p-2 border rounded">
                <option v-for="c in availableCategories" :key="c" :value="c">{{ c }}</option>
              </select>
            </label>

            <label>Section Name
              <input v-model="createForm.name" class="w-full p-2 border rounded" placeholder="Product name (e.g., SPA or Timing Belt)" />
            </label>

            <label>Size
              <input v-model="createForm.size" class="w-full p-2 border rounded" placeholder="Enter size" />
            </label>

            <label>Balance Stock
              <input v-model.number="createForm.stock" type="number" class="w-full p-2 border rounded" min="0" />
            </label>

            <label>Minimum Inventory Level
              <input v-model.number="createForm.reorder_level" type="number" class="w-full p-2 border rounded" min="1" required />
              <small class="text-gray-500">Minimum value: 1</small>
            </label>

            <label>Rate per item
              <input v-model.number="createForm.rate" type="number" step="0.01" class="w-full p-2 border rounded" />
            </label>

            <div class="flex justify-end gap-2 mt-2">
              <button @click="showCreateModal = false" class="px-3 py-1">Cancel</button>
              <button @click="createProduct" class="px-3 py-1 bg-blue-600 text-white rounded">Create</button>
            </div>
          </div>
        </div>
      </div>

      <!-- IN/OUT Modal -->
      <div v-if="showInoutModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showInoutModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-4xl z-50">
          <h3 class="font-semibold mb-2">IN / OUT</h3>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
            <input v-model="inoutFilters.name" placeholder="Name" class="p-2 border rounded" />
            <input v-model="inoutFilters.skuSize" placeholder="SKU-Size" class="p-2 border rounded" />
            <input v-model.number="inoutFilters.stockMin" placeholder="Stock min" type="number" class="p-2 border rounded" />
            <input v-model.number="inoutFilters.stockMax" placeholder="Stock max" type="number" class="p-2 border rounded" />
          </div>

          <div class="max-h-64 overflow-auto mb-3 border rounded">
            <table class="w-full text-sm">
              <thead class="bg-gray-100"><tr><th class="py-2 px-3"><input type="checkbox" @change="$event.target.checked ? selectAll() : clearSelection()" /></th><th class="py-2 px-3">Name</th><th class="py-2 px-3">SKU-Size</th><th class="py-2 px-3 text-center">Stock</th></tr></thead>
              <tbody>
                <tr v-for="p in filteredForInout" :key="p.id" class="border-t">
                  <td class="py-2 px-3"><input type="checkbox" :value="p.id" v-model="selectedIds" /></td>
                  <td class="py-2 px-3">{{ p.name }}</td>
                  <td class="py-2 px-3"><span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded">{{ p.sku }}-{{ p.size }}</span></td>
                  <td class="py-2 px-3 text-center">{{ p.stock }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="markSelected('IN')" class="px-3 py-1 bg-green-600 text-white rounded">Mark IN</button>
            <button @click="markSelected('OUT')" class="px-3 py-1 bg-red-600 text-white rounded">Mark OUT</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { fetchInventoryProducts, type InventoryProduct } from '../../../../lib/api/inventoryApi'
import ExcelUpload from './ExcelUpload.vue'

const props = defineProps<{
  title?: string
  initialCategory?: string
  initialCategories?: string[]
  globalSectionQuery?: string
  globalSizeQuery?: string
}>()

// Override defaults for A Section
const SECTION_NAME = 'XL'
const SECTION_TITLE = 'XL Section Inventory'

const emit = defineEmits(['clear-global-search'])

interface Product extends InventoryProduct {}
interface Notification { id: number; type: 'success'|'error'|'warning'; title: string; message: string }

const categories = [
  // Vee Belts - Classical Sections
  'A Section', 'B Section', 'C Section', 'D Section', 'E Section',
  // Vee Belts - Wedge Sections  
  'SPA Section', 'SPB Section', 'SPC Section', 'SPZ Section',
  // Vee Belts - Narrow Sections
  '3V Section', '5V Section', '8V Section',
  // Cogged Belts - Classical Sections
  'AX Section', 'BX Section', 'CX Section',
  // Cogged Belts - Wedge Sections
  'XPA Section', 'XPB Section', 'XPC Section', 'XPZ Section',
  // Cogged Belts - Narrow Sections
  '3VX Section', '5VX Section', '8VX Section',
  // Poly V-Belts
  'PJ Section', 'PK Section', 'PL Section', 'PM Section', 'PH Section',
  // Poly V-Belts - Double Side
  'DPL Section', 'DPK Section',
  // Timing Belts - Classical
  'XL Section', 'L Section', 'H Section', 'XH Section', 'T5 Section', 'T10 Section',
  // Timing Belts - HTD
  '5M Section', '8M Section', '14M Section',
  // Timing Belts - Double Side
  'DL Section', 'DH Section', 'D5M Section', 'D8M Section',
  // TPU Belt Open
  'TPU 5M Section', 'TPU 8M Section', 'TPU 8M RPP Section', 'TPU S8M Section', 'TPU 14M Section', 'TPU XL Section', 'TPU L Section', 'TPU H Section', 'TPU AT5 Section', 'TPU AT10 Section', 'TPU T10 Section', 'TPU AT20 Section',
  // Special Belts - Vee Belts Special
  'Conical C Section', 'Harvester Section',
  // Special Belts - Banded Cogged
  'RAX Section', 'RBX Section', 'R3VX Section', 'R5VX Section',
  // Special Belts - Hybrid
  '8M PK Section', '8M PL Section',
  // Special Belts - Coating
  'Poly Coating Section', 'Flat Coating Section', 'Timing Coating Section'
]

const notifications = ref<Notification[]>([])
const jsonInput = ref('')
let notificationId = 0
const showNotification = (type: Notification['type'], title: string, message: string, timeout = 5000) => { 
  const id = ++notificationId
  notifications.value.push({ id, type, title, message })
  if (timeout > 0) setTimeout(() => removeNotification(id), timeout)
}
const removeNotification = (id: number) => { 
  notifications.value = notifications.value.filter(n => n.id !== id)
}

// Use section-specific storage keys - hardcoded for A Section
const STORAGE_KEY = computed(() => `microbelts_products_${SECTION_NAME}_v1`)
const HISTORY_KEY = computed(() => `inventory_history_${SECTION_NAME}`)
const products = ref<Product[]>([])
const loading = ref(false)
const searchTerm = ref('')
const selectedCategory = ref(props.initialCategory ?? '')
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')
const originalValue = ref<any>('')

const showCreateModal = ref(false)
const createForm = ref({ 
  category: categories[0], 
  section: '',
  name: '', 
  sku: '', 
  size: '', 
  stock: 0, 
  reorder_level: 5, 
  rate: 0, 
  value: 0 
})

const showInoutModal = ref(false)
const inoutFilters = ref({ 
  name: '', 
  skuSize: '', 
  stockMin: null as number|null, 
  stockMax: null as number|null 
})
const selectedIds = ref<number[]>([])

const sidebarCollapsed = ref(false)
const dateFrom = ref('')
const dateTo = ref('')

interface Transaction {
  productId: number
  timestamp: number
  type: 'IN' | 'OUT' | 'EDIT'
  description: string
  stockBefore: number
  stockAfter: number
  rate: number
  quantity?: number
}

const showHistoryModal = ref(false)
const selectedProduct = ref<Product | null>(null)
const transactionHistory = ref<Transaction[]>([])

const persistProducts = () => localStorage.setItem(STORAGE_KEY.value, JSON.stringify(products.value))

const loadProducts = async () => {
  loading.value = true
  console.log('=== A_table loadProducts called ===')
  
  // Force A Section for this component
  const sectionName = SECTION_NAME
  console.log('Loading section:', sectionName)
  console.log('STORAGE_KEY:', STORAGE_KEY.value)
  
  try {
    // Check localStorage first for this specific section
    const raw = localStorage.getItem(STORAGE_KEY.value)
    if (raw) {
      try {
        const parsed = JSON.parse(raw)
        if (parsed && parsed.length > 0) {
          console.log(`Loaded ${parsed.length} products from localStorage for ${sectionName}`)
          products.value = parsed
          loading.value = false
          return
        }
      } catch (e) {
        console.warn('Failed to parse localStorage', e)
      }
    }
    
    // If localStorage is empty, try to load from JSON file
    try {
      console.log(`Attempting to load: ../../../../mock/${sectionName}Products.json`)
      const mockData = await import(`../../../../mock/${sectionName}Products.json`)
      products.value = mockData.default || []
      console.log(`Loaded ${products.value.length} products from JSON file for ${sectionName}`)
      persistProducts() // Save to localStorage for next time
      loading.value = false
      return
    } catch (error) {
      console.warn(`No mock file for ${sectionName}`, error)
      // If no JSON file exists, start with empty array
      products.value = []
      loading.value = false
      return
    }
  } finally {
    loading.value = false
    console.log('=== A_table loadProducts finished ===')
    console.log('Final products count:', products.value.length)
  }
}

const clearDateFilter = () => {
  dateFrom.value = ''
  dateTo.value = ''
}

const availableCategories = computed(() => {
  // Always return A Section for this component
  return [`${SECTION_NAME} Section`]
})

const visibleProducts = computed(() => {
  let list = products.value.slice()
  
  // Debug logging
  console.log('=== A_table visibleProducts Debug ===')
  console.log('Total products:', list.length)
  console.log('Section:', SECTION_NAME)
  
  // Log all unique categories in products
  const uniqueCategories = [...new Set(list.map(p => p.category))]
  console.log('Unique categories in products:', uniqueCategories)
  
  // Always filter by A Section
  const targetCategory = `${SECTION_NAME} Section`
  const beforeFilter = list.length
  list = list.filter(p => p.category === targetCategory)
  console.log(`Filtered by ${targetCategory}: ${beforeFilter} -> ${list.length}`)
  
  if (props.initialCategories && (dateFrom.value || dateTo.value)) {
    const fromDate = dateFrom.value ? new Date(dateFrom.value).getTime() : 0
    const toDate = dateTo.value ? new Date(dateTo.value).setHours(23, 59, 59, 999) : Date.now()
    
    const dateRangeTransactionProductIds = new Set()
    const allTransactions = JSON.parse(localStorage.getItem(HISTORY_KEY.value) || '[]')
    
    allTransactions.forEach((transaction: Transaction) => {
      if (transaction.timestamp >= fromDate && transaction.timestamp <= toDate) {
        dateRangeTransactionProductIds.add(transaction.productId)
      }
    })
    
    const beforeDateFilter = list.length
    list = list.filter(p => dateRangeTransactionProductIds.has(p.id))
    console.log(`Filtered by date range: ${beforeDateFilter} -> ${list.length}`)
  }
  
  // Apply combined exact search from universal search bars
  if (props.globalSectionQuery || props.globalSizeQuery) {
    const beforeCombinedSearch = list.length
    
    list = list.filter(p => {
      let sectionMatch = true
      let sizeMatch = true
      
      // Exact section match (if section query provided)
      if (props.globalSectionQuery && props.globalSectionQuery.trim()) {
        const sectionQuery = props.globalSectionQuery.toLowerCase()
        // Check the separate section field first, then fallback to name/category for backward compatibility
        sectionMatch = (p.section && p.section.toLowerCase() === sectionQuery) ||
                      p.name.toLowerCase() === sectionQuery || 
                      p.category.toLowerCase().includes(sectionQuery)
      }
      
      // Exact size match (if size query provided)
      if (props.globalSizeQuery && props.globalSizeQuery.trim()) {
        const sizeQuery = props.globalSizeQuery.toLowerCase()
        // Check both string and number comparison for size
        sizeMatch = p.size.toLowerCase() === sizeQuery || 
                   String(p.size) === props.globalSizeQuery.trim()
      }
      
      // Both conditions must be true (AND logic)
      return sectionMatch && sizeMatch
    })
    
    console.log(`Filtered by combined exact search (Section: "${props.globalSectionQuery}", Size: "${props.globalSizeQuery}"): ${beforeCombinedSearch} -> ${list.length}`)
  }
  
  // Apply local search term
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    const beforeSearch = list.length
    list = list.filter(p => p.name.toLowerCase().includes(q) || p.size.toLowerCase().includes(q))
    console.log(`Filtered by local search: ${beforeSearch} -> ${list.length}`)
  }
  
  list.forEach(p => {
    p.value = p.stock * p.rate
  })
  
  console.log('Final visible products:', list.length)
  console.log('=== End Debug ===')
  
  return list
})

const loadTransactionHistory = () => {
  const raw = localStorage.getItem(HISTORY_KEY.value)
  if (raw) {
    try {
      transactionHistory.value = JSON.parse(raw)
    } catch (e) {
      console.warn('Failed to parse transaction history', e)
      transactionHistory.value = []
    }
  }
}

const saveTransactionHistory = () => {
  localStorage.setItem(HISTORY_KEY.value, JSON.stringify(transactionHistory.value))
}

const addTransaction = (transaction: Transaction) => {
  transactionHistory.value.unshift(transaction)
  saveTransactionHistory()
}

const showHistory = (product: Product) => {
  selectedProduct.value = product
  transactionHistory.value = transactionHistory.value
    .filter(t => t.productId === product.id)
    .sort((a, b) => b.timestamp - a.timestamp)
  showHistoryModal.value = true
}

const selectAll = () => { selectedIds.value = filteredForInout.value.map(p => p.id) }
const clearSelection = () => { selectedIds.value = [] }

const markSelected = (action: 'IN'|'OUT') => {
  if (!selectedIds.value.length) { 
    showNotification('warning', 'No Selection', 'Please select items')
    return 
  }
  const qtyStr = prompt(`Quantity to ${action}`, '1')
  const qty = Math.max(0, Number(qtyStr || 0))
  if (!qty) { 
    showNotification('warning', 'Invalid', 'Quantity must be > 0')
    return 
  }

  for (const id of selectedIds.value) {
    const idx = products.value.findIndex(p => p.id === id)
    if (idx === -1) continue
    const p = products.value[idx]
    
    if (action === 'OUT' && p.stock < qty) { 
      showNotification('error', 'Insufficient', `${p.name} (Size: ${p.size}) has only ${p.stock} units in stock`)
      continue 
    }
    
    const oldStock = p.stock
    
    if (action === 'IN') { 
      p.stock += qty
      p.in_qty = 0
    } else { 
      p.stock -= qty
      p.out_qty = 0
    }
    
    addTransaction({
      productId: p.id,
      timestamp: Date.now(),
      type: action,
      description: `${action === 'IN' ? 'Added' : 'Removed'} ${qty} units${action === 'OUT' ? ' from' : ' to'} inventory`,
      stockBefore: oldStock,
      stockAfter: p.stock,
      rate: p.rate,
      quantity: qty
    })
    
    p.value = p.stock * p.rate
    
    showNotification(
      'success',
      `${action} recorded`,
      `${qty} units ${action === 'IN' ? 'added to' : 'removed from'} ${p.name} (Size: ${p.size}). New stock: ${p.stock}`
    )
    
    if (action === 'OUT' && p.stock <= p.reorder_level) {
      showNotification(
        'warning',
        'Low Stock Alert',
        `${p.name} (Size: ${p.size}) stock (${p.stock}) is at or below minimum level (${p.reorder_level})`,
        8000
      )
    }
  }
  persistProducts()
  selectedIds.value = []
  showInoutModal.value = false
}

const filteredForInout = computed(() => {
  let list = products.value.slice()
  const f = inoutFilters.value
  if (f.name) list = list.filter(p => p.name.toLowerCase().includes(f.name.toLowerCase()))
  if (f.skuSize) list = list.filter(p => (`${p.sku}-${p.size}`).toLowerCase().includes(f.skuSize.toLowerCase()))
  if (f.stockMin != null) list = list.filter(p => p.stock >= (f.stockMin || 0))
  if (f.stockMax != null) list = list.filter(p => p.stock <= (f.stockMax || 0))
  return list
})

function openInoutModal() { showInoutModal.value = true }

const startEdit = (product: Product, field: keyof Product) => { 
  editingCell.value = `${product.id}-${String(field)}`
  editValue.value = String((product as any)[field] ?? '')
  originalValue.value = editValue.value
  nextTick(() => {
    const el = document.querySelector('input[ref="editInput"]') as HTMLInputElement
    if (el) el.focus()
  })
}

const cancelEdit = () => { 
  editingCell.value = null
  editValue.value = ''
  originalValue.value = ''
}

// Clear global search function
const clearGlobalSearch = () => {
  // Emit event to parent to clear global search
  emit('clear-global-search')
}

// Map section names to proper categories
const mapSectionToCategory = (section: string): string => {
  const sectionLower = section.toLowerCase()
  
  // V-Belt Classical Sections
  if (sectionLower === 'a') return 'A Section'
  if (sectionLower === 'b') return 'B Section'
  if (sectionLower === 'c') return 'C Section'
  if (sectionLower === 'd') return 'D Section'
  if (sectionLower === 'e') return 'E Section'
  
  // V-Belt Wedge Sections
  if (sectionLower === 'spa') return 'SPA Section'
  if (sectionLower === 'spb') return 'SPB Section'
  if (sectionLower === 'spc') return 'SPC Section'
  if (sectionLower === 'spz') return 'SPZ Section'
  
  // V-Belt Narrow Sections
  if (sectionLower === '3v') return '3V Section'
  if (sectionLower === '5v') return '5V Section'
  if (sectionLower === '8v') return '8V Section'
  
  // Cogged Belts Classical
  if (sectionLower === 'ax') return 'AX Section'
  if (sectionLower === 'bx') return 'BX Section'
  if (sectionLower === 'cx') return 'CX Section'
  
  // Cogged Belts Wedge
  if (sectionLower === 'xpa') return 'XPA Section'
  if (sectionLower === 'xpb') return 'XPB Section'
  if (sectionLower === 'xpc') return 'XPC Section'
  if (sectionLower === 'xpz') return 'XPZ Section'
  
  // Cogged Belts Narrow
  if (sectionLower === '3vx') return '3VX Section'
  if (sectionLower === '5vx') return '5VX Section'
  if (sectionLower === '8vx') return '8VX Section'
  
  // Poly V-Belts
  if (sectionLower === 'pj') return 'PJ Section'
  if (sectionLower === 'pk' || sectionLower === 'polybelt') return 'PK Section'
  if (sectionLower === 'pl') return 'PL Section'
  if (sectionLower === 'pm') return 'PM Section'
  if (sectionLower === 'ph') return 'PH Section'
  
  // Poly V-Belts Double Side
  if (sectionLower === 'dpl') return 'DPL Section'
  if (sectionLower === 'dpk') return 'DPK Section'
  
  // Timing Belts Classical
  if (sectionLower === 'xl') return 'XL Section'
  if (sectionLower === 'l') return 'L Section'
  if (sectionLower === 'h') return 'H Section'
  if (sectionLower === 'xh') return 'XH Section'
  if (sectionLower === 't5') return 'T5 Section'
  if (sectionLower === 't10') return 'T10 Section'
  
  // Timing Belts HTD
  if (sectionLower === '5m') return '5M Section'
  if (sectionLower === '8m') return '8M Section'
  if (sectionLower === '14m') return '14M Section'
  
  // Timing Belts Double Side
  if (sectionLower === 'dl') return 'DL Section'
  if (sectionLower === 'dh') return 'DH Section'
  if (sectionLower === 'd5m') return 'D5M Section'
  if (sectionLower === 'd8m') return 'D8M Section'
  
  // TPU Belt Open
  if (sectionLower === 'tpu5m') return 'TPU 5M Section'
  if (sectionLower === 'tpu8m') return 'TPU 8M Section'
  if (sectionLower === 'tpu8mrpp') return 'TPU 8M RPP Section'
  if (sectionLower === 'tpus8m') return 'TPU S8M Section'
  if (sectionLower === 'tpu14m') return 'TPU 14M Section'
  if (sectionLower === 'tpuxl') return 'TPU XL Section'
  if (sectionLower === 'tpul') return 'TPU L Section'
  if (sectionLower === 'tpuh') return 'TPU H Section'
  if (sectionLower === 'tpuat5') return 'TPU AT5 Section'
  if (sectionLower === 'tpuat10') return 'TPU AT10 Section'
  if (sectionLower === 'tput10') return 'TPU T10 Section'
  if (sectionLower === 'tpuat20') return 'TPU AT20 Section'
  
  // Special Belts
  if (sectionLower === 'conicalc') return 'Conical C Section'
  if (sectionLower === 'harvester') return 'Harvester Section'
  if (sectionLower === 'rax') return 'RAX Section'
  if (sectionLower === 'rbx') return 'RBX Section'
  if (sectionLower === 'r3vx') return 'R3VX Section'
  if (sectionLower === 'r5vx') return 'R5VX Section'
  if (sectionLower === '8mpk') return '8M PK Section'
  if (sectionLower === '8mpl') return '8M PL Section'
  if (sectionLower === 'polycoating') return 'Poly Coating Section'
  if (sectionLower === 'flatcoating') return 'Flat Coating Section'
  if (sectionLower === 'timingcoating') return 'Timing Coating Section'
  
  // Default fallback
  return `${section.charAt(0).toUpperCase() + section.slice(1)} Section`
}

const processUploadedData = (uploadedData: any[], options: { replace: boolean }) => {
  try {
    let addedCount = 0
    let updatedCount = 0
    let skippedCount = 0

    if (options.replace) {
      products.value = products.value.filter(p => !(props.initialCategories ? props.initialCategories.includes(p.category) : true))
      transactionHistory.value = []
      persistProducts()
    }
    
    console.log('Processing Excel upload data:', uploadedData)
    
    uploadedData.forEach((item, index) => {
      // Generate unique ID for new products
      const uniqueId = Date.now() + Math.random() + index
      
      // Handle TPU format: width→section, meters→size, or standard format
      const section = String(item.width || item.section || '')
      const size = String(item.meters || item.size || '')
      
      // Create comprehensive product record with proper category mapping
      const newProduct: Product = {
        id: uniqueId,
        name: item.name || SECTION_NAME,
        sku: `${SECTION_NAME}-${section}-${size}`,
        section: section,
        size: size,
        stock: Number(item.stock || item.balanceStock) || 0,
        reorder_level: Number(item.reorder_level) || 0,
        rate: Number(item.rate) || 0,
        value: Number(item.value) || 0,
        category: `${SECTION_NAME} Section`,
        in_qty: 0,
        out_qty: 0,
        items_per_sleve: 1,
        remark: item.remark || ''
      }
      
      // Debug logging
      console.log('Processing item:', {
        section: section,
        size: size,
        category: newProduct.category,
        name: newProduct.name,
        sku: newProduct.sku,
        rate: newProduct.rate,
        value: newProduct.value
      })
      
      // Validate essential data
      if (!section || section === '0' || !size || size === '0') {
        console.warn('Skipping invalid product (missing section/size):', item)
        skippedCount++
        return
      }
      
      // Check for existing product by SKU
      const existingIndex = products.value.findIndex(p => 
        p.sku === newProduct.sku || 
        (p.name === newProduct.name && p.size === newProduct.size)
      )
      
      if (existingIndex !== -1) {
        // Update existing product
        const existingProduct = products.value[existingIndex]
        const oldStock = existingProduct.stock
        const oldRate = existingProduct.rate
        
        // Merge data, keeping existing ID and updating all other fields
        products.value[existingIndex] = { 
          ...existingProduct,
          ...newProduct, 
          id: existingProduct.id // Keep original ID
        }
        
        // Add transaction for stock changes
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock} for ${newProduct.name}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        // Add transaction for rate changes
        if (oldRate !== newProduct.rate && newProduct.rate > 0) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: 'EDIT',
            description: `Excel upload: Rate updated from ₹${oldRate} to ₹${newProduct.rate} for ${newProduct.name}`,
            stockBefore: newProduct.stock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
        console.log('Updated existing product:', newProduct.name)
      } else {
        // Add new product
        
        // Add initial stock transaction if stock > 0
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product "${newProduct.name}" added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        products.value.push(newProduct)
        addedCount++
        console.log('Added new product:', newProduct.name)
      }
    })
    
    // Save all data to localStorage (and later to backend)
    persistProducts()
    
    // Show comprehensive success message
    const message = `Added: ${addedCount} new products, Updated: ${updatedCount} existing products` + 
                   (skippedCount > 0 ? `, Skipped: ${skippedCount} invalid entries` : '')
    
    showNotification('success', 'Excel Upload Complete', message, 8000)
    
    // Log summary for debugging
    console.log('Excel upload summary:', {
      total: uploadedData.length,
      added: addedCount,
      updated: updatedCount,
      skipped: skippedCount,
      finalProductCount: products.value.length
    })
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data. Please check the console for details.')
  }
}

// Handle Excel data upload with complete data migration (append mode)
const handleDataUpload = (uploadedData: any[]) => {
  processUploadedData(uploadedData, { replace: false })
}

const importJson = (mode: 'append' | 'replace') => {
  if (!jsonInput.value.trim()) {
    showNotification('warning', 'No JSON', 'Please paste JSON data first.')
    return
  }
  try {
    const parsed = JSON.parse(jsonInput.value)
    if (!Array.isArray(parsed)) {
      showNotification('error', 'Invalid JSON', 'JSON must be an array of objects.')
      return
    }
    processUploadedData(parsed, { replace: mode === 'replace' })
    showNotification('success', 'JSON Imported', `Mode: ${mode === 'replace' ? 'Replace' : 'Append'}. Entries: ${parsed.length}`)
  } catch (e) {
    console.error('JSON parse error', e)
    showNotification('error', 'Invalid JSON', 'Failed to parse JSON. Check the format.')
  }
}

const saveCell = (product: Product, field: keyof Product) => { 
  const val = (field === 'stock' || field === 'rate' || field === 'value' || field === 'reorder_level' || field === 'in_qty' || field === 'out_qty') ? 
    field === 'reorder_level' ? Math.max(1, Number(editValue.value)) : Number(editValue.value) : 
    editValue.value
  const idx = products.value.findIndex(p => p.id === product.id)
  if (idx !== -1) {
    const updatedProduct = products.value[idx]
    const oldStock = updatedProduct.stock

    if (field === 'in_qty') {
      const oldQty = product.in_qty || 0
      const newQty = Math.max(0, Number(val) || 0)
      const change = newQty - oldQty
      
      if (change !== 0) {
        updatedProduct.stock += change
        
        addTransaction({
          productId: product.id,
          timestamp: Date.now(),
          type: 'IN',
          description: `Added ${change} units (IN adjustment)`,
          stockBefore: oldStock,
          stockAfter: updatedProduct.stock,
          rate: updatedProduct.rate,
          quantity: change
        })

        updatedProduct.in_qty = 0
        
        showNotification('success', 'Stock Updated', 
          `Added ${change} units to ${product.name} (Size: ${product.size}). New stock: ${updatedProduct.stock}`)
      }
    } 
    else if (field === 'out_qty') {
      const oldQty = product.out_qty || 0
      const newQty = Math.max(0, Number(val) || 0)
      const change = newQty - oldQty
      
      if (change !== 0) {
        if (updatedProduct.stock - change < 0) {
          showNotification('error', 'Insufficient Stock', 
            `Cannot remove ${change} units. Only ${updatedProduct.stock} available.`)
          cancelEdit()
          return
        }

        updatedProduct.stock -= change
        
        addTransaction({
          productId: product.id,
          timestamp: Date.now(),
          type: 'OUT',
          description: `Removed ${change} units (OUT adjustment)`,
          stockBefore: oldStock,
          stockAfter: updatedProduct.stock,
          rate: updatedProduct.rate,
          quantity: change
        })

        updatedProduct.out_qty = 0
        
        showNotification('success', 'Stock Updated', 
          `Removed ${change} units from ${product.name} (Size: ${product.size}). New stock: ${updatedProduct.stock}`)
      }
    }
    else {
      (updatedProduct as any)[field] = val
    }

    updatedProduct.value = updatedProduct.stock * updatedProduct.rate
    
    persistProducts()

    if (field !== 'in_qty' && field !== 'out_qty') {
      showNotification('success', 'Updated', `Updated ${String(field)} for ${product.name}`)
    }

    if (updatedProduct.stock <= updatedProduct.reorder_level) {
      showNotification('warning', 'Low Stock', 
        `${updatedProduct.name} stock (${updatedProduct.stock}) is at or below minimum level (${updatedProduct.reorder_level})`, 
        8000)
    }
  }
  cancelEdit()
}

const getStockClass = (p: Product) => { 
  if (p.stock <= 0) return 'text-red-600 font-semibold'
  if (p.stock <= p.reorder_level) return 'text-yellow-600 font-semibold'
  return 'text-green-600 font-semibold'
}

const createProduct = () => {
  const id = products.value.reduce((m, p) => Math.max(m, p.id), 0) + 1
  const c = createForm.value
  const p: Product = { 
    id, 
    category: c.category,
    name: c.name || c.category,
    sku: `${c.category.replace(' Section', '').toUpperCase()}-${c.size}`,
    size: c.size || '',
    stock: Number(c.stock) || 0,
    reorder_level: Number(c.reorder_level) || 5,
    rate: Number(c.rate) || 0,
    value: (Number(c.stock) || 0) * (Number(c.rate) || 0),
    in_qty: 0,
    out_qty: 0,
    items_per_sleve: 1
  }
  products.value.push(p)
  persistProducts()
  showNotification('success', 'Created', `Created ${p.name}`)
  showCreateModal.value = false
  
  if (p.stock <= p.reorder_level) {
    showNotification('warning', 'Low Stock', `${p.name} stock (${p.stock}) is at or below minimum level (${p.reorder_level})`, 8000)
  }
}

const onDelete = (id: number) => { 
  if (!confirm('Delete product?')) return
  products.value = products.value.filter(p => p.id !== id)
  persistProducts()
  showNotification('success', 'Deleted', 'Product removed')
}

const downloadJson = () => {
  const sectionName = props.initialCategories && props.initialCategories.length === 1 
    ? props.initialCategories[0].replace(' Section', '') 
    : 'inventory'
  
  // Export with full structure including section field
  const exportData = visibleProducts.value.map(p => ({
    id: p.id,
    category: p.category,
    name: p.name,
    sku: p.sku,
    section: p.section,
    size: p.size,
    stock: p.stock,
    reorder_level: p.reorder_level,
    rate: p.rate,
    value: p.value,
    in_qty: p.in_qty || 0,
    out_qty: p.out_qty || 0,
    remark: p.remark || ''
  }))
  
  const dataStr = JSON.stringify(exportData, null, 2)
  const dataBlob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(dataBlob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${sectionName}Products.json`
  link.click()
  URL.revokeObjectURL(url)
  showNotification('success', 'Downloaded', `${sectionName}Products.json downloaded`)
}

// Sidebar state detection
const checkSidebarState = () => {
  const sidebar = document.getElementById('logo-sidebar')
  if (sidebar) {
    sidebarCollapsed.value = sidebar.classList.contains('w-16')
  }
}

onMounted(() => {
  loadProducts()
  loadTransactionHistory()
  checkSidebarState()
  window.addEventListener('resize', checkSidebarState)
  const observer = new MutationObserver(checkSidebarState)
  const sidebar = document.getElementById('logo-sidebar')
  if (sidebar) {
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] })
  }
})
</script>