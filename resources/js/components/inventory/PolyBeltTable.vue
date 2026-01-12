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
          Click a cell to edit. All changes are saved to the database.
        </p>
      </div>

      <!-- Summary Stats -->
      <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Products</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ visibleProducts.length }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Ribs</div>
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ totalRibs }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Value</div>
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">₹{{ totalValue.toFixed(2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Low Ribs Items</div>
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ lowRibsCount }}</div>
        </div>
      </div>

      <!-- Filters -->
       <div class="sticky top-14 z-30 bg-gray-50 dark:bg-gray-900 pb-2 sm:pb-4">
      <div class="mb-4 sticky bg-white dark:bg-gray-800 rounded-lg shadow-md p-3">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Search -->
          <input 
            v-model="searchTerm" 
            placeholder="Search section / size / ribs" 
            class="px-3 py-1.5 text-sm border rounded bg-white dark:bg-gray-700 dark:text-white"
          />
          
          <!-- Quick Filter Buttons -->
          <button 
            @click="toggleLowStockFilter" 
            :class="showLowStockOnly ? 'bg-yellow-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm rounded hover:opacity-80 transition-colors"
          >
            {{ showLowStockOnly ? '✓ Low Stock' : 'Low Stock' }}
          </button>
          
          <button 
            @click="toggleOutOfStockFilter" 
            :class="showOutOfStockOnly ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm rounded hover:opacity-80 transition-colors"
          >
            {{ showOutOfStockOnly ? '✓ Out of Stock' : 'Out of Stock' }}
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
          
          <!-- JSON Import/Export Buttons (Commented for production) -->
          <div class="ml-auto flex items-center gap-2">
            <!-- <button @click="showImportModal = true" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
              Import JSON
            </button>
            <button @click="downloadJSON" class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">
              Download JSON
            </button> -->
            <button @click="showCreateModal = true" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
              Create Product
            </button>
          </div>
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
<!-- NEW -->

<div class="flex-1 bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
  <div class="overflow-y-auto max-h-[calc(100vh-400px)]">
    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
      <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase sticky top-0 z-20">
        <tr>
          <th class="py-3 px-3">Section</th>
                <th class="py-3 px-3">Size</th>
                <th class="py-3 px-3 text-center">Ribs</th>
                <th class="py-3 px-3 text-center">IN Ribs</th>
                <th class="py-3 px-3 text-center">OUT Ribs</th>
                <th class="py-3 px-3 text-center">Min Ribs</th>
                <th class="py-3 px-3 text-right">Rate per Rib</th>
                <th class="py-3 px-3 text-right">Value</th>
                <th class="py-3 px-3">Remark</th>
                <th class="py-3 px-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in visibleProducts" :key="p.id" class="border-t hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-section`">
                    <input v-model="editValue"  @keyup.enter="saveCell(p, 'section')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'section')" class="cursor-pointer font-bold text-black dark:text-white">{{ p.section }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-size`">
                    <input 
                      v-model="editValue" 
                      type="number" 
                      step="0.01" 
                      min="0.01" 
                      @keyup.enter="saveCell(p, 'size')" 
                      @keyup.esc="cancelEdit" 
                      class="w-full p-1 border rounded" 
                      placeholder="Enter size"
                      @focus="$event.target.select()"
                      :disabled="savingCell === `${p.id}-size`"
                    />
                  </div>
                  <div v-else @click="startEdit(p, 'size')" class="cursor-pointer font-bold text-black dark:text-white">
                    {{ p.size }}
                    <span v-if="savingCell === `${p.id}-size`" class="text-xs text-blue-500 ml-1">(saving...)</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-ribs`">
                    <input v-model="editValue" type="number" min="0" @keyup.enter="saveCell(p, 'ribs')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'ribs')" class="cursor-pointer">
                    <span :class="getRibsClass(p)" class="font-bold">{{ p.ribs }}</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-in_qty`">
                    <input 
                      v-model="editValue" 
                      type="number" 
                      min="0"
                      @keyup.enter="performInOut(p, 'IN')" 
                      @keyup.esc="cancelEdit" 
                      class="w-20 p-1 border rounded text-center bg-green-50" 
                      placeholder="IN ribs (Press Enter)"
                    />
                  </div>
                  <div v-else @click="startEdit(p, 'in_qty')" class="cursor-pointer hover:bg-green-50 px-2 py-1 rounded">
                    <span class="text-green-600 font-medium">0</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-out_qty`">
                    <input 
                      v-model="editValue" 
                      type="number" 
                      min="0"
                      @keyup.enter="performInOut(p, 'OUT')" 
                      @keyup.esc="cancelEdit" 
                      class="w-20 p-1 border rounded text-center bg-red-50" 
                      placeholder="OUT ribs (Press Enter)"
                    />
                  </div>
                  <div v-else @click="startEdit(p, 'out_qty')" class="cursor-pointer hover:bg-red-50 px-2 py-1 rounded">
                    <span class="text-red-600 font-medium">0</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p.id}-reorder_level`">
                    <input v-model="editValue" type="number" min="0" 
                           @keyup.enter="saveCell(p, 'reorder_level')" 
                           @keyup.esc="cancelEdit" 
                           class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'reorder_level')" class="cursor-pointer">{{ p.reorder_level ?? 'Not tracked' }}</div>
                </td>

                <td class="py-2 px-3 text-right">
                  <div v-if="editingCell === `${p.id}-rate_per_rib`">
                    <input v-model="editValue" type="number" step="0.01"  @keyup.enter="saveCell(p, 'rate_per_rib')" @keyup.esc="cancelEdit" class="w-24 p-1 border rounded text-right" />
                  </div>
                  <div v-else @click="startEdit(p, 'rate_per_rib')" class="cursor-pointer">
                    ₹{{ (Number(p.rate_per_rib) || 0).toFixed(2) }}
                    <span v-if="editingCell && editingCell.startsWith(`${p.id}-size`)" class="text-xs text-blue-500 ml-1">(updating...)</span>
                  </div>
                </td>

                <td class="py-2 px-3 text-right font-medium text-green-600">
                  ₹{{ (Number(p.value) || 0).toFixed(2) }}
                  <span v-if="editingCell && editingCell.startsWith(`${p.id}-size`)" class="text-xs text-blue-500 ml-1">(updating...)</span>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p.id}-remark`">
                    <input v-model="editValue" @keyup.enter="saveCell(p, 'remark')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'remark')" class="cursor-pointer">{{ p.remark || '-' }}</div>
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
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-3xl z-50 max-h-[80vh] overflow-y-auto">
          <div class="flex justify-between items-center mb-4">
            <div>
              <h3 class="font-semibold text-lg">Transaction History</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400" v-if="selectedProduct">
                {{ selectedProduct.section }} - {{ selectedProduct.size }} ({{ selectedProduct.ribs }} ribs)
              </p>
            </div>
            <button @click="showHistoryModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <span class="text-2xl">&times;</span>
            </button>
          </div>

          <!-- Date Filter for History -->
          <div class="mb-4 flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 rounded">
            <label class="text-sm text-gray-700 dark:text-gray-200">From:</label>
            <input 
              v-model="historyDateFrom" 
              type="date" 
              class="p-2 border rounded bg-white dark:bg-gray-800 dark:text-white text-sm"
            />
            <label class="text-sm text-gray-700 dark:text-gray-200">To:</label>
            <input 
              v-model="historyDateTo" 
              type="date" 
              class="p-2 border rounded bg-white dark:bg-gray-800 dark:text-white text-sm"
            />
            <button 
              @click="clearHistoryDateFilter" 
              class="px-3 py-2 text-sm bg-gray-500 text-white rounded hover:bg-gray-600"
            >
              Clear
            </button>
          </div>
          
          <div class="space-y-4">
            <div v-for="(transaction, index) in filteredTransactionHistory" :key="index" 
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

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-lg z-50">
          <h3 class="font-semibold mb-2">Create Product</h3>
          <div class="grid grid-cols-1 gap-2">
            <label>Section
              <input v-model="createForm.section" class="w-full p-2 border rounded" :placeholder="section || 'e.g., PJ, PK, PL'" />
            </label>

            <label>Size
              <input v-model.number="createForm.size" type="number" step="0.01" class="w-full p-2 border rounded" placeholder="Enter size (e.g., 1600)" />
            </label>

            <label>Ribs (Inventory Quantity)
              <input v-model.number="createForm.ribs" type="number" class="w-full p-2 border rounded" min="0" placeholder="Number of ribs in stock" />
            </label>

            <label>Minimum Ribs Level
              <input v-model.number="createForm.reorder_level" type="number" class="w-full p-2 border rounded" min="0" placeholder="Leave empty to disable tracking" />
            </label>

            <label>Rate per Rib (leave empty for auto-calculation)
              <input v-model.number="createForm.rate_per_rib" type="number" step="0.01" class="w-full p-2 border rounded" />
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

      <!-- Import JSON Modal -->
      <div v-if="showImportModal" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded p-4 w-full max-w-2xl z-50">
          <h3 class="font-semibold mb-4">Import Poly Belts JSON</h3>
          
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
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { usePolyBelts, type PolyBelt, type Transaction } from '../../composables/usePolyBelts'

const props = defineProps<{
  section?: string  // Optional: filter by specific section (PJ, PK, PL, etc.)
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
} = usePolyBelts(props.section)

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
const showLowStockOnly = ref(false)
const showOutOfStockOnly = ref(false)
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')
const savingCell = ref<string|null>(null)

const showCreateModal = ref(false)
const createForm = ref({ 
  section: props.section || '',
  size: 0, 
  ribs: 0,
  reorder_level: null, 
  rate_per_rib: undefined as number | undefined,
  remark: ''
})

// Import/Export functionality
const showImportModal = ref(false)
const importJSON = ref('')
const importMode = ref('append')

const sampleJSONFormat = `Import Format (Simple):
[
  {
    "section": "PL",
    "size": "1600",
    "ribs": 15,
    "ratePerRib": 54,
    "remark": "16 PL 1600"
  }
]

Download Format (Complete DB):
[
  {
    "id": 1,
    "section": "PL",
    "size": "1600",
    "ribs": 15,
    "in_ribs": 0,
    "out_ribs": 0,
    "reorder_level": 5,
    "rate_per_rib": 54.00,
    "value": 810.00,
    "remark": "16 PL 1600",
    "sku": "PL-1600-15R",
    "category": "Poly Belts",
    "created_by": null,
    "updated_by": null,
    "created_at": "2025-12-17T...",
    "updated_at": "2025-12-17T..."
  }
]`

const showHistoryModal = ref(false)
const selectedProduct = ref<PolyBelt | null>(null)
const transactionHistory = ref<Transaction[]>([])
const historyDateFrom = ref('')
const historyDateTo = ref('')

const sidebarCollapsed = ref(false)

const visibleProducts = computed(() => {
  let list = products.value.slice()
  
  // Search filter
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase().trim()
    
    // Check if search contains both section and size (space-separated)
    const searchParts = q.split(' ').filter(part => part.length > 0)
    
    if (searchParts.length >= 2) {
      // Combined search: exact match for section AND size
      const [sectionPart, sizePart] = searchParts
      list = list.filter(p => 
        p.section.toLowerCase() === sectionPart && 
        p.size.toLowerCase().includes(sizePart)
      )
    } else {
      // Single search term: match section OR size OR ribs
      list = list.filter(p => 
        p.section.toLowerCase().includes(q) || 
        p.size.toLowerCase().includes(q) ||
        p.ribs.toString().includes(q)
      )
    }
  }
  
  // Low ribs filter
  if (showLowStockOnly.value) {
    list = list.filter(p => p.ribs <= p.reorder_level && p.ribs > 0)
  }
  
  // Out of ribs filter
  if (showOutOfStockOnly.value) {
    list = list.filter(p => p.ribs === 0)
  }
  
  // Date filter - filter by created_at or updated_at
  if (dateFrom.value || dateTo.value) {
    list = list.filter(p => {
      // Use the most recent date (updated_at if exists, otherwise created_at)
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
const totalRibs = computed(() => {
  return visibleProducts.value.reduce((sum, p) => sum + p.ribs, 0)
})

const totalValue = computed(() => {
  return visibleProducts.value.reduce((sum, p) => sum + (Number(p.value) || 0), 0)
})

const lowRibsCount = computed(() => {
  return visibleProducts.value.filter(p => p.ribs <= p.reorder_level && p.ribs > 0).length
})

// Filtered transaction history by date
const filteredTransactionHistory = computed(() => {
  let list = transactionHistory.value.slice()
  
  if (historyDateFrom.value) {
    const fromDate = new Date(historyDateFrom.value)
    fromDate.setHours(0, 0, 0, 0)
    list = list.filter(t => new Date(t.created_at) >= fromDate)
  }
  
  if (historyDateTo.value) {
    const toDate = new Date(historyDateTo.value)
    toDate.setHours(23, 59, 59, 999)
    list = list.filter(t => new Date(t.created_at) <= toDate)
  }
  
  return list
})

const clearHistoryDateFilter = () => {
  historyDateFrom.value = ''
  historyDateTo.value = ''
}

const clearDateFilter = () => {
  dateFrom.value = ''
  dateTo.value = ''
  console.log('Date filter cleared')
}

const toggleLowStockFilter = () => {
  showLowStockOnly.value = !showLowStockOnly.value
  if (showLowStockOnly.value) {
    showOutOfStockOnly.value = false // Disable out of stock filter
  }
}

const toggleOutOfStockFilter = () => {
  showOutOfStockOnly.value = !showOutOfStockOnly.value
  if (showOutOfStockOnly.value) {
    showLowStockOnly.value = false // Disable low stock filter
  }
}

const startEdit = (product: PolyBelt, field: keyof PolyBelt | 'in_qty' | 'out_qty') => { 
  editingCell.value = `${product.id}-${String(field)}`
  // For in_qty and out_qty, always start with 0
  if (field === 'in_qty' || field === 'out_qty') {
    editValue.value = '0'
  } else {
    editValue.value = String((product as any)[field] ?? '')
  }
}

const cancelEdit = () => { 
  editingCell.value = null
  editValue.value = ''
  savingCell.value = null
}

const saveCell = async (product: PolyBelt, field: keyof PolyBelt) => {
  const cellId = `${product.id}-${String(field)}`
  
  console.log(`🔄 saveCell called for ${cellId}, editingCell: ${editingCell.value}, savingCell: ${savingCell.value}`)
  
  // Prevent multiple saves for the same cell
  if (!editingCell.value || editingCell.value !== cellId || savingCell.value === cellId) {
    console.log(`❌ saveCell blocked for ${cellId}`)
    return
  }
  
  let val: any
  
  // Handle different field types
  if (['ribs', 'reorder_level', 'rate_per_rib', 'size'].includes(field)) {
    // Check if the input is empty or just whitespace
    const inputValue = String(editValue.value).trim()
    console.log(`📝 Input value for ${field}: "${inputValue}"`)
    
    if (inputValue === '' || inputValue === 'NaN') {
      console.log(`❌ Empty input detected for ${field}`)
      showNotification('error', 'Invalid Input', `${field} cannot be empty`)
      cancelEdit()
      return
    }
    
    val = Number(inputValue)
    
    // Validation for specific fields
    if (field === 'size' && (val <= 0 || isNaN(val))) {
      showNotification('error', 'Invalid Size', 'Size must be a positive number greater than 0')
      cancelEdit()
      return
    }
    
    if (field === 'ribs' && (val < 0 || isNaN(val))) {
      showNotification('error', 'Invalid Ribs', 'Ribs must be a non-negative number')
      cancelEdit()
      return
    }
    
    if (field === 'rate_per_rib' && (val < 0 || isNaN(val))) {
      showNotification('error', 'Invalid Rate', 'Rate must be a non-negative number')
      cancelEdit()
      return
    }
    
  } else if (['section', 'remark'].includes(field)) {
    val = String(editValue.value).trim()
    
    // Validation for section
    if (field === 'section' && !val) {
      showNotification('error', 'Invalid Section', 'Section cannot be empty')
      cancelEdit()
      return
    }
  } else {
    val = editValue.value
  }
  
  // Set saving state and clear editing state immediately to prevent double saves
  console.log(`💾 Starting save for ${cellId} with value: ${val}`)
  savingCell.value = cellId
  cancelEdit()
  
  try {
    const response = await apiUpdateProduct(product.id, { [field]: val })
    
    // Show different messages based on what was updated
    if (field === 'size') {
      showNotification('success', 'Size Updated', `Size updated to ${val}. Rate and value recalculated automatically.`)
    } else if (field === 'section') {
      showNotification('success', 'Section Updated', `Section updated to ${val}. Rate and value recalculated automatically.`)
    } else {
      showNotification('success', 'Updated', `Updated ${String(field)}`)
    }
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Update failed')
  } finally {
    console.log(`✅ Save completed for ${cellId}`)
    savingCell.value = null
  }
}

const getRibsClass = (p: PolyBelt) => { 
  if (p.ribs <= 0) return 'text-red-600'
  if (p.ribs <= p.reorder_level) return 'text-yellow-600'
  return 'text-green-600'
}

const createProduct = async () => {
  try {
    await apiCreateProduct(createForm.value)
    showNotification('success', 'Created', 'Product created successfully')
    showCreateModal.value = false
    createForm.value = { 
      section: props.section || '',
      size: 0, 
      ribs: 0,
      reorder_level: null, 
      rate_per_rib: undefined,
      remark: ''
    }
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Creation failed')
  }
}

const onDelete = async (id: number) => { 
  if (!confirm('Delete product?')) return
  
  try {
    await apiDeleteProduct(id)
    showNotification('success', 'Deleted', 'Product removed')
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Deletion failed')
  }
}

const performInOut = async (product: PolyBelt, action: 'IN' | 'OUT') => {
  const cellId = `${product.id}-${action.toLowerCase()}_qty`
  
  console.log(`🔄 performInOut called for ${cellId}, editingCell: ${editingCell.value}, savingCell: ${savingCell.value}`)
  
  // Prevent multiple saves for the same cell
  if (!editingCell.value || editingCell.value !== cellId || savingCell.value === cellId) {
    console.log(`❌ performInOut blocked for ${cellId}`)
    return
  }
  
  const inputValue = String(editValue.value).trim()
  
  if (inputValue === '' || inputValue === 'NaN') {
    showNotification('error', 'Invalid Input', 'Quantity cannot be empty')
    cancelEdit()
    return
  }
  
  const qty = Number(inputValue)
  
  if (isNaN(qty) || qty <= 0) {
    showNotification('error', 'Invalid Quantity', 'Quantity must be a positive number')
    cancelEdit()
    return
  }

  // Set saving state and clear editing state immediately to prevent double saves
  console.log(`💾 Starting IN/OUT for ${cellId} with quantity: ${qty}`)
  savingCell.value = cellId
  cancelEdit()

  try {
    await inOutOperation([product.id], action, qty)
    showNotification('success', `${action} Complete`, `${action} ${qty} units for ${product.section}-${product.size} (${product.ribs}R)`)
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Operation failed')
  } finally {
    console.log(`✅ IN/OUT completed for ${cellId}`)
    savingCell.value = null
  }
}

const showHistory = async (product: PolyBelt) => {
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
      size: Number(item.size),
      ribs: Number(item.ribs),
      rate_per_rib: Number(item.ratePerRib || item.rate_per_rib || 0),
      remark: item.remark || '',
      reorder_level: item.reorder_level || 5,
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

  // Export complete database records (like SPA example)
  const exportData = products.value.map(product => ({
    id: product.id,
    section: product.section,
    size: product.size,
    ribs: product.ribs,
    in_ribs: product.in_ribs || 0,
    out_ribs: product.out_ribs || 0,
    reorder_level: product.reorder_level,
    rate_per_rib: Number(product.rate_per_rib),
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
  link.download = `poly-belts-${props.section || 'all'}-${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  showNotification('success', 'Downloaded', `Downloaded ${exportData.length} products`)
}

onMounted(async () => {
  console.log('PolyBeltTable mounted, section:', props.section, 'title:', props.title, 'globalSearch:', props.globalSearch)
  try {
    await fetchProducts()
    console.log('Products loaded:', products.value.length)
    
    // If global search is provided, set it as search term
    if (props.globalSearch) {
      searchTerm.value = props.globalSearch
    }
  } catch (err) {
    console.error('Error loading products:', err)
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

// Watch date filters for debugging
watch([dateFrom, dateTo], ([from, to]) => {
  console.log('Date filter changed:', { from, to, totalProducts: products.value.length, visibleProducts: visibleProducts.value.length })
})
</script>