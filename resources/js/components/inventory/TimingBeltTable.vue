<template>
  <div class="transition-all duration-300" :class="props.sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
      <div class=" z-30 bg-gray-50 dark:bg-gray-900 pb-4">

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
      <!--  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
          <div class="text-sm text-gray-600 dark:text-gray-400">Total Stock</div>
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ Number(totalStock || 0).toFixed(2) }}</div>
        </div> -->
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
      <div class="mb-4  sticky  bg-white dark:bg-gray-800 rounded-lg shadow-md p-3">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Search -->
          <input 
            v-model="searchTerm" 
            placeholder="Search section / size" 
            class="px-3 py-1.5 text-sm border rounded bg-white dark:bg-gray-700 dark:text-white"
          />
          


          <!-- Quick Filter Buttons -->
       <!--   <button 
            @click="toggleOutOfStockFilter" 
            :class="showOutOfStockOnly ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm rounded hover:opacity-80 transition-colors"
          >
            {{ showOutOfStockOnly ? '✓ Out of Stock' : 'Out of Stock' }}
          </button> -->
          
          <!-- JSON Import/Export Buttons -->
          <div class="ml-auto flex items-center gap-2">
         <!--   <button @click="showImportModal = true" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
              Imporrt JSON
            </button>
            <button @click="showExcelImportModal = true" class="px-3 py-1.5 text-sm bg-orange-600 text-white rounded hover:bg-orange-700">
              Import Excel
            </button>
            <button @click="downloadJSON" class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">
              Download JSON
            </button>  -->
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
                <th class="py-3 px-3">{{ props.section?.startsWith('NEOPRENE') ? 'FULL SLEEVE' : 'FULL SLEEVE' }}</th>
                <th class="py-3 px-3 text-center">MM Sleeve</th>
                <th class="py-3 px-3 text-right">Total Value</th>
                <th class="py-3 px-3">Remark</th>
                <th class="py-3 px-3 text-center">IN/OUT</th>
                <th class="py-3 px-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
          <tr v-for="p in visibleProducts" :key="p?.id || 'unknown'" class="border-t hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-section`">
                    <input v-model="editValue" @keyup.enter="saveCell(p, 'section')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'section')" class="cursor-pointer font-bold text-black dark:text-white">{{ p?.section || '-' }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-size`">
                    <input v-model="editValue"  @keyup.enter="saveCell(p, 'size')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'size')" class="cursor-pointer font-bold text-black dark:text-white">{{ p?.size || '-' }}</div>
                </td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-type`">
                    <input v-model="editValue" @keyup.enter="saveCell(p, 'type')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'type')" class="cursor-pointer font-bold text-black dark:text-white text-center">
                    {{ p?.type || '' }}
                  </div>
                </td>

                <!-- MM Sleeve -->
                <td class="py-2 px-3 text-center">
                  <div v-if="editingCell === `${p?.id}-total_mm`">
                    <input v-model.number="editValue" type="number" step="0.01" min="0"  @keyup.enter="saveCell(p, 'total_mm')" @keyup.esc="cancelEdit" class="w-20 p-1 border rounded text-center" />
                  </div>
                  <div v-else @click="startEdit(p, 'total_mm')" class="cursor-pointer">
                    <span :class="getStockClass(p)" class="font-medium">{{ Number(p?.total_mm || 0).toFixed(2) }}mm</span>
                  </div>
                </td>

                <!-- Value -->
                <td class="py-2 px-3 text-right font-medium text-green-600">₹{{ Number(p?.value || 0).toFixed(2) }}</td>

                <td class="py-2 px-3">
                  <div v-if="editingCell === `${p?.id}-remark`">
                    <input v-model="editValue" @keyup.enter="saveCell(p, 'remark')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded" />
                  </div>
                  <div v-else @click="startEdit(p, 'remark')" class="cursor-pointer">{{ p?.remark || '-' }}</div>
                </td>

                <td class="py-2 px-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <!-- Total MM Operations -->
                    <div class="flex flex-col gap-1">
                      <div class="flex gap-1">
                        <button @click="showInOutModal(p, 'IN', 'total_mm')" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                          IN
                        </button>
                        <button @click="showInOutModal(p, 'OUT', 'total_mm')" class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                          OUT
                        </button>
                      </div>
                      <!-- Full Sleeve Operations -->
                  <!--    <div class="flex gap-1">
                        <button @click="showInOutModal(p, 'IN', 'type')" class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                          IN
                        </button>
                        <button @click="showInOutModal(p, 'OUT', 'type')" class="px-2 py-1 text-xs bg-orange-600 text-white rounded hover:bg-orange-700">
                          OUT
                        </button>
                      </div> -->
                    </div>
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

            <label>{{ props.section?.startsWith('NEOPRENE') ? 'Full Sleeve' : 'Full Sleeve' }}
              <input v-model="createForm.type" class="w-full p-2 border rounded" :placeholder="props.section?.startsWith('NEOPRENE') ? 'e.g., 18' : 'e.g., 18, 21, 10, 24'" />
            </label>
            
            <label> MM Sleeve
              <input v-model.number="createForm.total_mm" type="number" step="0.01" class="w-full p-2 border rounded" min="0" placeholder="Total inventory in mm" />
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
              <div v-if="inOutForm.unit_type === 'total_mm'">
                Current Total MM: {{ selectedProduct?.total_mm || 0 }}mm
              </div>
              <div v-else>
                Current Type (Full Sleeve): {{ selectedProduct?.type || 0 }}
              </div>
            </div>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium mb-1">Unit Type</label>
              <div class="flex gap-2">
                <label class="flex items-center">
                  <input v-model="inOutForm.unit_type" type="radio" value="total_mm" class="mr-2" />
                   MM Sleeve
                </label>
                <label class="flex items-center">
                  <input v-model="inOutForm.unit_type" type="radio" value="type" class="mr-2" />
                  Full Sleeve
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">
                Quantity ({{ inOutForm.unit_type === 'total_mm' ? 'mm' : 'full sleeves' }})
              </label>
              <input 
                v-model.number="inOutForm.quantity" 
                type="number" 
                :step="inOutForm.unit_type === 'total_mm' ? '0.01' : '1'"
                min="0.01"
                class="w-full p-2 border rounded" 
                :placeholder="`Enter quantity in ${inOutForm.unit_type === 'total_mm' ? 'mm' : 'full sleeves'}`"
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
              {{ inOutAction }} {{ inOutForm.unit_type === 'total_mm' ? 'MM' : 'Full Sleeve' }}
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

    <!-- Excel Import Modal -->
    <div v-if="showExcelImportModal" class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="showExcelImportModal = false"></div>
      <div class="relative bg-white dark:bg-gray-800 rounded p-6 w-full max-w-2xl z-50">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Import Timing Belts from Excel</h3>
        
        <!-- Belt Type Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Belt Type
          </label>
          <select v-model="excelImportForm.beltType" class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white">
            <option value="commercial">Commercial Timing Belts</option>
            <option value="neoprene">Neoprene Timing Belts</option>
          </select>
        </div>

        <!-- File Upload -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Excel File (.xlsx, .xls)
          </label>
          <input 
            type="file" 
            ref="excelFileInput"
            @change="handleExcelFileSelect"
            accept=".xlsx,.xls"
            class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white"
          />
        </div>

        <!-- Expected Format Info -->
        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
          <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Expected Excel Format:</h4>
          <div v-if="excelImportForm.beltType === 'commercial'" class="text-sm text-blue-700 dark:text-blue-300">
            <p><strong>Commercial:</strong> XL | FULL SLEVE | MM | TOTAL(MM) | RATE PER SLV | TOTAL MM RATE | FINAL VALUE</p>
            <p class="mt-1">Section will be detected from header (XL, L, 5M, T5, T10, AT5, AT10)</p>
            <p class="mt-1 text-xs">Example: 100 | 18 | 390,420,430... | 4690 | 417 | 4346 | 11852</p>
          </div>
          <div v-else class="text-sm text-blue-700 dark:text-blue-300">
            <p><strong>Neoprene:</strong> FULL SLEEVE | MM | RATE PER SLEEVE | TOTAL RATE</p>
          </div>
        </div>

        <!-- Preview Data -->
        <div v-if="excelPreviewData.length > 0" class="mb-4">
          <h4 class="font-medium text-gray-900 dark:text-white mb-2">
            Preview Data ({{ excelPreviewData.length }} items)
          </h4>
          <div class="max-h-60 overflow-y-auto border rounded">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="p-2 text-left">Section</th>
                  <th class="p-2 text-left">Size</th>
                  <th class="p-2 text-left">Type</th>
                  <th class="p-2 text-left">Total MM</th>
                  <th class="p-2 text-left">Value</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in excelPreviewData.slice(0, 10)" :key="index" class="border-t">
                  <td class="p-2">{{ item.section }}</td>
                  <td class="p-2">{{ item.size }}</td>
                  <td class="p-2">{{ item.type }}</td>
                  <td class="p-2">{{ item.total_mm }}</td>
                  <td class="p-2">₹{{ item.value }}</td>
                </tr>
              </tbody>
            </table>
            <div v-if="excelPreviewData.length > 10" class="p-2 text-center text-gray-500 text-sm">
              ... and {{ excelPreviewData.length - 10 }} more items
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-2">
          <button 
            @click="showExcelImportModal = false" 
            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
          >
            Cancel
          </button>
          <button 
            @click="processExcelFile" 
            :disabled="!excelImportForm.selectedFile || processingExcel"
            class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 disabled:bg-gray-400"
          >
            {{ processingExcel ? 'Processing...' : 'Process Excel' }}
          </button>
          <button 
            v-if="excelPreviewData.length > 0"
            @click="importToDatabase" 
            :disabled="importingToDb"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:bg-gray-400"
          >
            {{ importingToDb ? 'Importing...' : 'Import to Database' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useTimingBelts, type TimingBelt, type Transaction } from '../../composables/useTimingBelts'
import axios from '@/lib/axios'

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
const savingCell = ref<string|null>(null)

const showCreateModal = ref(false)

// Excel Import functionality
const showExcelImportModal = ref(false)
const excelFileInput = ref<HTMLInputElement | null>(null)
const excelImportForm = ref({
  beltType: 'commercial',
  selectedFile: null as File | null
})
const excelPreviewData = ref<any[]>([])
const processingExcel = ref(false)
const importingToDb = ref(false)
const createForm = ref({ 
  section: props.section || '',
  size: '', 
  type: props.section?.startsWith('NEOPRENE') ? 'FULL SLEEVE' : '',
  total_mm: 0,
  full_sleeve: 0,
  rate: 0,
  rate_per_sleeve: 0,
  remark: ''
})

// IN/OUT Modal
const showInOutModalFlag = ref(false)
const selectedProduct = ref<TimingBelt | null>(null)
const inOutAction = ref<'IN' | 'OUT'>('IN')
const inOutForm = ref({
  unit_type: 'total_mm' as 'total_mm' | 'type',
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
    "section": "${props.section || 'XL'}",
    "size": "150",
    "type": "${props.section?.startsWith('NEOPRENE') ? 'FULL SLEEVE' : '18'}",
    "total_mm": 1000.00,
    "rate": 2.50,
    "value": 11852.00,
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
  savingCell.value = null
}

const saveCell = async (product: TimingBelt | null | undefined, field: keyof TimingBelt) => {
  if (!product || !product.id) {
    cancelEdit()
    return
  }
  
  const cellId = `${product.id}-${String(field)}`
  
  // Prevent multiple saves for the same cell
  if (!editingCell.value || editingCell.value !== cellId || savingCell.value === cellId) {
    return
  }
  
  const val = ['total_mm', 'rate'].includes(field) ? Number(editValue.value) : editValue.value
  
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

const getStockClass = (p: TimingBelt | null | undefined) => { 
  if (!p) return 'text-gray-400'
  const currentStock = p.total_mm || 0
  if (currentStock <= 0) return 'text-black-600'
  if (currentStock <= (p.reorder_level || 5)) return 'text-yellow-600'
  return 'text-black-600'
}

const getSleeveStockClass = (p: TimingBelt | null | undefined) => { 
  if (!p) return 'text-gray-400'
  const currentStock = p.full_sleeve || 0
  if (currentStock <= 0) return 'text-red-600'
  if (currentStock <= (p.reorder_level || 5)) return 'text-yellow-600'
  return 'text-green-600'
}

const createProduct = async () => {
  try {
    await apiCreateProduct(createForm.value)
    showNotification('success', 'Created', 'Timing belt created successfully')
    showCreateModal.value = false
    createForm.value = { 
      section: props.section || '',
      size: '', 
      type: props.section?.startsWith('NEOPRENE') ? 'FULL SLEEVE' : '',
      total_mm: 0,
      full_sleeve: 0,
      rate: 0,
      rate_per_sleeve: 0,
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

const showInOutModal = (product: TimingBelt | null | undefined, action: 'IN' | 'OUT', unitType: 'total_mm' | 'type' = 'total_mm') => {
  if (!product) return
  selectedProduct.value = product
  inOutAction.value = action
  inOutForm.value = {
    unit_type: unitType,
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
      unit_type: inOutForm.value.unit_type,
      quantity: inOutForm.value.quantity,
      remark: inOutForm.value.remark
    })
    
    const operationUnit = inOutForm.value.unit_type === 'total_mm' ? 'mm' : 'full sleeves'
    showNotification('success', `${inOutAction.value} Complete`, 
      `${inOutAction.value} ${inOutForm.value.quantity} ${operationUnit} for ${selectedProduct.value?.section}-${selectedProduct.value?.size}`)
    
    showInOutModalFlag.value = false
    
  } catch (err: any) {
    showNotification('error', 'Error', err.response?.data?.message || 'Operation failed')
  } finally {
    savingCell.value = null
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

const importFromJSON = async () => {
  await importJSONData()
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

// Export to JSON
const exportToJSON = () => {
  downloadJSON()
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

// Excel Import Functions
const handleExcelFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    excelImportForm.value.selectedFile = file
    excelPreviewData.value = [] // Clear previous preview
  }
}

const processExcelFile = async () => {
  if (!excelImportForm.value.selectedFile) {
    showNotification('error', 'Error', 'Please select an Excel file')
    return
  }

  processingExcel.value = true
  
  try {
    const formData = new FormData()
    formData.append('excel_file', excelImportForm.value.selectedFile)
    formData.append('belt_type', excelImportForm.value.beltType)

    const response = await axios.post('/api/timing-belts/upload-excel', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (response.data.success) {
      excelPreviewData.value = response.data.data
      showNotification('success', 'Processed', `Successfully processed ${response.data.count} items from Excel`)
    } else {
      showNotification('error', 'Error', response.data.message || 'Failed to process Excel file')
    }
  } catch (error: any) {
    console.error('Excel processing error:', error)
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to process Excel file')
  } finally {
    processingExcel.value = false
  }
}

const importToDatabase = async () => {
  if (excelPreviewData.value.length === 0) {
    showNotification('error', 'Error', 'No data to import')
    return
  }

  importingToDb.value = true
  
  try {
    const response = await axios.post('/api/timing-belts/import-to-database', {
      data: excelPreviewData.value,
      section: excelPreviewData.value[0]?.section || 'UNKNOWN'
    })

    if (response.data.success) {
      showNotification('success', 'Imported', response.data.message)
      showExcelImportModal.value = false
      excelPreviewData.value = []
      excelImportForm.value.selectedFile = null
      
      // Refresh the products list
      await fetchProducts()
    } else {
      showNotification('error', 'Error', response.data.message || 'Failed to import to database')
    }
  } catch (error: any) {
    console.error('Database import error:', error)
    showNotification('error', 'Error', error.response?.data?.message || 'Failed to import to database')
  } finally {
    importingToDb.value = false
  }
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