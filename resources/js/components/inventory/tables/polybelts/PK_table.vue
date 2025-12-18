<template>
  <div class="transition-all duration-300" :class="sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          PK Section Inventory
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Click a cell to edit. Use category filter to view items.
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

      <!-- PK Section Excel Upload -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">PK Section Excel Upload</h3>
        </div>
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
          <input
            ref="fileInput"
            type="file"
            accept=".xlsx,.xls"
            @change="handleASectionExcelUpload"
            class="hidden"
          >
          <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <div class="mt-4">
            <button
              @click="fileInput?.click()"
              :disabled="isUploading"
              class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors"
            >
              {{ isUploading ? 'Processing...' : 'Choose Excel File' }}
            </button>
          </div>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Upload .xlsx or .xls files with PK Section data (RACK No., Size, Balance stock, Rate, Value)
          </p>
          <div v-if="uploadStatus" class="mt-4 p-3 rounded-lg" :class="uploadStatus.includes('Error') ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">
            {{ uploadStatus }}
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="flex flex-wrap items-center gap-3">
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
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
              <tr>
                <th class="py-3 px-3">PK</th>
                <th class="py-3 px-3">RIBS</th>
                <th class="py-3 px-3 text-center">Balance Stock</th>
                <th class="py-3 px-3 text-center">Min Inventory</th>
                <th class="py-3 px-3 text-right">Rate Per RIBS</th>
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
          <h3 class="font-semibold mb-2">Create PK Section Product</h3>
          <div class="grid grid-cols-1 gap-2">
            <label>Rack No.
              <input v-model="createForm.section" class="w-full p-2 border rounded" placeholder="A" />
            </label>

            <label>Product Name
              <input v-model="createForm.name" class="w-full p-2 border rounded" placeholder="Product name (e.g., A-18)" />
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
import * as XLSX from 'xlsx'

const props = defineProps<{
  title?: string
  initialCategory?: string
  initialCategories?: string[]
  globalSectionQuery?: string
  globalSizeQuery?: string
}>()

const emit = defineEmits(['clear-global-search'])

interface Product extends InventoryProduct {}
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

const STORAGE_KEY = 'microbelts_products_v1'
const HISTORY_KEY = 'inventory_history'
const products = ref<Product[]>([])
const loading = ref(false)
const searchTerm = ref('')
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')
const originalValue = ref<any>('')
const fileInput = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)
const uploadStatus = ref('')

const showCreateModal = ref(false)
const createForm = ref({ 
  category: 'PK Section', 
  section: 'PK',
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

const persistProducts = () => localStorage.setItem(STORAGE_KEY, JSON.stringify(products.value))

const loadProducts = async () => {
  loading.value = true
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      try {
        products.value = JSON.parse(raw)
      } catch (e) {
        console.warn('failed parse', e)
        products.value = []
      }
    }

    if (!products.value.length) {
      const apiProducts = await fetchInventoryProducts()
      products.value = apiProducts
      persistProducts()
    }
  } finally {
    loading.value = false
  }
}

const clearDateFilter = () => {
  dateFrom.value = ''
  dateTo.value = ''
}

const availableCategories = computed(() => {
  return ['PK Section']
})

const visibleProducts = computed(() => {
  // Filter only PK Section products
  let list = products.value.filter(p => p.category === 'PK Section')

  // Apply date filter (if any)
  if (dateFrom.value || dateTo.value) {
    const fromDate = dateFrom.value ? new Date(dateFrom.value).getTime() : 0
    const toDate = dateTo.value ? new Date(dateTo.value).setHours(23, 59, 59, 999) : Date.now()
    const allTransactions: Transaction[] = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]')
    const allowedIds = new Set(allTransactions.filter(t => t.timestamp >= fromDate && t.timestamp <= toDate).map(t => t.productId))
    list = list.filter(p => allowedIds.has(p.id))
  }

  // Apply global section/size search
  if (props.globalSectionQuery || props.globalSizeQuery) {
    list = list.filter(p => {
      const sectionMatch = props.globalSectionQuery
        ? (p.section?.toLowerCase() === props.globalSectionQuery.toLowerCase() ||
           p.name.toLowerCase() === props.globalSectionQuery.toLowerCase())
        : true

      const sizeMatch = props.globalSizeQuery
        ? (p.size.toLowerCase() === props.globalSizeQuery.toLowerCase() ||
           String(p.size) === props.globalSizeQuery.trim())
        : true

      return sectionMatch && sizeMatch
    })
  }

  // Apply local search term
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(p => p.name.toLowerCase().includes(q) || p.size.toLowerCase().includes(q))
  }

  // Calculate value for each product
  list.forEach(p => { p.value = p.value })

  return list
})


const loadTransactionHistory = () => {
  const raw = localStorage.getItem(HISTORY_KEY)
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
  localStorage.setItem(HISTORY_KEY, JSON.stringify(transactionHistory.value))
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
    
    p.value = p.value
    
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
  // Filter only PK Section products
  let list = products.value.filter(p => p.category === 'PK Section')
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

// Parse PK Section Excel row (array-based: [Rack, Size, Stock, Rate, Value])
const parseASectionExcelRow = (row: any[], idx: number): Product | null => {
  const PK = row?.[0]
  const RIBS = row?.[1]

  // Skip empty rows
  if (!PK && !RIBS) return null
  if (!RIBS) return null

  const parseNumber = (val: any): number => {
    if (typeof val === 'string') {
      val = val.replace(/[^\d.-]/g, '')
    }
    return Number(val) || 0
  }

  
  const rate = parseNumber(row?.[2])
  const providedValue = parseNumber(row?.[3])
  const stock = 0
  const reorder = 5

  return {
    id: Date.now() + Math.random(),
    section: PK !== undefined && PK !== null && PK !== '' ? String(PK) : 'Unknown',
    category: 'PK Section',
    name: `${PK ? String(PK) : 'PK'}-${meter}`,
    size: String(RIBS),
    sku: `${PK ? String(PK) : 'PK'}-${RIBS}`,
    stock,
    rate,
    reorder_level: reorder,
    value: providedValue,
    in_qty: 0,
    out_qty: 0,
    items_per_sleve: 1
  }
}

// Handle PK Section Excel file upload
const handleASectionExcelUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
    uploadStatus.value = 'Error: Please select a valid Excel file (.xlsx or .xls)'
    return
  }
  
  isUploading.value = true
  uploadStatus.value = 'Processing Excel file...'
  
  try {
    // Always clear existing PK Section local data and history before processing
    products.value = products.value.filter(p => p.category !== 'PK Section')
    persistProducts()
    localStorage.removeItem(HISTORY_KEY)

    const data = await new Promise<ArrayBuffer>((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = (e) => resolve(e.target?.result as ArrayBuffer)
      reader.onerror = () => reject(new Error('Failed to read file'))
      reader.readAsArrayBuffer(file)
    })
    
    const workbook = XLSX.read(new Uint8Array(data), { type: 'array' })
    const sheetName = workbook.SheetNames[0]
    const worksheet = workbook.Sheets[sheetName]
    // Read as raw rows to avoid header issues; first row is the header
    const rows: any[][] = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' })
    const dataRows = rows.slice(1) // skip header row
    
    let added = 0
    let updated = 0
    let skipped = 0
    const skipReasons: Record<string, number> = {}

    const baseId = Date.now()
    dataRows.forEach((row: any[], idx: number) => {
      const product = parseASectionExcelRow(row, idx)

      if (!product) {
        skipped++
        skipReasons['Missing size/rack'] = (skipReasons['Missing size/rack'] || 0) + 1
        console.warn('PK Section upload skipped row', { idx, row })
        return
      }

      // Force rebuild: treat every row as new, assign deterministic id for this run
      product.id = baseId + idx
      products.value.push(product)

      if (product.stock > 0) {
        addTransaction({
          productId: product.id,
          timestamp: Date.now(),
          type: 'IN',
          description: `Excel upload: New PK Section product added`,
          stockBefore: 0,
          stockAfter: product.stock,
          rate: product.rate
        })
      }

      added++
    })

    persistProducts()

    const reasonText = Object.entries(skipReasons).map(([r, c]) => `${r}: ${c}`).join('; ')
    uploadStatus.value = `Success: Added ${added}, Updated ${updated}, Skipped ${skipped}${reasonText ? ' | Reasons -> ' + reasonText : ''}`
    showNotification(
      'success',
      'PK Section Upload Complete',
      `Added: ${added}, Updated: ${updated}, Skipped: ${skipped}${reasonText ? ' | ' + reasonText : ''}`,
      8000
    )
  } catch (error) {
    console.error('Upload error:', error)
    uploadStatus.value = 'Error: Failed to process file. Please check the format.'
    showNotification('error', 'Upload Error', 'Failed to process Excel file. Please check the format.')
  } finally {
    isUploading.value = false
    // Reset file input
    if (target) target.value = ''
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

    updatedProduct.value = updatedProduct.value
    
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
    category: 'PK Section',
    section: c.section || 'PK',
    name: c.name || `PK-${c.size}`,
    sku: `${c.section || 'PK'}-${c.size}`,
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
  
  // Reset form
  createForm.value = {
    category: 'PK Section',
    section: 'PK',
    name: '',
    sku: '',
    size: '',
    stock: 0,
    reorder_level: 5,
    rate: 0,
    value: 0
  }
  
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