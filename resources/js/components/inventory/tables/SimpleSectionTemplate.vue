<template>
  <div class="transition-all duration-300" :class="sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ SECTION_NAME }} Section Inventory
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Click a cell to edit. Paste JSON to import data.
        </p>
      </div>

      <!-- JSON Paste Import -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-4">
        <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Paste JSON Data</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
          Paste array: [{"section":"{{ SECTION_NAME }}","size":"790","balanceStock":7,"rate":427.39}]
        </p>
        <textarea
          v-model="jsonInput"
          rows="4"
          class="w-full p-2 border rounded bg-white dark:bg-gray-900 text-sm font-mono"
          placeholder='[{"section":"5VX","size":"790","balanceStock":7,"rate":427.39,"value":2991.73}]'
        ></textarea>
        <div class="flex gap-2 mt-2">
          <button @click="importJson('append')" class="px-3 py-1 bg-blue-600 text-white rounded">Append</button>
          <button @click="importJson('replace')" class="px-3 py-1 bg-red-600 text-white rounded">Replace</button>
          <button @click="downloadJson" class="px-3 py-1 bg-green-600 text-white rounded">Download JSON</button>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="flex flex-wrap items-center gap-3">
          <input v-model="searchTerm" placeholder="Search name / size" class="p-2 border rounded" />
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
                <th class="py-3 px-3">Name</th>
                <th class="py-3 px-3">Size</th>
                <th class="py-3 px-3 text-center">Stock</th>
                <th class="py-3 px-3 text-center">Min Level</th>
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
                    <input v-model.number="editValue" type="number" min="1" @blur="saveCell(p, 'reorder_level')" @keyup.enter="saveCell(p, 'reorder_level')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
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
                  <button @click="onDelete(p.id)" class="text-red-600 px-2 hover:text-red-800">Delete</button>
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
          <h3 class="font-semibold mb-2">Create Product</h3>
          <div class="grid grid-cols-1 gap-2">
            <label>Name
              <input v-model="createForm.name" class="w-full p-2 border rounded" :placeholder="SECTION_NAME" />
            </label>
            <label>Size
              <input v-model="createForm.size" class="w-full p-2 border rounded" placeholder="Enter size" />
            </label>
            <label>Stock
              <input v-model.number="createForm.stock" type="number" class="w-full p-2 border rounded" min="0" />
            </label>
            <label>Min Level
              <input v-model.number="createForm.reorder_level" type="number" class="w-full p-2 border rounded" min="1" />
            </label>
            <label>Rate
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
            <input v-model="inoutFilters.size" placeholder="Size" class="p-2 border rounded" />
            <input v-model.number="inoutFilters.stockMin" placeholder="Stock min" type="number" class="p-2 border rounded" />
            <input v-model.number="inoutFilters.stockMax" placeholder="Stock max" type="number" class="p-2 border rounded" />
          </div>
          <div class="max-h-64 overflow-auto mb-3 border rounded">
            <table class="w-full text-sm">
              <thead class="bg-gray-100">
                <tr>
                  <th class="py-2 px-3"><input type="checkbox" @change="$event.target.checked ? selectAll() : clearSelection()" /></th>
                  <th class="py-2 px-3">Name</th>
                  <th class="py-2 px-3">Size</th>
                  <th class="py-2 px-3 text-center">Stock</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in filteredForInout" :key="p.id" class="border-t">
                  <td class="py-2 px-3"><input type="checkbox" :value="p.id" v-model="selectedIds" /></td>
                  <td class="py-2 px-3">{{ p.name }}</td>
                  <td class="py-2 px-3">{{ p.size }}</td>
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

// 🔥 CHANGE THIS FOR EACH SECTION 🔥
const SECTION_NAME = 'CHANGE_ME' // Example: '5VX', 'A', 'B', 'AT10', etc.

interface Product {
  id: number
  category: string
  name: string
  sku: string
  size: string
  stock: number
  reorder_level: number
  rate: number
  value: number
  in_qty?: number
  out_qty?: number
}

interface Notification { id: number; type: 'success'|'error'|'warning'; title: string; message: string }

const STORAGE_KEY = `${SECTION_NAME}_products`
const products = ref<Product[]>([])
const searchTerm = ref('')
const editingCell = ref<string|null>(null)
const editValue = ref<any>('')
const jsonInput = ref('')
const showCreateModal = ref(false)
const showInoutModal = ref(false)
const sidebarCollapsed = ref(false)
const selectedIds = ref<number[]>([])
const notifications = ref<Notification[]>([])

const createForm = ref({ 
  name: SECTION_NAME, 
  size: '', 
  stock: 0, 
  reorder_level: 5, 
  rate: 0
})

const inoutFilters = ref({ 
  name: '', 
  size: '', 
  stockMin: null as number|null, 
  stockMax: null as number|null 
})

let notificationId = 0
const showNotification = (type: Notification['type'], title: string, message: string, timeout = 5000) => { 
  const id = ++notificationId
  notifications.value.push({ id, type, title, message })
  if (timeout > 0) setTimeout(() => notifications.value = notifications.value.filter(n => n.id !== id), timeout)
}

// Load from mock JSON file (like SPA section)
const loadProducts = async () => {
  // Check localStorage first
  const raw = localStorage.getItem(STORAGE_KEY)
  if (raw) {
    try {
      products.value = JSON.parse(raw)
      return
    } catch (e) {
      console.warn('Failed to parse localStorage', e)
    }
  }
  
  // Try to load from mock JSON file
  try {
    const mockData = await import(`../../mock/${SECTION_NAME}Products.json`)
    products.value = mockData.default || []
    persistProducts() // Save to localStorage
  } catch (error) {
    console.warn(`No mock file found for ${SECTION_NAME}`, error)
    products.value = []
  }
}

// Save to localStorage (for session persistence)
const persistProducts = () => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(products.value))
}

// Import JSON
const importJson = (mode: 'append' | 'replace') => {
  if (!jsonInput.value.trim()) {
    showNotification('warning', 'No JSON', 'Please paste JSON data first.')
    return
  }
  
  try {
    const parsed = JSON.parse(jsonInput.value)
    if (!Array.isArray(parsed)) {
      showNotification('error', 'Invalid JSON', 'JSON must be an array.')
      return
    }
    
    if (mode === 'replace') {
      products.value = []
    }
    
    const baseId = products.value.length > 0 ? Math.max(...products.value.map(p => p.id)) + 1 : 1
    
    parsed.forEach((item, index) => {
      products.value.push({
        id: baseId + index,
        category: item.category || `${SECTION_NAME} Section`,
        name: item.name || item.section || SECTION_NAME,
        sku: item.sku || item.size || '',
        size: String(item.size || ''),
        stock: Number(item.stock || item.balanceStock || 0),
        reorder_level: Number(item.reorder_level || 5),
        rate: Number(item.rate || 0),
        value: Number(item.value || 0),
        in_qty: 0,
        out_qty: 0
      })
    })
    
    persistProducts()
    jsonInput.value = ''
    showNotification('success', 'Imported', `${mode === 'replace' ? 'Replaced' : 'Added'} ${parsed.length} products`)
  } catch (e) {
    showNotification('error', 'Invalid JSON', 'Failed to parse JSON.')
  }
}

// Download JSON
const downloadJson = () => {
  const dataStr = JSON.stringify(products.value, null, 2)
  const dataBlob = new Blob([dataStr], { type: 'application/json' })
  const url = URL.createObjectURL(dataBlob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${SECTION_NAME}Products.json`
  link.click()
  URL.revokeObjectURL(url)
}

const visibleProducts = computed(() => {
  let list = products.value.slice()
  
  if (searchTerm.value) {
    const q = searchTerm.value.toLowerCase()
    list = list.filter(p => p.name.toLowerCase().includes(q) || p.size.toLowerCase().includes(q))
  }
  
  list.forEach(p => { p.value = p.stock * p.rate })
  
  return list
})

const filteredForInout = computed(() => {
  let list = products.value.slice()
  const f = inoutFilters.value
  if (f.name) list = list.filter(p => p.name.toLowerCase().includes(f.name.toLowerCase()))
  if (f.size) list = list.filter(p => p.size.toLowerCase().includes(f.size.toLowerCase()))
  if (f.stockMin != null) list = list.filter(p => p.stock >= (f.stockMin || 0))
  if (f.stockMax != null) list = list.filter(p => p.stock <= (f.stockMax || 0))
  return list
})

const startEdit = (product: Product, field: keyof Product) => { 
  editingCell.value = `${product.id}-${String(field)}`
  editValue.value = String((product as any)[field] ?? '')
}

const cancelEdit = () => { 
  editingCell.value = null
  editValue.value = ''
}

const saveCell = (product: Product, field: keyof Product) => { 
  const val = (field === 'stock' || field === 'rate' || field === 'reorder_level' || field === 'in_qty' || field === 'out_qty') ? 
    Number(editValue.value) : editValue.value
  
  const idx = products.value.findIndex(p => p.id === product.id)
  if (idx !== -1) {
    const p = products.value[idx]
    
    if (field === 'in_qty' && val > 0) {
      p.stock += val
      p.in_qty = 0
      showNotification('success', 'Stock Updated', `Added ${val} units`)
    } else if (field === 'out_qty' && val > 0) {
      if (p.stock >= val) {
        p.stock -= val
        p.out_qty = 0
        showNotification('success', 'Stock Updated', `Removed ${val} units`)
      } else {
        showNotification('error', 'Insufficient Stock', `Only ${p.stock} available`)
        cancelEdit()
        return
      }
    } else {
      (p as any)[field] = val
    }
    
    p.value = p.stock * p.rate
    persistProducts()
    
    if (field !== 'in_qty' && field !== 'out_qty') {
      showNotification('success', 'Updated', `Updated ${String(field)}`)
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
  const id = products.value.length > 0 ? Math.max(...products.value.map(p => p.id)) + 1 : 1
  const c = createForm.value
  products.value.push({ 
    id, 
    category: `${SECTION_NAME} Section`,
    name: c.name || SECTION_NAME,
    sku: c.size,
    size: c.size,
    stock: c.stock,
    reorder_level: c.reorder_level,
    rate: c.rate,
    value: c.stock * c.rate,
    in_qty: 0,
    out_qty: 0
  })
  persistProducts()
  showNotification('success', 'Created', 'Product created')
  showCreateModal.value = false
}

const onDelete = (id: number) => { 
  if (!confirm('Delete product?')) return
  products.value = products.value.filter(p => p.id !== id)
  persistProducts()
  showNotification('success', 'Deleted', 'Product removed')
}

const selectAll = () => { selectedIds.value = filteredForInout.value.map(p => p.id) }
const clearSelection = () => { selectedIds.value = [] }
const openInoutModal = () => { showInoutModal.value = true }

const markSelected = (action: 'IN'|'OUT') => {
  if (!selectedIds.value.length) { 
    showNotification('warning', 'No Selection', 'Please select items')
    return 
  }
  const qtyStr = prompt(`Quantity to ${action}`, '1')
  const qty = Math.max(0, Number(qtyStr || 0))
  if (!qty) return
  
  for (const id of selectedIds.value) {
    const p = products.value.find(p => p.id === id)
    if (!p) continue
    
    if (action === 'IN') { 
      p.stock += qty
    } else { 
      if (p.stock >= qty) {
        p.stock -= qty
      } else {
        showNotification('error', 'Insufficient', `${p.name} has only ${p.stock} units`)
        continue
      }
    }
    p.value = p.stock * p.rate
  }
  
  persistProducts()
  selectedIds.value = []
  showInoutModal.value = false
  showNotification('success', `${action} Complete`, `Updated ${selectedIds.value.length} products`)
}

onMounted(() => {
  loadProducts()
})
</script>