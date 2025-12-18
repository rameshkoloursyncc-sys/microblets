<template>
  <div class="transition-all duration-300" :class="sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ title || 'Inventory Management' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Click a cell to edit. Use category filter to view SPA or Timing Belt items.
        </p>
      </div>

      <!-- Excel Upload Component -->
      <ExcelUpload @data-uploaded="handleDataUpload" />



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
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
              <tr>
                <th class="py-3 px-3">Section</th>
                <th class="py-3 px-3">Size</th>
                <th class="py-3 px-3 text-center"> Balance Stock</th>
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
                  <div v-if="editingCell === `${p.id}-name`">
                    <input v-model="editValue" @blur="saveCell(p, 'name')" @keyup.enter="saveCell(p, 'name')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'name')" class="cursor-pointer">{{ p.name }}</div>
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
                {{ selectedProduct.name }} -  {{ selectedProduct.size }}
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
import { fetchInventoryProducts, type InventoryProduct } from '../../lib/api/inventoryApi'
import ExcelUpload from './ExcelUpload.vue'

const props = defineProps<{
  title?: string
  initialCategory?: string
  initialCategories?: string[]
}>()

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
let notificationId = 0
const showNotification = (type: Notification['type'], title: string, message: string, timeout = 5000) => { const id = ++notificationId; notifications.value.push({ id, type, title, message }); if (timeout>0) setTimeout(()=> removeNotification(id), timeout) }
const removeNotification = (id:number) => { notifications.value = notifications.value.filter(n=>n.id!==id) }

const STORAGE_KEY = 'microbelts_products_v1'
const products = ref<Product[]>([])
const loading = ref(false)
const searchTerm = ref('')
const selectedCategory = ref(props.initialCategory ?? '')
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')
// Constants for local storage
const HISTORY_KEY = 'inventory_history'

const originalValue = ref<any>('')

const showCreateModal = ref(false)
const createForm = ref({ category: categories[0], name: '', sku: '', size: '', stock: 0, reorder_level: 5, rate: 0, value: 0 }) // Default minimum stock level is 5

const showInoutModal = ref(false)
const inoutFilters = ref({ name: '', skuSize: '', stockMin: null as number|null, stockMax: null as number|null })
const selectedIds = ref<number[]>([])

// Sidebar collapse state - we'll get this from parent or detect it
const sidebarCollapsed = ref(false)

// Listen for sidebar state changes (you can also pass this as a prop if needed)
const checkSidebarState = () => {
  const sidebar = document.getElementById('logo-sidebar')
  if (sidebar) {
    sidebarCollapsed.value = sidebar.classList.contains('w-16')
  }
}

// Check sidebar state periodically or on window resize
onMounted(() => {
  checkSidebarState()
  window.addEventListener('resize', checkSidebarState)
  // Also check when sidebar classes might change
  const observer = new MutationObserver(checkSidebarState)
  const sidebar = document.getElementById('logo-sidebar')
  if (sidebar) {
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] })
  }
})

const persistProducts = () => localStorage.setItem(STORAGE_KEY, JSON.stringify(products.value))

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

    // If nothing in local storage, fall back to mocked API
    if (!products.value.length) {
      const apiProducts = await fetchInventoryProducts()
      products.value = apiProducts
      persistProducts()
    }
  } finally {
    loading.value = false
  }
}

// Compute low stock products
const lowStockProducts = computed(() => {
  return products.value.filter(p => p.stock <= p.reorder_level)
    .sort((a, b) => (a.stock / a.reorder_level) - (b.stock / b.reorder_level))
})

const showLowStock = ref(false)
const dateFilter = ref('all')
const dateFrom = ref('')
const dateTo = ref('')

// Clear date filter function
const clearDateFilter = () => {
  dateFrom.value = ''
  dateTo.value = ''
}

// Available categories for create modal - isolated when in section view
const availableCategories = computed(() => {
  if (props.initialCategories && props.initialCategories.length > 0) {
    // In section-specific view, show only the categories for this section
    return props.initialCategories
  } else {
    // In general view, show all categories
    return categories
  }
})

const visibleProducts = computed(() => {
  let list = products.value.slice()
  
  // Apply category filter - handle both single category and multiple categories
  if (props.initialCategories && props.initialCategories.length > 0) {
    // Filter by multiple categories (from navigation)
    list = list.filter(p => props.initialCategories!.includes(p.category))
  } else if (selectedCategory.value) {
    // Filter by single selected category (from dropdown)
    list = list.filter(p => p.category === selectedCategory.value)
  }
  
  // Apply date filter (only for section-specific views)
  if (props.initialCategories && (dateFrom.value || dateTo.value)) {
    const fromDate = dateFrom.value ? new Date(dateFrom.value).getTime() : 0
    const toDate = dateTo.value ? new Date(dateTo.value).setHours(23, 59, 59, 999) : Date.now()
    
    // Filter products based on their transaction date range
    const dateRangeTransactionProductIds = new Set()
    const allTransactions = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]')
    
    allTransactions.forEach((transaction: Transaction) => {
      if (transaction.timestamp >= fromDate && transaction.timestamp <= toDate) {
        dateRangeTransactionProductIds.add(transaction.productId)
      }
    })
    
    // Only show products that had transactions in the date range
    list = list.filter(p => dateRangeTransactionProductIds.has(p.id))
  }
  
  // Apply low stock filter (only in general view)
  if (!props.initialCategories && showLowStock.value) {
    list = list.filter(p => p.stock <= p.reorder_level)
  }
  
  // Apply search filter
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(p => p.name.toLowerCase().includes(q) || p.size.toLowerCase().includes(q))
  }
  
  // Calculate value based on stock * rate for each product
  list.forEach(p => {
    p.value = p.stock * p.rate
  })
  
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
  transactionHistory.value.unshift(transaction)  // Add to beginning of array
  saveTransactionHistory()
}

const showHistory = (product: Product) => {
  selectedProduct.value = product
  // Filter transactions for this product
  transactionHistory.value = transactionHistory.value
    .filter(t => t.productId === product.id)
    .sort((a, b) => b.timestamp - a.timestamp)  // Most recent first
  showHistoryModal.value = true
}

const uniqueSkus = computed(() => [...new Set(products.value.map(p => p.sku))])

const selectAll = () => { selectedIds.value = filteredForInout.value.map(p=>p.id) }
const clearSelection = () => { selectedIds.value = [] }

// Initialize
onMounted(() => {
  loadProducts()
  loadTransactionHistory()
})

const markSelected = (action:'IN'|'OUT') => {
  if (!selectedIds.value.length) { showNotification('warning','No Selection','Please select items'); return }
  const qtyStr = prompt(`Quantity to ${action}`,'1')
  const qty = Math.max(0, Number(qtyStr||0))
  if (!qty) { showNotification('warning','Invalid','Quantity must be > 0'); return }

  for (const id of selectedIds.value) {
    const idx = products.value.findIndex(p=>p.id===id)
    if (idx===-1) continue
    const p = products.value[idx]
    
    if (action==='OUT' && p.stock<qty) { 
      showNotification('error','Insufficient',`${p.name} (Size: ${p.size}) has only ${p.stock} units in stock`); 
      continue 
    }
    
    const oldStock = p.stock
    
    // Update stock only - IN/OUT quantities stay at zero
    if (action==='IN') { 
      p.stock += qty
      p.in_qty = 0
    } else { 
      p.stock -= qty
      p.out_qty = 0
    }
    
    // Add transaction record
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
    
    // Recalculate value based on new stock
    p.value = p.stock * p.rate
    
    // Show success notification
    showNotification(
      'success',
      `${action} recorded`,
      `${qty} units ${action==='IN'?'added to':'removed from'} ${p.name} (Size: ${p.size}). New stock: ${p.stock}`
    )
    
    // Check for low stock after OUT operation
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

const filteredForInout = computed(()=>{
  let list = products.value.slice()
  const f = inoutFilters.value
  if (f.name) list = list.filter(p=>p.name.toLowerCase().includes(f.name.toLowerCase()))
  if (f.skuSize) list = list.filter(p=>(`${p.sku}-${p.size}`).toLowerCase().includes(f.skuSize.toLowerCase()))
  if (f.stockMin!=null) list = list.filter(p=>p.stock>= (f.stockMin||0))
  if (f.stockMax!=null) list = list.filter(p=>p.stock<= (f.stockMax||0))
  return list
})

function openInoutModal(){ showInoutModal.value = true }

const startEdit = (product: Product, field: keyof Product) => { editingCell.value = `${product.id}-${String(field)}`; editValue.value = String((product as any)[field] ?? ''); originalValue.value = editValue.value; nextTick(()=>{ const el = document.querySelector('input[ref="editInput"]') as HTMLInputElement; if (el) el.focus() }) }
const cancelEdit = () => { editingCell.value=null; editValue.value=''; originalValue.value='' }
// Handle Excel data upload with complete payload structure
const handleDataUpload = (uploadedData: any[]) => {
  try {
    let addedCount = 0
    let updatedCount = 0
    
    uploadedData.forEach((item) => {
      // Convert to Product format for table display
      const newProduct: Product = {
        id: Date.now() + Math.random(),
        name: item.name || `${item.section.toUpperCase()}-${item.size}`,
        sku: `${item.section.toUpperCase()}-${item.size}`,
        size: String(item.size),
        stock: item.balanceStock || 0,
        reorder_level: Math.max(1, item.reorder_level || 5),
        rate: item.rate || 0,
        value: (item.balanceStock || 0) * (item.rate || 0),
        category: item.category || `${item.section} Section`,
        in_qty: 0,
        out_qty: 0,
        items_per_sleve: item.items_per_sleve || 1
      }
      
      // Check if product exists by SKU (section + size)
      const existingIndex = products.value.findIndex(p => 
        p.sku === newProduct.sku
      )
      
      if (existingIndex !== -1) {
        // Update existing product
        const existingProduct = products.value[existingIndex]
        const oldStock = existingProduct.stock
        
        // Update the existing product with new data
        products.value[existingIndex] = { 
          ...existingProduct, 
          ...newProduct, 
          id: existingProduct.id // Keep original ID
        }
        
        // Add transaction if stock changed
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        products.value.push(newProduct)
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}
        
        products.value[existingIndex] = { ...existingProduct, ...newProduct, id: existingProduct.id }
        
        // Add transaction for stock change
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        products.value.push(newProduct)
        
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}
        
        products.value[existingIndex] = { ...existingProduct, ...newProduct, id: existingProduct.id }
        
        // Add transaction for stock change
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        products.value.push(newProduct)
        
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}
        
        products.value[existingIndex] = { ...existingProduct, ...newProduct, id: existingProduct.id }
        
        // Add transaction for stock change
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        products.value.push(newProduct)
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}
        
        products.value[existingIndex] = { ...existingProduct, ...newProduct, id: existingProduct.id }
        
        // Add transaction for stock change
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        products.value.push(newProduct)
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}
        const oldStock = existingProduct.stock
        
        products.value[existingIndex] = { ...existingProduct, ...newProduct, id: existingProduct.id }
        
        // Add stock entry if stock changed
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        products.value.push(newProduct)
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}
        const oldStock = existingProduct.stock
        
        products.value[existingIndex] = { ...existingProduct, ...newProduct, id: existingProduct.id }
        
        // Add stock entry if stock changed
        if (oldStock !== newProduct.stock) {
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        if (newProduct.stock > 0) {
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        products.value.push(newProduct)
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}
        const oldStock = existingProduct.stock
        
        products.value[existingIndex] = { ...existingProduct, ...newProduct, id: existingProduct.id }
        
        // Add stock entry if stock changed
        if (oldStock !== newProduct.stock) {
          const stockEntry = {
            type: newProduct.stock > oldStock ? 'in' : 'out',
            qty: Math.abs(newProduct.stock - oldStock),
            time: new Date().toISOString()
          }
          
          if (!products.value[existingIndex].stockEntries) {
            products.value[existingIndex].stockEntries = []
          }
          products.value[existingIndex].stockEntries.push(stockEntry)
          
          addTransaction({
            productId: existingProduct.id,
            timestamp: Date.now(),
            type: newProduct.stock > oldStock ? 'IN' : 'OUT',
            description: `Excel upload: Stock updated from ${oldStock} to ${newProduct.stock}`,
            stockBefore: oldStock,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        updatedCount++
      } else {
        // Add new product
        if (newProduct.stock > 0) {
          newProduct.stockEntries = [{
            type: 'in',
            qty: newProduct.stock,
            time: newProduct.inStockTime
          }]
          
          addTransaction({
            productId: newProduct.id,
            timestamp: Date.now(),
            type: 'IN',
            description: `Excel upload: New product added with ${newProduct.stock} units`,
            stockBefore: 0,
            stockAfter: newProduct.stock,
            rate: newProduct.rate
          })
        }
        
        products.value.push(newProduct)
        addedCount++
      }
    })
    
    persistProducts()
    
    showNotification('success', 'Excel Upload Complete', 
      `Successfully processed: ${addedCount} new products, ${updatedCount} updated products`)
      
  } catch (error) {
    console.error('Error processing uploaded data:', error)
    showNotification('error', 'Upload Error', 'Failed to process uploaded data')
  }
}

const saveCell = (product: Product, field: keyof Product) => { 
  const val = (field==='stock' || field==='rate' || field==='value' || field==='reorder_level' || field==='in_qty' || field==='out_qty') ? 
    // Ensure reorder_level is at least 1
    field === 'reorder_level' ? Math.max(1, Number(editValue.value)) : Number(editValue.value) : 
    editValue.value
  const idx = products.value.findIndex(p=>p.id===product.id)
  if(idx!==-1) {
    const updatedProduct = products.value[idx]
    const oldStock = updatedProduct.stock
    const oldValue = (updatedProduct as any)[field]

    if (field === 'in_qty') {
      // Calculate the actual change in quantity
      const oldQty = product.in_qty || 0
      const newQty = Math.max(0, Number(val) || 0)
      const change = newQty - oldQty
      
      if (change !== 0) {
        // Update stock and record transaction
        updatedProduct.stock += change
        
        // Record transaction
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

        // Reset IN quantity to zero after recording
        updatedProduct.in_qty = 0
        
        showNotification('success', 'Stock Updated', 
          `Added ${change} units to ${product.name} (Size: ${product.size}). New stock: ${updatedProduct.stock}`)
      }
    } 
    else if (field === 'out_qty') {
      // Calculate the actual change in quantity
      const oldQty = product.out_qty || 0
      const newQty = Math.max(0, Number(val) || 0)
      const change = newQty - oldQty
      
      if (change !== 0) {
        // Check if we have enough stock
        if (updatedProduct.stock - change < 0) {
          showNotification('error', 'Insufficient Stock', 
            `Cannot remove ${change} units. Only ${updatedProduct.stock} available.`)
          cancelEdit()
          return
        }

        // Update stock and record transaction
        updatedProduct.stock -= change
        
        // Record transaction
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

        // Reset OUT quantity to zero after recording
        updatedProduct.out_qty = 0
        
        showNotification('success', 'Stock Updated', 
          `Removed ${change} units from ${product.name} (Size: ${product.size}). New stock: ${updatedProduct.stock}`)
      }
    }
    else {
      // For other fields, just update the value
      (updatedProduct as any)[field] = val
    }

    // Recalculate value
    updatedProduct.value = updatedProduct.stock * updatedProduct.rate
    
    persistProducts()

    // For non-IN/OUT fields, show the generic update notification
    if (field !== 'in_qty' && field !== 'out_qty') {
      showNotification('success', 'Updated', `Updated ${String(field)} for ${product.name}`)
    }

    // Check stock level after any relevant changes
    if (updatedProduct.stock <= updatedProduct.reorder_level) {
      showNotification('warning', 'Low Stock', 
        `${updatedProduct.name} stock (${updatedProduct.stock}) is at or below minimum level (${updatedProduct.reorder_level})`, 
        8000)
    }
  }
  cancelEdit()
}

const getStockClass = (p:Product)=>{ if (p.stock<=0) return 'text-red-600 font-semibold'; if (p.stock<=p.reorder_level) return 'text-yellow-600 font-semibold'; return 'text-green-600 font-semibold' }

const createProduct = ()=>{
  const id = products.value.reduce((m,p)=>Math.max(m,p.id),0)+1
  const c = createForm.value
  const p:Product = { 
    id, 
    category: c.category,
    name: c.name || c.category,
    size: c.size || '',
    stock: Number(c.stock) || 0,
    reorder_level: Number(c.reorder_level) || 0,
    rate: Number(c.rate) || 0,
    value: Number(c.value) || 0,
    in_qty: 0,
    out_qty: 0
  }
  products.value.push(p)
  persistProducts()
  showNotification('success', 'Created', `Created ${p.name}`)
  showCreateModal.value = false
  
  // Check if initial stock is below reorder level
  if (p.stock <= p.reorder_level) {
    showNotification('warning', 'Low Stock', `${p.name} stock (${p.stock}) is at or below minimum level (${p.reorder_level})`, 8000)
  }
}

const onDelete = (id:number)=>{ if (!confirm('Delete product?')) return; products.value = products.value.filter(p=>p.id!==id); persistProducts(); showNotification('success','Deleted','Product removed') }

onMounted(()=> loadProducts())
</script>