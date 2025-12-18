<script setup lang="ts">
import { ref } from 'vue'
import * as XLSX from 'xlsx'

interface StockEntry {
  type: 'in' | 'out'
  qty: number
  time: string
}

interface VeeBeltData {
  rackNo: string
}

interface PolyBeltData {
  section: string
  ribs: number
  ratePerRib: number
  finalValue: number
  remarks: string
}

interface TPUBeltData {
  width: number
  meter: number
  rate: number
  totalValue: number
  section: string
  remarks: string
}

interface InventoryPayload {
  section: string
  size: number
  balanceStock: number
  rate: number
  value: number
  inStock: number
  outStock: number
  balanceStockAfterInOut: number
  category: string
  name: string
  remarks: string
  inStockTime: string
  outStockTime: string
  stockEntries: StockEntry[]
  vbelt?: VeeBeltData
  polybelt?: PolyBeltData
  tpubelt?: TPUBeltData
}

const emit = defineEmits(['data-uploaded'])

const fileInput = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)
const uploadStatus = ref('')
const parsedData = ref<InventoryPayload[]>([])

// Backend URL for bulk upload
const BACKEND_URL = ref('http://localhost:8000/api/products/bulk-upload')
const USE_MOCK = ref(false) // Set to true for localStorage testing

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
    uploadStatus.value = 'Please select a valid Excel file (.xlsx or .xls)'
    return
  }
  
  isUploading.value = true
  uploadStatus.value = 'Processing Excel file...'
  
  try {
    const data = await parseExcelFile(file)
    parsedData.value = data
    
    if (USE_MOCK.value) {
      // Mock upload - save to localStorage
      await mockUpload(data)
    } else {
      // Real backend upload
      await uploadToBackend(data)
    }
    
    emit('data-uploaded', data)
    uploadStatus.value = `Successfully processed ${data.length} records`
  } catch (error) {
    console.error('Upload error:', error)
    uploadStatus.value = 'Error processing file. Please check the format.'
  } finally {
    isUploading.value = false
  }
}

const parseExcelFile = (file: File): Promise<InventoryPayload[]> => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    
    reader.onload = (e) => {
      try {
        const data = new Uint8Array(e.target?.result as ArrayBuffer)
        const workbook = XLSX.read(data, { type: 'array' })
        const sheetName = workbook.SheetNames[0]
        const worksheet = workbook.Sheets[sheetName]
        
        // Get raw data without headers to analyze structure
        const rawData = XLSX.utils.sheet_to_json(worksheet, { header: 1 })
        
        let parsedData: InventoryPayload[] = []
        
        // Detect file type and parse accordingly
        if (detectPolyBeltFormat(rawData)) {
          parsedData = parsePolyBeltFile(rawData)
        } else if (detectVBeltFormat(rawData)) {
          parsedData = parseVBeltFile(rawData, file.name)
        } else {
          // Fallback to standard parsing
          const jsonData = XLSX.utils.sheet_to_json(worksheet)
          parsedData = parseStandardFormat(jsonData)
        }
        
        resolve(parsedData)
      } catch (error) {
        reject(error)
      }
    }
    
    reader.onerror = () => reject(new Error('Failed to read file'))
    reader.readAsArrayBuffer(file)
  })
}

// Detect Poly Belt format (has PK, RIBS, Rate Per Ribs, FINAL VALUE columns)
const detectPolyBeltFormat = (rawData: any[][]): boolean => {
  if (rawData.length < 1) return false
  const firstRow = rawData[0] || []
  const headers = firstRow.map(h => String(h).toLowerCase())
  return headers.includes('pk') && headers.includes('ribs') && headers.includes('final value')
}

// Detect V-Belt format (has Sz., O/B, In, OUT, Bal. pattern)
const detectVBeltFormat = (rawData: any[][]): boolean => {
  if (rawData.length < 3) return false
  
  // Look for the header row with Sz., O/B, In, OUT, Bal. pattern
  for (let i = 0; i < Math.min(5, rawData.length); i++) {
    const row = rawData[i] || []
    const rowStr = row.map(cell => String(cell || '').toLowerCase())
    if (rowStr.includes('sz.') && rowStr.includes('o/b') && rowStr.includes('bal.')) {
      return true
    }
  }
  return false
}

// Parse Poly Belt files (PK section with ribs data)
const parsePolyBeltFile = (rawData: any[][]): InventoryPayload[] => {
  const results: InventoryPayload[] = []
  
  // First row should be headers: PK, RIBS, Rate Per Ribs, etc.
  const headers = rawData[0] || []
  
  for (let i = 1; i < rawData.length; i++) {
    const row = rawData[i] || []
    if (row.length < 2) continue
    
    const size = Number(row[0]) || 0
    const ribs = Number(row[1]) || 0
    const ratePerRib = Number(row[2]) || 0
    const finalValue = Number(row[6]) || (ribs * ratePerRib)
    const remarks = String(row[7] || '')
    
    if (size > 0) {
      const payload: InventoryPayload = {
        section: 'PK', // Separate section field
        size, // Separate size field
        balanceStock: ribs, // Use ribs as stock for poly belts
        rate: ratePerRib,
        value: finalValue,
        inStock: 0,
        outStock: 0,
        balanceStockAfterInOut: ribs,
        category: 'PK Section',
        name: `PK-${size}`,
        remarks,
        inStockTime: new Date().toISOString(),
        outStockTime: '',
        stockEntries: [],
        polybelt: {
          section: 'PK',
          ribs,
          ratePerRib,
          finalValue,
          remarks
        }
      }
      results.push(payload)
    }
  }
  
  return results
}

// Parse V-Belt files (3V, 5V, E sections with rack numbers and stock data)
const parseVBeltFile = (rawData: any[][], fileName: string): InventoryPayload[] => {
  const results: InventoryPayload[] = []
  
  // Determine section from filename with comprehensive mapping
  let defaultSection = 'vbelt'
  let category = 'V Section'
  
  // V-Belt Classical Sections
  if (fileName.includes('A') && fileName.includes('Section')) {
    defaultSection = 'A'
    category = 'A Section'
  } else if (fileName.includes('B') && fileName.includes('Section')) {
    defaultSection = 'B'
    category = 'B Section'
  } else if (fileName.includes('C') && fileName.includes('Section')) {
    defaultSection = 'C'
    category = 'C Section'
  } else if (fileName.includes('D') && fileName.includes('Section')) {
    defaultSection = 'D'
    category = 'D Section'
  } else if (fileName.includes('E') && fileName.includes('Section')) {
    defaultSection = 'E'
    category = 'E Section'
  }
  // V-Belt Wedge Sections
  else if (fileName.includes('SPA')) {
    defaultSection = 'SPA'
    category = 'SPA Section'
  } else if (fileName.includes('SPB')) {
    defaultSection = 'SPB'
    category = 'SPB Section'
  } else if (fileName.includes('SPC')) {
    defaultSection = 'SPC'
    category = 'SPC Section'
  } else if (fileName.includes('SPZ')) {
    defaultSection = 'SPZ'
    category = 'SPZ Section'
  }
  // V-Belt Narrow Sections
  else if (fileName.includes('3V')) {
    defaultSection = '3V'
    category = '3V Section'
  } else if (fileName.includes('5V')) {
    defaultSection = '5V'
    category = '5V Section'
  } else if (fileName.includes('8V')) {
    defaultSection = '8V'
    category = '8V Section'
  }
  // Cogged Belts
  else if (fileName.toLowerCase().includes('cogged')) {
    // Try to detect specific cogged belt type
    if (fileName.includes('AX')) {
      defaultSection = 'AX'
      category = 'AX Section'
    } else if (fileName.includes('BX')) {
      defaultSection = 'BX'
      category = 'BX Section'
    } else if (fileName.includes('CX')) {
      defaultSection = 'CX'
      category = 'CX Section'
    } else {
      defaultSection = 'AX'
      category = 'AX Section' // Default to AX for generic cogged
    }
  }
  
  // Find the header row and data rows
  let headerRowIndex = -1
  let dataStartIndex = -1
  let headers: string[] = []
  
  for (let i = 0; i < Math.min(5, rawData.length); i++) {
    const row = rawData[i] || []
    const rowStr = row.map(cell => String(cell || '').toLowerCase())
    if (rowStr.includes('sz.') && rowStr.includes('o/b')) {
      headerRowIndex = i
      dataStartIndex = i + 1
      headers = row.map(cell => String(cell || '').toLowerCase())
      break
    }
  }
  
  if (headerRowIndex === -1 || dataStartIndex >= rawData.length) {
    return results
  }
  
  // For 3V belts, look for "rack" and "sz" columns
  const is3VFormat = fileName.includes('3V')
   const isveeFormat = fileName.includes('A') || fileName.includes('B') || fileName.includes('C') || fileName.includes('D') || fileName.includes('E') ||
                     fileName.includes('SPA') || fileName.includes('SPB') || fileName.includes('SPC') || fileName.includes('SPZ') ||
                     fileName.includes('3V') || fileName.includes('5V') || fileName.includes('8V')
  let rackColumnIndex = -1
  let sizeColumnIndex = -1
  let stockColumnIndex = -1
  
  if (is3VFormat) {
    // Look for "rack" and "sz" columns in 3V files
    rackColumnIndex = headers.findIndex(h => h.includes('rack'))
    sizeColumnIndex = headers.findIndex(h => h.includes('sz'))
    stockColumnIndex = headers.findIndex(h => h.includes('o/b') || h.includes('stock') || h.includes('balance'))
  }
  
  if (isveeFormat) {
    // Look for "rack" and "sz" columns in 3V files
    rackColumnIndex = headers.findIndex(h => h.includes('rack'))
    sizeColumnIndex = headers.findIndex(h => h.includes('sz'))
    stockColumnIndex = headers.findIndex(h => h.includes('o/b') || h.includes('stock') || h.includes('balance'))
  }
  // Parse data rows
  for (let i = dataStartIndex; i < rawData.length; i++) {
    const row = rawData[i] || []
    if (row.length < 3) continue
    
    let section = defaultSection
    let size = ''
    let balanceStock = 0
    
    if (is3VFormat && rackColumnIndex >= 0 && sizeColumnIndex >= 0) {
      // For 3V: rack = section, sz = size
      section = String(row[rackColumnIndex] || defaultSection)
      size = String(row[sizeColumnIndex] || '')
      balanceStock = Number(row[stockColumnIndex] || row[2]) || 0
    } else if (isveeFormat && rackColumnIndex >= 0 && sizeColumnIndex >= 0) {
      // For vee belts: rack = section, sz = size
      section = String(row[rackColumnIndex] || defaultSection)
      size = String(row[sizeColumnIndex] || '')
      balanceStock = Number(row[stockColumnIndex] || row[2]) || 0
    } else  {
      // For other formats: first column = section name, second = size/rack number
      const sectionName = String(row[0] || '')
      const rackNo = row[1]
      
      // Extract section from the data if available, otherwise use filename section
      section = sectionName || defaultSection
      size = String(rackNo || '')
      balanceStock = Number(row[2]) || 0 // O/B (Opening Balance)
    }
    
    if (section && size) {
      const payload: InventoryPayload = {
        section,
        size: Number(size) || 0,
        balanceStock,
        rate: 25.0, // Default rate, can be updated later
        value: balanceStock * 25.0,
        inStock: 0,
        outStock: 0,
        balanceStockAfterInOut: balanceStock,
        category,
        name: `${section.toUpperCase()}-${size}`,
        remarks: '',
        inStockTime: new Date().toISOString(),
        outStockTime: '',
        stockEntries: [],
        vbelt: {
          rackNo: String(size)
        }
      }
      results.push(payload)
    }
  }
  
  return results
}

// Parse standard format files
const parseStandardFormat = (jsonData: any[]): InventoryPayload[] => {
  return jsonData.map((row: any) => {
    const normalizedRow = normalizeRowKeys(row)
    
    const section = normalizedRow.section || 'veebelt'
    const size = Number(normalizedRow.size) || 0
    const balanceStock = Number(normalizedRow.balancestock || normalizedRow.stock || normalizedRow.balance) || 0
    const rate = Number(normalizedRow.rate || normalizedRow.price) || 25.0
    const value = Number(normalizedRow.value) || (balanceStock * rate)
    
    const payload: InventoryPayload = {
      section,
      size,
      balanceStock,
      rate,
      value,
      inStock: Number(normalizedRow.instock || normalizedRow.in) || 0,
      outStock: Number(normalizedRow.outstock || normalizedRow.out) || 0,
      balanceStockAfterInOut: balanceStock,
      category: normalizedRow.category || `${section.toUpperCase()} Section`,
      name: normalizedRow.name || `${section.toUpperCase()}-${size}`,
      remarks: normalizedRow.remarks || '',
      inStockTime: new Date().toISOString(),
      outStockTime: '',
      stockEntries: []
    }
    
    return payload
  })
}

// Helper function to normalize Excel column headers
const normalizeRowKeys = (row: any): any => {
  const normalized: any = {}
  
  Object.keys(row).forEach(key => {
    const normalizedKey = key.toLowerCase().replace(/[\s_-]/g, '')
    normalized[normalizedKey] = row[key]
  })
  
  return normalized
}

const mockUpload = async (data: InventoryPayload[]): Promise<void> => {
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 1000))
  
  // Save to localStorage for now
  const existingData = JSON.parse(localStorage.getItem('inventoryData') || '[]')
  const updatedData = [...existingData, ...data]
  localStorage.setItem('inventoryData', JSON.stringify(updatedData))
  
  console.log('Mock upload completed:', data)
}

const uploadToBackend = async (data: InventoryPayload[]): Promise<void> => {
  // Transform the data to match the backend API format
  const transformedData = data.map(item => ({
    name: item.name,
    sku: `${item.section}-${item.size}`,
    section: item.section,
    size: String(item.size),
    stock: item.balanceStock,
    dimension: item.dimension || null,
    reorder_level: item.reorder_level || null,
    items_per_sleve: item.items_per_sleve || null,
    rate: item.rate,
    value: item.value
  }))

  const response = await fetch(BACKEND_URL.value, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ data: transformedData })
  })
  
  if (!response.ok) {
    const errorText = await response.text()
    throw new Error(`Upload failed: ${response.statusText} - ${errorText}`)
  }
  
  const result = await response.json()
  console.log('Backend upload completed:', result)
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const downloadTemplate = () => {
  // Create multiple sheets for different formats
  const wb = XLSX.utils.book_new()
  
  // V-Belt format template (like 5V, 3V, SPA sections)
  // V-Belt format template (A, B, C, D sections)
const vbeltData = [
  ['RACK No.', 'Size', 'Balance stock', 'Rate', 'Value'], // Header row
  ['A', 18, 0, 18.9, 0],
  ['A', 19, 0, 19.95, 0],
  ['A', 20, 0, 21, 0],
  ['A', 21, 0, 22.05, 0],
  ['A', 22, 0, 23.1, 0],
  ['A', 23, 0, 24.15, 0],
  ['A', 24, 18, 25.2, 453.6],
  ['A', 25, 24, 26.25, 630],
  ['A', 26, 13, 27.3, 354.9],
  ['A', 27, 256, 28.35, 7257.6]
]
const vbeltWs = XLSX.utils.aoa_to_sheet(vbeltData)
XLSX.utils.book_append_sheet(wb, vbeltWs, 'V-Belt Format')

  // Poly Belt format template
  const polyData = [
    ['PK', 'RIBS', 'Rate Per Ribs', 'TOTAL SLV RATE', 'PER MM RATE', 'TOTAL MM RATE', 'FINAL VALUE', 'Remark'],
    [688, 97, 15.98, 1550.17, 0.0355, 1550.17, 1550.17, ''],
    [755, 80, 17.54, 1402.99, 0.0390, 1402.99, 1402.99, '44Rib + 36Rib'],
    [815, 80, 18.93, 1514.49, 0.0421, 1514.49, 1514.49, '64Rib + 4Rib X 4Nos']
  ]
  const polyWs = XLSX.utils.json_to_sheet(polyData)
  XLSX.utils.book_append_sheet(wb, polyWs, 'Poly Belt Format')
  
  // Standard format template
  const standardData = [
    {
      section: 'veebelt',
      size: 100,
      balanceStock: 50,
      rate: 25.50,
      value: 1275.00,
      inStock: 10,
      outStock: 5,
      category: 'A Section',
      name: 'A100 V-Belt',
      remarks: 'Standard belt'
    },
    {
      section: 'polybelt', 
      size: 200,
      balanceStock: 30,
      rate: 45.00,
      value: 1350.00,
      inStock: 8,
      outStock: 3,
      category: 'PK Section',
      name: 'PK200 Poly Belt',
      remarks: 'Multi-rib belt'
    }
  ]
  const standardWs = XLSX.utils.json_to_sheet(standardData)
  XLSX.utils.book_append_sheet(wb, standardWs, 'Standard Format')
  
  XLSX.writeFile(wb, 'inventory_templates.xlsx')
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Excel Data Upload</h3>
      <button
        @click="downloadTemplate"
        class="px-4 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors"
      >
        Download Template
      </button>
    </div>
    
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
      <input
        ref="fileInput"
        type="file"
        accept=".xlsx,.xls"
        @change="handleFileUpload"
        class="hidden"
      >
      
      <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      
      <div class="mt-4">
        <button
          @click="triggerFileInput"
          :disabled="isUploading"
          class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors"
        >
          {{ isUploading ? 'Processing...' : 'Choose Excel File' }}
        </button>
      </div>
      
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Upload .xlsx or .xls files with inventory data
      </p>
      
      <div v-if="uploadStatus" class="mt-4 p-3 rounded-lg" :class="uploadStatus.includes('Error') ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">
        {{ uploadStatus }}
      </div>
    </div>
    
    <!-- Configuration Panel -->
    <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Upload Configuration</h4>
      <div class="flex items-center space-x-4">
        <label class="flex items-center">
          <input
            v-model="USE_MOCK"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
          >
          <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use Mock Upload (Local Storage)</span>
        </label>
      </div>
      <div v-if="!USE_MOCK" class="mt-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Backend URL:</label>
        <input
          v-model="BACKEND_URL"
          type="url"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
          placeholder="http://localhost:8000/api/inventory/upload"
        >
      </div>
    </div>
    
    <!-- Data Preview -->
    <div v-if="parsedData.length > 0" class="mt-6">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Data Preview ({{ parsedData.length }} records)</h4>
      <div class="max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Section</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Stock</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rate</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Value</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
            <tr v-for="(item, index) in parsedData.slice(0, 10)" :key="index">
              <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ item.section }}</td>
              <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ item.name }}</td>
              <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ item.balanceStock }}</td>
              <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">₹{{ item.rate }}</td>
              <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">₹{{ item.value }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>