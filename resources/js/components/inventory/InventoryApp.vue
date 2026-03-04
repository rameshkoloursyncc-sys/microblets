<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import axios from '@/lib/axios'
import { useAuth } from '../../composables/useAuth'
import FlowbiteTable from './FlowbiteTable_clean.vue'
import Sidebar from './SideBar.vue'
import CreateProduct from './CreateProduct.vue'
import { Datepicker } from 'flowbite'
import VeeBeltTable from './VeeBeltTable.vue'
import CoggedBeltTable from './CoggedBeltTable.vue'
import PolyBeltTable from './PolyBeltTable.vue'
import TpuBeltTable from './TpuBeltTable.vue'
import TimingBeltTable from './TimingBeltTable.vue'
import SpecialBeltTable from './SpecialBeltTable.vue'
import SettingsPage from './SettingsPage.vue'
import LoginPage from '../auth/LoginPage.vue'
import UserManagement from '../auth/UserManagement.vue'
import RawCarbonTable from './tables/RawCarbonTable.vue'
const currentView = ref('inventory')

// Stock alert functionality
const sendingAlert = ref(false)
const alertMessage = ref<{type: 'success' | 'error', text: string} | null>(null)
const sidebarCollapsed = ref(false)
const globalSectionQuery = ref('')
const globalSizeQuery = ref('')
const refreshKey = ref(0) // Used to trigger table refreshes

// Dashboard stats
const finishedGoodsStats = ref({
  totalProducts: 0,
  inStock: 0,
  lowStock: 0,
  outOfStock: 0,
  totalValue: 0,
  beltTypeValues: {
    vee: 0,
    cogged: 0,
    poly: 0,
    tpu: 0,
    timing: 0,
    special: 0
  }
})

const rawMaterialsStats = ref({
  totalProducts: 0,
  inStock: 0,
  lowStock: 0,
  outOfStock: 0,
  totalValue: 0,
  categoryValues: {
    'Carbon': 0,
    'Chemical': 0,
    'Cord - Cogged Belt': 0,
    'Cord - Timing Belt': 0,
    'Cord - Vee Belt': 0,
    'Fabric - Cogged Belt': 0,
    'Fabric - Timing Belt': 0,
    'Fabric - Vee Belt': 0,
    'Fabric - TPU Belt': 0,
    'Oil': 0,
    'Others': 0,
    'Resin': 0,
    'Rubber': 0,
    'TPU': 0,
    'Fibre Glass Cord': 0,
    'Steel Wire': 0,
    'Packing': 0,
    'Open': 0
  }
})

// Date filter state
const finishedGoodsStartDate = ref<string | null>(null)
const finishedGoodsEndDate = ref<string | null>(null)
const rawMaterialsStartDate = ref<string | null>(null)
const rawMaterialsEndDate = ref<string | null>(null)
const loadingSnapshot = ref(false)
const snapshotError = ref<string | null>(null)

// Authentication
const { user, isAuthenticated, isAdmin, initAuth, login, logout, startSessionKeepAlive, attemptSessionRestore } = useAuth()
const authLoading = ref(true)

// Handle login success
const handleLoginSuccess = (userData: any) => {
  login(userData)
  authLoading.value = false
}

// Handle logout
const handleLogout = async () => {
  await logout()
  currentView.value = 'inventory' // Reset to default view
}

// Initialize auth on mount
onMounted(async () => {
  console.log('InventoryApp mounting, initializing auth...')
  try {
    await initAuth()
    console.log('Auth initialized, user:', user.value?.name)
    
    // Start session keep-alive if user is authenticated
    if (isAuthenticated.value) {
      startSessionKeepAlive()
      // Load dashboard stats
      console.log('🚀 App mounted, loading dashboard stats...')
      await loadDashboardStats()
      await loadDieRequirements()
    }
  } catch (error) {
    console.error('Auth initialization error:', error)
  } finally {
    authLoading.value = false
    console.log('Auth loading complete, authenticated:', isAuthenticated.value)
  }
  
  // Add global error handler for session recovery
  window.addEventListener('unhandledrejection', async (event) => {
    if (event.reason?.response?.status === 401 && user.value) {
      console.log('Global 401 error detected, attempting session recovery')
      const restored = await attemptSessionRestore()
      if (restored) {
        console.log('Session recovered, preventing error propagation')
        event.preventDefault() // Prevent the error from propagating
      }
    }
  })
})

// Watch for view changes to load stats when dashboard is accessed
watch(currentView, async (newView) => {
  if (newView === 'dashboard' && isAuthenticated.value) {
    console.log('📊 Dashboard view accessed, loading stats...')
    await loadDashboardStats()
    await loadDieRequirements()
  }
})

// Handle sidebar toggle
const handleSidebarToggle = (collapsed: boolean) => {
  sidebarCollapsed.value = collapsed
}

// Load snapshot data for date range
const loadSnapshotData = async (startDate?: string, endDate?: string, section: 'finished' | 'raw' | 'both' = 'both') => {
  loadingSnapshot.value = true
  snapshotError.value = null
  
  try {
    console.log('📅 Loading snapshot data...', { startDate, endDate, section })
    
    // Build query params
    const params: any = {}
    if (startDate && endDate) {
      params.start_date = startDate
      params.end_date = endDate
    } else if (startDate) {
      params.date = startDate
    }
    
    const response = await axios.get('/api/dashboard/snapshot', { params })
    
    if (response.data.success) {
      const data = response.data.data
      console.log('📊 Snapshot data loaded:', data)
      
      // Check if it's aggregated data (date range) or single date
      const fg = data.finished_goods
      const rm = data.raw_materials
      
      // Update finished goods stats from snapshot (if requested)
      if (section === 'finished' || section === 'both') {
        finishedGoodsStats.value = {
          totalProducts: Math.round(fg.avg_total_products || fg.total_products || 0),
          inStock: Math.round(fg.avg_in_stock || fg.in_stock || 0),
          lowStock: Math.round(fg.avg_low_stock || fg.low_stock || 0),
          outOfStock: Math.round(fg.avg_out_of_stock || fg.out_of_stock || 0),
          totalValue: fg.avg_total_value || fg.total_value || 0,
          beltTypeValues: {
            vee: fg.categories?.vee_belts || fg.vee_belts_value || 0,
            cogged: fg.categories?.cogged_belts || fg.cogged_belts_value || 0,
            poly: fg.categories?.poly_belts || fg.poly_belts_value || 0,
            tpu: fg.categories?.tpu_belts || fg.tpu_belts_value || 0,
            timing: fg.categories?.timing_belts || fg.timing_belts_value || 0,
            special: fg.categories?.special_belts || fg.special_belts_value || 0
          }
        }
      }
      
      // Update raw materials stats from snapshot (if requested)
      if (section === 'raw' || section === 'both') {
        rawMaterialsStats.value = {
          totalProducts: Math.round(rm.avg_total_materials || rm.total_products || 0),
          inStock: Math.round(rm.avg_available || rm.in_stock || 0),
          lowStock: Math.round(rm.avg_low_stock || rm.low_stock || 0),
          outOfStock: Math.round(rm.avg_out_of_stock || rm.out_of_stock || 0),
          totalValue: rm.avg_total_value || rm.total_value || 0,
          categoryValues: {
            'Carbon': rm.categories?.Carbon || rm.carbon_value || 0,
            'Chemical': rm.categories?.Chemical || rm.chemical_value || 0,
            'Cord - Cogged Belt': rm.categories?.['Cord - Cogged Belt'] || rm.cord_cogged_value || 0,
            'Cord - Timing Belt': rm.categories?.['Cord - Timing Belt'] || rm.cord_timing_value || 0,
            'Cord - Vee Belt': rm.categories?.['Cord - Vee Belt'] || rm.cord_vee_value || 0,
            'Fabric - Cogged Belt': rm.categories?.['Fabric - Cogged Belt'] || rm.fabric_cogged_value || 0,
            'Fabric - Timing Belt': rm.categories?.['Fabric - Timing Belt'] || rm.fabric_timing_value || 0,
            'Fabric - Vee Belt': rm.categories?.['Fabric - Vee Belt'] || rm.fabric_vee_value || 0,
            'Fabric - TPU Belt': rm.categories?.['Fabric - TPU Belt'] || rm.fabric_tpu_value || 0,
            'Oil': rm.categories?.Oil || rm.oil_value || 0,
            'Others': rm.categories?.Others || rm.others_value || 0,
            'Resin': rm.categories?.Resin || rm.resin_value || 0,
            'Rubber': rm.categories?.Rubber || rm.rubber_value || 0,
            'TPU': rm.categories?.TPU || rm.tpu_value || 0,
            'Fibre Glass Cord': rm.categories?.['Fibre Glass Cord'] || rm.fibre_glass_cord_value || 0,
            'Steel Wire': rm.categories?.['Steel Wire'] || rm.steel_wire_value || 0,
            'Packing': rm.categories?.Packing || rm.packing_value || 0,
            'Open': rm.categories?.Open || rm.open_value || 0
          }
        }
      }
      
      console.log('✅ Snapshot data applied to dashboard:', {
        section,
        finishedGoods: section !== 'raw' ? finishedGoodsStats.value : 'not updated',
        rawMaterials: section !== 'finished' ? rawMaterialsStats.value : 'not updated'
      })
    } else {
      throw new Error(response.data.message || 'Failed to load snapshot')
    }
  } catch (error: any) {
    console.error('❌ Error loading snapshot:', error)
    snapshotError.value = error.response?.data?.message || 'Failed to load snapshot data'
    
    // Fall back to real-time data
    await loadDashboardStats()
  } finally {
    loadingSnapshot.value = false
  }
}

// Load dashboard statistics (real-time)
const loadDashboardStats = async () => {
  try {
    console.log('🔄 Loading dashboard stats from backend API...')
    
    // Use the new backend API for accurate calculations
    const response = await axios.get('/api/dashboard/inventory-stats')
    
    if (response.data.success) {
      const data = response.data.data
      
      console.log('📊 Backend calculated stats:', data)
      
      // Set the stats from backend response
      finishedGoodsStats.value = {
        totalProducts: data.totals.total_products,
        inStock: data.totals.in_stock,
        lowStock: data.totals.low_stock,
        outOfStock: data.totals.out_of_stock,
        totalValue: data.totals.total_value,
        beltTypeValues: {
          vee: data.belt_types.vee,
          cogged: data.belt_types.cogged,
          poly: data.belt_types.poly,
          tpu: data.belt_types.tpu,
          timing: data.belt_types.timing,
          special: data.belt_types.special
        }
      }
      
      console.log('✅ Dashboard stats loaded successfully:', finishedGoodsStats.value)
      
      // If poly or TPU belts show zero, check table structures for debugging
      if (data.belt_types.poly === 0 || data.belt_types.tpu === 0) {
        console.log('🔍 Poly or TPU belts showing zero, checking table structures...')
        try {
          const structureResponse = await axios.get('/api/dashboard/check-tables')
          console.log('📋 Table structures:', structureResponse.data)
        } catch (structureError) {
          console.error('❌ Failed to get table structures:', structureError)
        }
      }
    } else {
      throw new Error(response.data.message || 'Failed to load stats')
    }

    // Load raw materials stats
    try {
      const rawMaterialsResponse = await axios.get('/api/dashboard/raw-materials-stats')
      
      if (rawMaterialsResponse.data.success) {
        const rawData = rawMaterialsResponse.data.data
        
        rawMaterialsStats.value = {
          totalProducts: rawData.totals.total_products,
          inStock: rawData.totals.in_stock,
          lowStock: rawData.totals.low_stock,
          outOfStock: rawData.totals.out_of_stock,
          totalValue: rawData.totals.total_value,
          categoryValues: rawData.category_values
        }
        
        console.log('✅ Raw materials stats loaded successfully:', rawMaterialsStats.value)
      }
    } catch (rawError: any) {
      console.error('❌ Error loading raw materials stats:', rawError)
      // Keep default values on error
    }

  } catch (error: any) {
    console.error('❌ Error loading dashboard stats:', error)
    console.error('Error details:', error.response?.data || error.message)
    
    // Set error state
    finishedGoodsStats.value = { 
      totalProducts: 0, 
      inStock: 0, 
      lowStock: 0, 
      outOfStock: 0,
      totalValue: 0,
      beltTypeValues: {
        vee: 0, cogged: 0, poly: 0, tpu: 0, timing: 0, special: 0
      }
    }
    rawMaterialsStats.value = { 
      totalProducts: 0, 
      inStock: 0, 
      lowStock: 0, 
      outOfStock: 0,
      totalValue: 0,
      categoryValues: {
        'Carbon': 0,
        'Chemical': 0,
        'Cord - Cogged Belt': 0,
        'Cord - Timing Belt': 0,
        'Cord - Vee Belt': 0,
        'Fabric - Cogged Belt': 0,
        'Fabric - Timing Belt': 0,
        'Fabric - Vee Belt': 0,
        'Fabric - TPU Belt': 0,
        'Oil': 0,
        'Others': 0,
        'Resin': 0,
        'Rubber': 0,
        'TPU': 0,
        'Fibre Glass Cord': 0,
        'Steel Wire': 0,
        'Packing': 0,
        'Open': 0
      }
    }
  }
}

// Handle date selection for finished goods
const handleFinishedGoodsDateChange = async () => {
  console.log('📅 Finished goods date changed:', { start: finishedGoodsStartDate.value, end: finishedGoodsEndDate.value })
  
  if (finishedGoodsStartDate.value || finishedGoodsEndDate.value) {
    // Load snapshot data for finished goods only
    await loadSnapshotData(finishedGoodsStartDate.value || undefined, finishedGoodsEndDate.value || undefined, 'finished')
  } else {
    // Load real-time data for finished goods
    await loadDashboardStats()
  }
}

// Handle date selection for raw materials
const handleRawMaterialsDateChange = async () => {
  console.log('📅 Raw materials date changed:', { start: rawMaterialsStartDate.value, end: rawMaterialsEndDate.value })
  
  if (rawMaterialsStartDate.value || rawMaterialsEndDate.value) {
    // Load snapshot data for raw materials only
    await loadSnapshotData(rawMaterialsStartDate.value || undefined, rawMaterialsEndDate.value || undefined, 'raw')
  } else {
    // Load real-time data for raw materials
    await loadDashboardStats()
  }
}

// Refresh all table data after sending alerts
const refreshTables = () => {
  console.log('🔄 Refreshing table data after alert sent...')
  console.log('🔄 Current refreshKey before increment:', refreshKey.value)
  // Force refresh by updating a reactive key that tables can watch
  refreshKey.value++
  console.log('🔄 New refreshKey after increment:', refreshKey.value)
}

// Send stock alert email
const sendStockAlert = async () => {
  sendingAlert.value = true
  alertMessage.value = null
  
  try {
    console.log('📧 Sending stock alert report...')
    
    const response = await axios.post('/api/dashboard/send-stock-alert', {
      force: false // Send even if no alerts (for testing)
    })
    
    if (response.data.success) {
      alertMessage.value = {
        type: 'success',
        text: response.data.message
      }
      console.log('✅ Stock alert sent successfully:', response.data)
      
      // Trigger refresh of all table data to show updated alert status
      refreshTables()
    } else {
      alertMessage.value = {
        type: 'error', 
        text: response.data.message || 'Failed to send stock alert'
      }
    }
  } catch (error: any) {
    console.error('❌ Error sending stock alert:', error)
    alertMessage.value = {
      type: 'error',
      text: error.response?.data?.message || 'Failed to send stock alert'
    }
  } finally {
    sendingAlert.value = false
    
    // Clear message after 5 seconds
    setTimeout(() => {
      alertMessage.value = null
    }, 5000)
  }
}

// Send smart stock alert with die requirements
const sendingSmartAlert = ref(false)
const smartAlertMessage = ref<{type: 'success' | 'error', text: string} | null>(null)
const downloadingExcel = ref(false)

const sendSmartStockAlert = async () => {
  sendingSmartAlert.value = true
  smartAlertMessage.value = null
  
  try {
    console.log('🏭 Sending smart stock alert report...')
    
    const response = await axios.post('/api/dashboard/send-smart-stock-alert', {
      force: false // Force send for testing - will sync current data and send alerts
      // emails will be taken from .env file automatically
    })
    
    if (response.data.success) {
      smartAlertMessage.value = {
        type: 'success',
        text: `Smart alert sent! ${response.data.alerts_sent} items processed, ${response.data.recipients?.length || 0} recipients.`
      }
      console.log('✅ Smart stock alert sent successfully:', response.data)
      
      // Trigger refresh of all table data to show updated alert status
      refreshTables()
    } else {
      smartAlertMessage.value = {
        type: 'error', 
        text: response.data.message || 'Failed to send smart stock alert'
      }
    }
  } catch (error: any) {
    console.error('❌ Error sending smart stock alert:', error)
    smartAlertMessage.value = {
      type: 'error',
      text: error.response?.data?.message || 'Failed to send smart stock alert'
    }
  } finally {
    sendingSmartAlert.value = false
    
    // Clear message after 5 seconds
    setTimeout(() => {
      smartAlertMessage.value = null
    }, 5000)
  }
}

// Download Excel report without sending email
const downloadExcelReport = async () => {
  downloadingExcel.value = true
  
  try {
    console.log('📥 Downloading Excel report...')
    
    const response = await axios.get('/api/dashboard/download-excel-report', {
      responseType: 'blob' // Important for file downloads
    })
    
    // Create blob link to download
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    
    // Set filename with current date
    const date = new Date().toISOString().split('T')[0]
    link.setAttribute('download', `stock-report-${date}.xlsx`)
    
    // Trigger download
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    console.log('✅ Excel report downloaded successfully')
    
  } catch (error: any) {
    console.error('❌ Error downloading Excel report:', error)
    // Show error message in the same alert area
    alertMessage.value = {
      type: 'error',
      text: 'Failed to download Excel report'
    }
    
    // Clear message after 5 seconds
    setTimeout(() => {
      alertMessage.value = null
    }, 5000)
  } finally {
    downloadingExcel.value = false
  }
}

// Load die requirements
const dieRequirements = ref(null)
const loadingDieRequirements = ref(false)

const loadDieRequirements = async () => {
  loadingDieRequirements.value = true
  
  try {
    console.log('🔧 Loading die requirements...')
    
    const response = await axios.get('/api/dashboard/die-requirements')
    
    console.log('📦 DIE REQUIREMENTS RAW RESPONSE:', {
      status: response.status,
      success: response.data.success,
      data: response.data.data,
      full_response: response.data
    })
    
    if (response.data.success) {
      dieRequirements.value = response.data.data
      
      console.log('✅ Die requirements loaded and parsed:', {
        belt_types: Object.keys(response.data.data),
        total_sections: Object.values(response.data.data).reduce((sum: number, sections: any) => sum + sections.length, 0),
        details: response.data.data
      })
      
      // Log each belt type's requirements
      Object.entries(response.data.data).forEach(([beltType, sections]: [string, any]) => {
        console.log(`📊 ${beltType.toUpperCase()}:`, {
          sections: sections.map((s: any) => ({
            section: s.section,
            total_dies: s.total_dies,
            items_count: s.items_count
          }))
        })
      })
    } else {
      console.error('❌ Failed to load die requirements:', response.data.message)
    }
  } catch (error: any) {
    console.error('❌ Error loading die requirements:', {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status
    })
  } finally {
    loadingDieRequirements.value = false
  }
}

// Computed style for main content margin
const mainContentStyle = computed(() => {
  // Force margin with CSS for desktop
  if (typeof window !== 'undefined' && window.innerWidth >= 640) {
    return {
      marginLeft: sidebarCollapsed.value ? '4rem' : '20rem',
      transition: 'margin-left 0.3s ease'
    }
  }
  return {
    marginLeft: '0',
    transition: 'margin-left 0.3s ease'
  }
})

// Initialize datepickers
const initializeDatepickers = () => {
  setTimeout(() => {
    try {
      const finishedStartEl = document.getElementById('datepicker-finished-start')
      const finishedEndEl = document.getElementById('datepicker-finished-end')
      const rawStartEl = document.getElementById('datepicker-raw-start')
      const rawEndEl = document.getElementById('datepicker-raw-end')

      if (finishedStartEl) {
        const datepicker = new Datepicker(finishedStartEl, {
          format: 'yyyy-mm-dd',
          autohide: true,
          orientation: 'bottom'
        })
        
        finishedStartEl.addEventListener('changeDate', (e: any) => {
          finishedGoodsStartDate.value = e.target.value
          console.log('📅 Finished goods start date selected:', finishedGoodsStartDate.value)
          handleFinishedGoodsDateChange()
        })
      }

      if (finishedEndEl) {
        const datepicker = new Datepicker(finishedEndEl, {
          format: 'yyyy-mm-dd',
          autohide: true,
          orientation: 'bottom'
        })
        
        finishedEndEl.addEventListener('changeDate', (e: any) => {
          finishedGoodsEndDate.value = e.target.value
          console.log('📅 Finished goods end date selected:', finishedGoodsEndDate.value)
          handleFinishedGoodsDateChange()
        })
      }

      if (rawStartEl) {
        const datepicker = new Datepicker(rawStartEl, {
          format: 'yyyy-mm-dd',
          autohide: true,
          orientation: 'bottom'
        })
        
        rawStartEl.addEventListener('changeDate', (e: any) => {
          rawMaterialsStartDate.value = e.target.value
          console.log('📅 Raw materials start date selected:', rawMaterialsStartDate.value)
          handleRawMaterialsDateChange()
        })
      }

      if (rawEndEl) {
        const datepicker = new Datepicker(rawEndEl, {
          format: 'yyyy-mm-dd',
          autohide: true,
          orientation: 'bottom'
        })
        
        rawEndEl.addEventListener('changeDate', (e: any) => {
          rawMaterialsEndDate.value = e.target.value
          console.log('📅 Raw materials end date selected:', rawMaterialsEndDate.value)
          handleRawMaterialsDateChange()
        })
      }
      
      console.log('✅ Date pickers initialized')
    } catch (error) {
      console.error('❌ Error initializing datepickers:', error)
    }
  }, 500)
}

// Navigation mapping for belt sections
const navigationMapping: Record<string, { title: string; categories: string[] }> = {
  // Individual Vee Belt Types - Classical Section
  'vee-belts-a': { title: 'A Section Inventory', categories: ['A Section'] },
  'vee-belts-b': { title: 'B Section Inventory', categories: ['B Section'] },
  'vee-belts-c': { title: 'C Section Inventory', categories: ['C Section'] },
  'vee-belts-d': { title: 'D Section Inventory', categories: ['D Section'] },
  'vee-belts-e': { title: 'E Section Inventory', categories: ['E Section'] },
  // Individual Vee Belt Types - Wedge Section
  'vee-belts-spa': { title: 'SPA Section Inventory', categories: ['SPA Section'] },
  'vee-belts-spb': { title: 'SPB Section Inventory', categories: ['SPB Section'] },
  'vee-belts-spc': { title: 'SPC Section Inventory', categories: ['SPC Section'] },
  'vee-belts-spz': { title: 'SPZ Section Inventory', categories: ['SPZ Section'] },
  // Individual Vee Belt Types - Narrow Section
  'vee-belts-3v': { title: '3V Section Inventory', categories: ['3V Section'] },
  'vee-belts-5v': { title: '5V Section Inventory', categories: ['5V Section'] },
  'vee-belts-8v': { title: '8V Section Inventory', categories: ['8V Section'] },
  // Individual Cogged Belt Types - Classical Section
  'cogged-belts-ax': { title: 'AX Section Inventory', categories: ['AX Section'] },
  'cogged-belts-bx': { title: 'BX Section Inventory', categories: ['BX Section'] },
  'cogged-belts-cx': { title: 'CX Section Inventory', categories: ['CX Section'] },
  // Individual Cogged Belt Types - Wedge Section
  'cogged-belts-xpa': { title: 'XPA Section Inventory', categories: ['XPA Section'] },
  'cogged-belts-xpb': { title: 'XPB Section Inventory', categories: ['XPB Section'] },
  'cogged-belts-xpc': { title: 'XPC Section Inventory', categories: ['XPC Section'] },
  'cogged-belts-xpz': { title: 'XPZ Section Inventory', categories: ['XPZ Section'] },
  // Individual Cogged Belt Types - Narrow Section
  'cogged-belts-3vx': { title: '3VX Section Inventory', categories: ['3VX Section'] },
  'cogged-belts-5vx-page': { title: '5VX Section Inventory', categories: ['5VX Section'] },
  'cogged-belts-8vx': { title: '8VX Section Inventory', categories: ['8VX Section'] },
  // Individual Poly Belt Types - V-Belts
  'poly-belts-pj': { title: 'PJ Section Inventory', categories: ['PJ Section'] },
  'poly-belts-pk': { title: 'PK Section Inventory', categories: ['PK Section'] },
  'poly-belts-pl': { title: 'PL Section Inventory', categories: ['PL Section'] },
  'poly-belts-pm': { title: 'PM Section Inventory', categories: ['PM Section'] },
  'poly-belts-ph': { title: 'PH Section Inventory', categories: ['PH Section'] },
  // Individual Poly Belt Types - Double Sided
  'poly-belts-dpl': { title: 'DPL Section Inventory', categories: ['DPL Section'] },
  'poly-belts-dpk': { title: 'DPK Section Inventory', categories: ['DPK Section'] },
  
  // Individual TPU Belt Types
  'tpu-belts-5m': { title: 'TPU 5M Belt Inventory', categories: ['5M Section'] },
  'tpu-belts-8m': { title: 'TPU 8M Belt Inventory', categories: ['8M Section'] },
  'tpu-belts-8m-rpp': { title: 'TPU 8M RPP Belt Inventory', categories: ['8M RPP Section'] },
  'tpu-belts-s8m': { title: 'TPU S8M Belt Inventory', categories: ['S8M Section'] },
  'tpu-belts-14m': { title: 'TPU 14M Belt Inventory', categories: ['14M Section'] },
  'tpu-belts-xl': { title: 'TPU XL Belt Inventory', categories: ['XL Section'] },
  'tpu-belts-l': { title: 'TPU L Belt Inventory', categories: ['L Section'] },
  'tpu-belts-h': { title: 'TPU H Belt Inventory', categories: ['H Section'] },
  'tpu-belts-at5': { title: 'TPU AT5 Belt Inventory', categories: ['AT5 Section'] },
  'tpu-belts-at10': { title: 'TPU AT10 Belt Inventory', categories: ['AT10 Section'] },
  'tpu-belts-t10': { title: 'TPU T10 Belt Inventory', categories: ['T10 Section'] },
  'tpu-belts-at20': { title: 'TPU AT20 Belt Inventory', categories: ['AT20 Section'] },
  // Special Belts and Coated Belts - Main Subsections
  'vee-belts-special': { title: 'Vee Belts Special Inventory (Conical C, Harvester)', categories: ['Conical C Section', 'Harvester Section'] },
  'banded-cogged-belts': { title: 'Banded Cogged Belts Inventory (RAX, RBX, R3VX, R5VX)', categories: ['RAX Section', 'RBX Section', 'R3VX Section', 'R5VX Section'] },
  'hybrid-belts': { title: 'Hybrid Belts Inventory (8M PK, 8M PL)', categories: ['8M PK Section', '8M PL Section'] },
  'coating-belts': { title: 'Coating Belts Inventory (Poly, Flat, Timing)', categories: ['Poly Coating Section', 'Flat Coating Section', 'Timing Coating Section'] },
  // Raw Material sections (same mapping)
  'raw-vee-belts-classical': { title: 'Raw Material - Classical Section (A, B, C, D, E)', categories: ['A Section', 'B Section', 'C Section', 'D Section', 'E Section'] },
  'raw-vee-belts-wedge': { title: 'Raw Material - Wedge Section (SPA, SPB, SPC, SPZ)', categories: ['SPA Section', 'SPB Section', 'SPC Section', 'SPZ Section'] },
  'raw-vee-belts-narrow': { title: 'Raw Material - Narrow Section (3V, 5V, 8V)', categories: ['3V Section', '5V Section', '8V Section'] },
  'raw-cogged-belts-classical': { title: 'Raw Material - Cogged Classical (AX, BX, CX)', categories: ['AX Section', 'BX Section', 'CX Section'] },
  'raw-cogged-belts-wedge': { title: 'Raw Material - Cogged Wedge (XPA, XPB, XPC, XPZ)', categories: ['XPA Section', 'XPB Section', 'XPC Section', 'XPZ Section'] },
  'raw-cogged-belts-narrow': { title: 'Raw Material - Cogged Narrow (3VX, 5VX, 8VX)', categories: ['3VX Section', '5VX Section', '8VX Section'] },
  'raw-poly-v-belts': { title: 'Raw Material - Poly V-Belts (PJ, PK, PL, PM, PH)', categories: ['PJ Section', 'PK Section', 'PL Section', 'PM Section', 'PH Section'] },
  'raw-poly-v-belts-double-sided': { title: 'Raw Material - Poly V-Belts Double Side (DPL, DPK)', categories: ['DPL Section', 'DPK Section'] },
  'raw-timing-belts-classic': { title: 'Raw Material - Classical Timing Belts (XL, L, H, XH, T5, T10)', categories: ['XL Section', 'L Section', 'H Section', 'XH Section', 'T5 Section', 'T10 Section'] },
  'raw-timing-belts-htd': { title: 'Raw Material - HTD Timing Belts (5M, 8M, 14M)', categories: ['5M Section', '8M Section', '14M Section'] },
  'raw-timing-belts-double-sided': { title: 'Raw Material - Double-Side Timing Belts (DL, DH, D5M, D8M)', categories: ['DL Section', 'DH Section', 'D5M Section', 'D8M Section'] },
  'raw-timing-belts-neoprene': { title: 'Raw Material - Neoprene Timing Belts', categories: ['NEOPRENE-XL Section', 'NEOPRENE-L Section', 'NEOPRENE-H Section', 'NEOPRENE-XH Section', 'NEOPRENE-T5 Section', 'NEOPRENE-T10 Section'] },
  // Raw Material - TPU Belt Open
  'raw-tpu-belt-open': { title: 'Raw Material - TPU Belt Open (5M, 8M, 8M RPP, S8M, 14M, XL, L, H, AT5, AT10, T10, AT20)', categories: ['5M Section', '8M Section', '8M RPP Section', 'S8M Section', '14M Section', 'XL Section', 'L Section', 'H Section', 'AT5 Section', 'AT10 Section', 'T10 Section', 'AT20 Section'] },
  // Raw Material - Special Belts and Coated Belts - Main Subsections
  'raw-vee-belts-special': { title: 'Raw Material - Vee Belts Special (Conical C, Harvester)', categories: ['Conical C Section', 'Harvester Section'] },
  'raw-banded-cogged-belts': { title: 'Raw Material - Banded Cogged Belts (RAX, RBX, R3VX, R5VX)', categories: ['RAX Section', 'RBX Section', 'R3VX Section', 'R5VX Section'] },
  'raw-hybrid-belts': { title: 'Raw Material - Hybrid Belts (8M PK, 8M PL)', categories: ['8M PK Section', '8M PL Section'] },
  'raw-coating-belts': { title: 'Raw Material - Coating Belts (Poly, Flat, Timing)', categories: ['Poly Coating Section', 'Flat Coating Section', 'Timing Coating Section'] },
  // Raw Material Categories
  'raw-material-carbons': { title: 'Raw Material - Carbon', categories: ['Carbon'] },
  'raw-material-chemical': { title: 'Raw Material - Chemical', categories: ['Chemical'] },
  'raw-material-cord': { title: 'Raw Material - Soft/Stiff Cord', categories: ['Soft/Stiff Cord'] },
  'raw-material-fabric': { title: 'Raw Material - Fabric', categories: ['Fabric'] },
  'raw-material-oil': { title: 'Raw Material - Oil', categories: ['Oil'] },
  'raw-material-others': { title: 'Raw Material - Others', categories: ['Others'] },
  'raw-material-resin': { title: 'Raw Material - Resin', categories: ['Resin'] },
  'raw-material-tpu': { title: 'Raw Material - TPU', categories: ['TPU'] },
  'raw-material-fibre-glass-cord': { title: 'Raw Material - Fibre Glass Cord', categories: ['Fibre Glass Cord'] },
  'raw-material-steel-wire': { title: 'Raw Material - Steel Wire', categories: ['Steel Wire'] },
  'raw-material-packing': { title: 'Raw Material - Packing Material', categories: ['Packing Material'] },
}
const customViewMapping = computed(() => {
  return {
    // Vee Belts Search - All sections
    'vee-belts-search': { 
      component: VeeBeltTable, 
      props: { 
        title: 'Vee Belts Search Results', 
        section: globalSectionQuery.value,
        globalSearch: globalSizeQuery.value,
        key: `vee-search-${globalSectionQuery.value}-${globalSizeQuery.value}` // Force re-render on change
      } 
    },
    // Vee Belts - Using new backend-connected VeeBeltTable
    'vee-belts-a-page': { component: VeeBeltTable, props: { section: 'A', title: 'A Section Inventory' } },
    
    // Cogged Belts - Using new backend-connected CoggedBeltTable
    'cogged-belts-search': { 
      component: CoggedBeltTable, 
      props: { 
        title: 'Cogged Belts Search Results', 
        section: globalSectionQuery.value,
        globalSearch: globalSizeQuery.value,
        refreshKey: refreshKey.value,
        key: `cogged-search-${globalSectionQuery.value}-${globalSizeQuery.value}-${refreshKey.value}` // Force re-render on change
      } 
    },
  'cogged-belts-ax': { component: CoggedBeltTable, props: { section: 'AX', title: 'AX Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-bx': { component: CoggedBeltTable, props: { section: 'BX', title: 'BX Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-cx': { component: CoggedBeltTable, props: { section: 'CX', title: 'CX Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-xpa': { component: CoggedBeltTable, props: { section: 'XPA', title: 'XPA Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-xpb': { component: CoggedBeltTable, props: { section: 'XPB', title: 'XPB Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-xpc': { component: CoggedBeltTable, props: { section: 'XPC', title: 'XPC Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-xpz': { component: CoggedBeltTable, props: { section: 'XPZ', title: 'XPZ Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-3vx': { component: CoggedBeltTable, props: { section: '3VX', title: '3VX Section Inventory', refreshKey: refreshKey.value } },
  'cogged-belts-5vx': { component: CoggedBeltTable, props: { section: '5VX', title: '5VX Section Inventory', refreshKey: refreshKey.value } },
  
    // Poly Belts - Using new backend-connected PolyBeltTable
    'poly-belts-search': { 
      component: PolyBeltTable, 
      props: { 
        title: 'Poly Belts Search Results', 
        section: globalSectionQuery.value,
        globalSearch: globalSizeQuery.value,
        refreshKey: refreshKey.value,
        key: `poly-search-${globalSectionQuery.value}-${globalSizeQuery.value}-${refreshKey.value}` // Force re-render on change
      } 
    },
    
    // TPU Belts - Using new backend-connected TpuBeltTable
    'tpu-belts-search': { 
      component: TpuBeltTable, 
      props: { 
        title: 'TPU Belts Search Results', 
        section: globalSectionQuery.value,
        globalSearch: globalSizeQuery.value,
        refreshKey: refreshKey.value,
        key: `tpu-search-${globalSectionQuery.value}-${globalSizeQuery.value}-${refreshKey.value}` // Force re-render on change
      } 
    },
  'poly-belts-pj': { component: PolyBeltTable, props: { section: 'PJ', title: 'PJ Section Inventory', refreshKey: refreshKey.value } },
  'poly-belts-pk': { component: PolyBeltTable, props: { section: 'PK', title: 'PK Section Inventory', refreshKey: refreshKey.value } },
  'poly-belts-pl': { component: PolyBeltTable, props: { section: 'PL', title: 'PL Section Inventory', refreshKey: refreshKey.value } },
  'poly-belts-pm': { component: PolyBeltTable, props: { section: 'PM', title: 'PM Section Inventory', refreshKey: refreshKey.value } },
  'poly-belts-ph': { component: PolyBeltTable, props: { section: 'PH', title: 'PH Section Inventory', refreshKey: refreshKey.value } },
  'poly-belts-dpl': { component: PolyBeltTable, props: { section: 'DPL', title: 'DPL Section Inventory', refreshKey: refreshKey.value } },
  'poly-belts-dpk': { component: PolyBeltTable, props: { section: 'DPK', title: 'DPK Section Inventory', refreshKey: refreshKey.value } },
  
  'vee-belts-b-page': { component: VeeBeltTable, props: { section: 'B', title: 'B Section Inventory' } },
  'vee-belts-c-page': { component: VeeBeltTable, props: { section: 'C', title: 'C Section Inventory' } },
  'vee-belts-d-page': { component: VeeBeltTable, props: { section: 'D', title: 'D Section Inventory' } },
  'vee-belts-e-page': { component: VeeBeltTable, props: { section: 'E', title: 'E Section Inventory' } },
  'vee-belts-spa-page': { component: VeeBeltTable, props: { section: 'SPA', title: 'SPA Section Inventory' } },
  'vee-belts-spb-page': { component: VeeBeltTable, props: { section: 'SPB', title: 'SPB Section Inventory' } },
  'vee-belts-spc-page': { component: VeeBeltTable, props: { section: 'SPC', title: 'SPC Section Inventory' } },
  'vee-belts-spz-page': { component: VeeBeltTable, props: { section: 'SPZ', title: 'SPZ Section Inventory' } },
  'vee-belts-3v-page': { component: VeeBeltTable, props: { section: '3V', title: '3V Section Inventory' } },
  'vee-belts-5v-page': { component: VeeBeltTable, props: { section: '5V', title: '5V Section Inventory' } },
  'vee-belts-8v-page': { component: VeeBeltTable, props: { section: '8V', title: '8V Section Inventory' } },

  'tpu-belts-t8m-page': { component: TpuBeltTable, props: { section: '8M', title: '8M Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-t5m-page': { component: TpuBeltTable, props: { section: '5M', title: '5M Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-t8m-RPP-page': { component: TpuBeltTable, props: { section: '8M RPP', title: '8M RPP Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-ts8m-page': { component: TpuBeltTable, props: { section: 'S8M', title: 'S8M Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-t14m-page': { component: TpuBeltTable, props: { section: '14M', title: '14M Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-txl-page': { component: TpuBeltTable, props: { section: 'XL', title: 'XL Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-tlm-page': { component: TpuBeltTable, props: { section: 'L', title: 'L Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-thm-page': { component: TpuBeltTable, props: { section: 'H', title: 'H Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-at5m-page': { component: TpuBeltTable, props: { section: 'AT5', title: 'AT5 Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-at10m-page': { component: TpuBeltTable, props: { section: 'AT10', title: 'AT10 Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-at20m-page': { component: TpuBeltTable, props: { section: 'AT20', title: 'AT20 Section Inventory', refreshKey: refreshKey.value } },
  'tpu-belts-t10M-page': { component: TpuBeltTable, props: { section: 'T10', title: 'T10 Section Inventory', refreshKey: refreshKey.value } },
  
  // Timing Belts - Using new backend-connected TimingBeltTable
  'timing-belts-search': { 
    component: TimingBeltTable, 
    props: { 
      title: 'Timing Belts Search Results', 
      section: globalSectionQuery.value,
      globalSearch: globalSizeQuery.value,
      refreshKey: refreshKey.value,
      key: `timing-search-${globalSectionQuery.value}-${globalSizeQuery.value}-${refreshKey.value}` // Force re-render on change
    } 
  },
  'timing-belts-xl': { component: TimingBeltTable, props: { section: 'XL', title: 'XL Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-l': { component: TimingBeltTable, props: { section: 'L', title: 'L Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-h': { component: TimingBeltTable, props: { section: 'H', title: 'H Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-xh': { component: TimingBeltTable, props: { section: 'XH', title: 'XH Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-t5': { component: TimingBeltTable, props: { section: 'T5', title: 'T5 Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-t10': { component: TimingBeltTable, props: { section: 'T10', title: 'T10 Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-3m': { component: TimingBeltTable, props: { section: '3M', title: '3M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-5m': { component: TimingBeltTable, props: { section: '5M', title: '5M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-8m': { component: TimingBeltTable, props: { section: '8M', title: '8M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-14m': { component: TimingBeltTable, props: { section: '14M', title: '14M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-dl': { component: TimingBeltTable, props: { section: 'DL', title: 'DL Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-dh': { component: TimingBeltTable, props: { section: 'DH', title: 'DH Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-d5m': { component: TimingBeltTable, props: { section: 'D5M', title: 'D5M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-d8m': { component: TimingBeltTable, props: { section: 'D8M', title: 'D8M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-xl': { component: TimingBeltTable, props: { section: 'NEOPRENE-XL', title: 'Neoprene XL Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-l': { component: TimingBeltTable, props: { section: 'NEOPRENE-L', title: 'Neoprene L Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-h': { component: TimingBeltTable, props: { section: 'NEOPRENE-H', title: 'Neoprene H Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-xh': { component: TimingBeltTable, props: { section: 'NEOPRENE-XH', title: 'Neoprene XH Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-t5': { component: TimingBeltTable, props: { section: 'NEOPRENE-T5', title: 'Neoprene T5 Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-t10': { component: TimingBeltTable, props: { section: 'NEOPRENE-T10', title: 'Neoprene T10 Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-3m': { component: TimingBeltTable, props: { section: 'NEOPRENE-3M', title: 'Neoprene 3M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-5m': { component: TimingBeltTable, props: { section: 'NEOPRENE-5M', title: 'Neoprene 5M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-8m': { component: TimingBeltTable, props: { section: 'NEOPRENE-8M', title: 'Neoprene 8M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-14m': { component: TimingBeltTable, props: { section: 'NEOPRENE-14M', title: 'Neoprene 14M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-dl': { component: TimingBeltTable, props: { section: 'NEOPRENE-DL', title: 'Neoprene DL Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-dh': { component: TimingBeltTable, props: { section: 'NEOPRENE-DH', title: 'Neoprene DH Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-d5m': { component: TimingBeltTable, props: { section: 'NEOPRENE-D5M', title: 'Neoprene D5M Section Inventory', refreshKey: refreshKey.value } },
  'timing-belts-neoprene-d8m': { component: TimingBeltTable, props: { section: 'NEOPRENE-D8M', title: 'Neoprene D8M Section Inventory', refreshKey: refreshKey.value } },
  
  // Special Belts - Using new backend-connected SpecialBeltTable
  'special-belts-search': { 
    component: SpecialBeltTable, 
    props: { 
      title: 'Special Belts Search Results', 
      section: globalSectionQuery.value,
      globalSearch: globalSizeQuery.value,
      key: `special-search-${globalSectionQuery.value}-${globalSizeQuery.value}` // Force re-render on change
    } 
  },
  'special-belts-conical-c': { component: SpecialBeltTable, props: { section: 'Conical C', title: 'Conical C Section Inventory' } },
  'special-belts-harvester': { component: SpecialBeltTable, props: { section: 'Harvester', title: 'Harvester Section Inventory' } },
  'special-belts-rax': { component: SpecialBeltTable, props: { section: 'RAX', title: 'RAX Section Inventory' } },
  'special-belts-rbx': { component: SpecialBeltTable, props: { section: 'RBX', title: 'RBX Section Inventory' } },
  'special-belts-r3vx': { component: SpecialBeltTable, props: { section: 'R3VX', title: 'R3VX Section Inventory' } },
  'special-belts-r5vx': { component: SpecialBeltTable, props: { section: 'R5VX', title: 'R5VX Section Inventory' } },
  'special-belts-8m-pk': { component: SpecialBeltTable, props: { section: '8M PK', title: '8M PK Section Inventory' } },
  'special-belts-8m-pl': { component: SpecialBeltTable, props: { section: '8M PL', title: '8M PL Section Inventory' } },
  
  // Settings page (admin only)
  'settings': { component: SettingsPage, props: {} },
  
  // User management page (admin only)
  'user-management': { component: UserManagement, props: {} },
  
  // Raw Material Categories
  'raw-material-carbon': { component: RawCarbonTable, props: { section: 'Carbon', title: 'Raw Material - Carbon' } },
  'raw-material-chemical': { component: RawCarbonTable, props: { section: 'Chemical', title: 'Raw Material - Chemical' } },
  
  // Cord subsections
  'raw-material-cord-cogged': { component: RawCarbonTable, props: { section: 'Cord - Cogged Belt', title: 'Raw Material - Cogged Belt Cord' } },
  'raw-material-cord-timing': { component: RawCarbonTable, props: { section: 'Cord - Timing Belt', title: 'Raw Material - Timing Belt Cord' } },
  'raw-material-cord-vee': { component: RawCarbonTable, props: { section: 'Cord - Vee Belt', title: 'Raw Material - Vee Belt Cord' } },
  
  // Fabric subsections
  'raw-material-fabric-cogged': { component: RawCarbonTable, props: { section: 'Fabric - Cogged Belt', title: 'Raw Material - Cogged Belt Fabric' } },
  'raw-material-fabric-timing': { component: RawCarbonTable, props: { section: 'Fabric - Timing Belt', title: 'Raw Material - Timing Belt Fabric' } },
  'raw-material-fabric-vee': { component: RawCarbonTable, props: { section: 'Fabric - Vee Belt', title: 'Raw Material - Vee Belt Fabric' } },
  'raw-material-fabric-tpu': { component: RawCarbonTable, props: { section: 'Fabric - TPU Belt', title: 'Raw Material - TPU Belt Fabric' } },
  
  'raw-material-oil': { component: RawCarbonTable, props: { section: 'Oil', title: 'Raw Material - Oil' } },
  'raw-material-others': { component: RawCarbonTable, props: { section: 'Others', title: 'Raw Material - Others' } },
  'raw-material-resin': { component: RawCarbonTable, props: { section: 'Resin', title: 'Raw Material - Resin' } },
  'raw-material-rubber': { component: RawCarbonTable, props: { section: 'Rubber', title: 'Raw Material - Rubber' } },
  'raw-material-tpu': { component: RawCarbonTable, props: { section: 'TPU', title: 'Raw Material - TPU' } },
  'raw-material-fibre-glass-cord': { component: RawCarbonTable, props: { section: 'Fibre Glass Cord', title: 'Raw Material - Fibre Glass Cord' } },
  'raw-material-steel-wire': { component: RawCarbonTable, props: { section: 'Steel Wire', title: 'Raw Material - Steel Wire' } },
  'raw-material-packing': { component: RawCarbonTable, props: { section: 'Packing', title: 'Raw Material - Packing Material' } },
  'raw-material-open': { component: RawCarbonTable, props: { section: 'Open', title: 'Raw Material - Open' } },
  
  // Raw Material Search - searches across ALL raw materials
  'raw-material-search': { component: RawCarbonTable, props: { title: 'Raw Material Search Results' } },
  }
});
const handleNavigation = (view: string) => {
  // Check admin access for restricted views
  if ((view === 'settings' || view === 'user-management') && !isAdmin.value) {
    console.warn('Access denied: Admin privileges required')
    return
  }
  
  currentView.value = view
  
  // Clear global search when navigating to specific sections (except search views)
  const searchViews = ['vee-belts-search', 'cogged-belts-search', 'poly-belts-search', 'tpu-belts-search', 'timing-belts-search', 'special-belts-search']
  if (view !== 'inventory' && !searchViews.includes(view)) {
    globalSectionQuery.value = ''
    globalSizeQuery.value = ''
  }
  
  // Initialize datepickers when navigating to dashboard
  if (view === 'dashboard') {
    initializeDatepickers()
  }
}

const handleSearch = (searchData: { type: string; sectionQuery?: string; sizeQuery?: string }) => {
  // Set global search parameters
  globalSectionQuery.value = searchData.sectionQuery || ''
  globalSizeQuery.value = searchData.sizeQuery || ''
  
  // Determine which search view to show based on section
  const section = globalSectionQuery.value.toUpperCase()
  
  // Check if currently on a raw material view
  const isOnRawMaterialView = currentView.value.startsWith('raw-material-')
  
  // Check if it's a vee belt section
  const veeSections = ['A', 'B', 'C', 'D', 'E', 'SPA', 'SPB', 'SPC', 'SPZ', '3V', '5V', '8V']
  // Check if it's a cogged belt section
  const coggedSections = ['AX', 'BX', 'CX', 'XPA', 'XPB', 'XPC', 'XPZ', '3VX', '5VX', '8VX']
  // Check if it's a poly belt section
  const polySections = ['PJ', 'PK', 'PL', 'PM', 'PH', 'DPL', 'DPK']
  // Check if it's a timing belt section (check timing first to avoid conflicts with TPU)
  const timingSections = ['XL', 'L', 'H', 'XH', 'T5', 'T10', '3M', '5M', '8M', '14M', 'DL', 'DH', 'D5M', 'D8M', 'NEO-XL', 'NEOPRENE-L', 'NEOPRENE-H', 'NEOPRENE-XH', 'NEOPRENE-T5', 'NEOPRENE-T10', 'NEOPRENE-3M', 'NEOPRENE-5M', 'NEOPRENE-8M', 'NEOPRENE-14M', 'NEOPRENE-DL', 'NEOPRENE-DH', 'NEOPRENE-D5M', 'NEOPRENE-D8M']
  // Check if it's a TPU belt section (more specific sections to avoid conflicts)
  const tpuSections = ['8M RPP', 'S8M', 'AT5', 'AT10', 'T10', 'AT20']
  
  // Check if search is for a finished goods section
  const isFinishedGoodsSection = veeSections.includes(section) || 
                                  coggedSections.includes(section) || 
                                  polySections.includes(section) || 
                                  timingSections.includes(section) || 
                                  tpuSections.includes(section)
  
  // Simple logic: section determines the view, size is just a filter
  if (section) {
    // If on raw material view but searching for finished goods, navigate to finished goods
    if (isOnRawMaterialView && isFinishedGoodsSection) {
      console.log('🔍 Switching from raw material to finished goods view for:', section)
      // Continue with normal finished goods navigation logic below
    } 
    // If on raw material view and searching for raw materials, stay and filter
    else if (isOnRawMaterialView && !isFinishedGoodsSection) {
      console.log('🔍 Staying on raw material view, filtering with:', globalSectionQuery.value)
      // Don't change view, just let the globalSearch prop filter the data
      return
    }
    
    if (globalSizeQuery.value) {
      // Section + Size = Use search view for filtering
      if (veeSections.includes(section)) {
        currentView.value = 'vee-belts-search'
      } else if (coggedSections.includes(section)) {
        currentView.value = 'cogged-belts-search'
      } else if (polySections.includes(section)) {
        currentView.value = 'poly-belts-search'
      } else if (timingSections.includes(section)) {
        currentView.value = 'timing-belts-search'
      } else if (tpuSections.includes(section)) {
        currentView.value = 'tpu-belts-search'
      } else {
        // Not a finished goods section, assume raw material search
        console.log('🔍 Raw material search with section + size:', section, globalSizeQuery.value)
        currentView.value = 'raw-material-search'
      }
    } else {
      // Section only = Direct to section page
      if (veeSections.includes(section)) {
        const sectionPageMap: Record<string, string> = {
          'A': 'vee-belts-a-page',
          'B': 'vee-belts-b-page', 
          'C': 'vee-belts-c-page',
          'D': 'vee-belts-d-page',
          'E': 'vee-belts-e-page',
          'SPA': 'vee-belts-spa-page',
          'SPB': 'vee-belts-spb-page',
          'SPC': 'vee-belts-spc-page',
          'SPZ': 'vee-belts-spz-page',
          '3V': 'vee-belts-3v-page',
          '5V': 'vee-belts-5v-page',
          '8V': 'vee-belts-8v-page'
        }
        currentView.value = sectionPageMap[section] || 'vee-belts-search'
      } else if (coggedSections.includes(section)) {
        const sectionPageMap: Record<string, string> = {
          'AX': 'cogged-belts-ax',
          'BX': 'cogged-belts-bx',
          'CX': 'cogged-belts-cx',
          'XPA': 'cogged-belts-xpa',
          'XPB': 'cogged-belts-xpb',
          'XPC': 'cogged-belts-xpc',
          'XPZ': 'cogged-belts-xpz',
          '3VX': 'cogged-belts-3vx',
          '5VX': 'cogged-belts-5vx'
        }
        currentView.value = sectionPageMap[section] || 'cogged-belts-search'
      } else if (polySections.includes(section)) {
        const sectionPageMap: Record<string, string> = {
          'PJ': 'poly-belts-pj',
          'PK': 'poly-belts-pk',
          'PL': 'poly-belts-pl',
          'PM': 'poly-belts-pm',
          'PH': 'poly-belts-ph',
          'DPL': 'poly-belts-dpl',
          'DPK': 'poly-belts-dpk'
        }
        currentView.value = sectionPageMap[section] || 'poly-belts-search'
      } else if (timingSections.includes(section)) {
        const sectionPageMap: Record<string, string> = {
          'XL': 'timing-belts-xl',
          'L': 'timing-belts-l',
          'H': 'timing-belts-h',
          'XH': 'timing-belts-xh',
          'T5': 'timing-belts-t5',
          'T10': 'timing-belts-t10',
          '3M': 'timing-belts-3m',
          '5M': 'timing-belts-5m',
          '8M': 'timing-belts-8m',
          '14M': 'timing-belts-14m',
          'DL': 'timing-belts-dl',
          'DH': 'timing-belts-dh',
          'D5M': 'timing-belts-d5m',
          'D8M': 'timing-belts-d8m',
          'NEOPRENE-XL': 'timing-belts-neoprene-xl',
          'NEOPRENE-L': 'timing-belts-neoprene-l',
          'NEOPRENE-H': 'timing-belts-neoprene-h',
          'NEOPRENE-XH': 'timing-belts-neoprene-xh',
          'NEOPRENE-T5': 'timing-belts-neoprene-t5',
          'NEOPRENE-T10': 'timing-belts-neoprene-t10',
          'NEOPRENE-3M': 'timing-belts-neoprene-3m',
          'NEOPRENE-5M': 'timing-belts-neoprene-5m',
          'NEOPRENE-8M': 'timing-belts-neoprene-8m',
          'NEOPRENE-14M': 'timing-belts-neoprene-14m',
          'NEOPRENE-DL': 'timing-belts-neoprene-dl',
          'NEOPRENE-DH': 'timing-belts-neoprene-dh',
          'NEOPRENE-D5M': 'timing-belts-neoprene-d5m',
          'NEOPRENE-D8M': 'timing-belts-neoprene-d8m'
        }
        currentView.value = sectionPageMap[section] || 'timing-belts-search'
      } else if (tpuSections.includes(section)) {
        const sectionPageMap: Record<string, string> = {
          '8M RPP': 'tpu-belts-t8m-RPP-page',
          'S8M': 'tpu-belts-ts8m-page',
          'AT5': 'tpu-belts-at5m-page',
          'AT10': 'tpu-belts-at10m-page',
          'T10': 'tpu-belts-t10M-page',
          'AT20': 'tpu-belts-at20m-page'
        }
        currentView.value = sectionPageMap[section] || 'tpu-belts-search'
      } else {
        // Not a finished goods section, assume raw material search
        console.log('🔍 Raw material search (section only):', section)
        currentView.value = 'raw-material-search'
      }
    }
  }
  
  console.log('Universal search activated:', {
    type: searchData.type,
    section: globalSectionQuery.value,
    size: globalSizeQuery.value,
    view: currentView.value
  })
}

const clearGlobalSearch = () => {
  globalSectionQuery.value = ''
  globalSizeQuery.value = ''
  console.log('Global search cleared')
}

onMounted(() => {
  if (currentView.value === 'dashboard') {
    initializeDatepickers()
  }
})
</script>

<template>
  <div>
    <!-- Show loading while checking authentication -->
    <div v-if="authLoading" class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400">Loading...</p>
      </div>
    </div>
    
    <!-- Show login page if not authenticated -->
    <LoginPage v-else-if="!isAuthenticated" @login-success="handleLoginSuccess" />
    
    <!-- Show main app if authenticated -->
    <div v-else>
      <Sidebar 
        @navigate="handleNavigation" 
        @sidebar-toggle="handleSidebarToggle" 
        @search="handleSearch"
        @logout="handleLogout"
      />
      
      <div v-if="currentView === 'inventory'">
      <VeeBeltTable 
        section="A"
        title="A Section Inventory"
        :sidebar-collapsed="sidebarCollapsed"
      />
    </div>
    <div v-else-if="currentView === 'spa-groups' || currentView === 'spa-l-groups'">
      <FlowbiteTable
        title="SPA Section Inventory"
        initialCategory="SPA Section"
        :sidebar-collapsed="sidebarCollapsed"
      />
    </div>
    <!-- Belt Section Navigation -->
    <!-- ✅ Custom full pages FIRST -->
<div
  v-else-if="customViewMapping && customViewMapping[currentView]"
>
  <component 
    :is="customViewMapping[currentView].component || customViewMapping[currentView]" 
    v-bind="customViewMapping[currentView].props || {}"
    :sidebar-collapsed="sidebarCollapsed"
    :global-search="globalSectionQuery"
    :key="`${customViewMapping[currentView].props?.key || currentView}-${refreshKey}`"
  />
</div>

<!-- ✅ Normal inventory table pages -->
<div v-else-if="navigationMapping && navigationMapping[currentView]">
  <FlowbiteTable
    :title="navigationMapping[currentView].title"
    :initialCategories="navigationMapping[currentView].categories"
  />
</div>

    <div v-else-if="currentView === 'create-product'" :style="mainContentStyle">
      <CreateProduct />
    </div>
    <div v-else-if="currentView === 'dashboard'" :style="mainContentStyle">
      <div class="p-3 sm:p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
          <p class="text-gray-600 dark:text-gray-400">Welcome to your Microbelts IMA dashboard</p>
          
          <!-- Snapshot Error Message -->
          <div v-if="snapshotError" class="mt-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <span class="font-medium">Error:</span>
              <span class="ml-1">{{ snapshotError }}</span>
            </div>
          </div>
          
          <!-- Date Range Info -->
          <div v-if="finishedGoodsStartDate || finishedGoodsEndDate || rawMaterialsStartDate || rawMaterialsEndDate" class="mt-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200">
            <div class="flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="font-medium">Viewing snapshot data:</span>
              <span class="ml-1">
                <span v-if="finishedGoodsStartDate || finishedGoodsEndDate">
                  Finished Goods: {{ finishedGoodsStartDate || 'Latest' }}<span v-if="finishedGoodsEndDate"> to {{ finishedGoodsEndDate }}</span>
                </span>
                <span v-if="(finishedGoodsStartDate || finishedGoodsEndDate) && (rawMaterialsStartDate || rawMaterialsEndDate)"> | </span>
                <span v-if="rawMaterialsStartDate || rawMaterialsEndDate">
                  Raw Materials: {{ rawMaterialsStartDate || 'Latest' }}<span v-if="rawMaterialsEndDate"> to {{ rawMaterialsEndDate }}</span>
                </span>
              </span>
            </div>
          </div>
        </div>
        
        <!-- Finished Goods Stats Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-4 flex-1 min-h-0">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg">Finished Goods</h2>
            <div class="flex items-center gap-3">
              <div class="flex items-center rounded-lg p-2">
                <div class="relative max-w-sm">
                  <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                  </div>
                  <input id="datepicker-finished-start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Start date">
                </div>
                <span class="mx-2 text-gray-500 dark:text-gray-400">to</span>
                <div class="relative max-w-sm">
                  <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                  </div>
                  <input id="datepicker-finished-end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="End date">
                </div>
              </div>
              <button 
                v-if="finishedGoodsStartDate || finishedGoodsEndDate"
                @click="finishedGoodsStartDate = null; finishedGoodsEndDate = null; handleFinishedGoodsDateChange()"
                class="px-3 py-2 text-sm bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
              >
                Clear
              </button>
              <div v-if="loadingSnapshot" class="flex items-center text-sm text-blue-600 dark:text-blue-400">
                <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading...
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                  <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Products</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ finishedGoodsStats.totalProducts.toLocaleString() }}</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                  <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">In Stock</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ finishedGoodsStats.inStock.toLocaleString() }}</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                  <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Low Stock</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ finishedGoodsStats.lowStock.toLocaleString() }}</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                  <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Out of Stock</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ finishedGoodsStats.outOfStock.toLocaleString() }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Value Section -->
          <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Inventory Value</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
              <!-- Total Combined Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-green-100 dark:bg-green-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Total Value</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(finishedGoodsStats.totalValue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Vee Belts Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-purple-100 dark:bg-purple-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Vee Belts</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(finishedGoodsStats.beltTypeValues.vee || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Cogged Belts Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-indigo-100 dark:bg-indigo-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Cogged Belts</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(finishedGoodsStats.beltTypeValues.cogged || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Poly Belts Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-pink-100 dark:bg-pink-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-pink-600 dark:text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Poly Belts</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(finishedGoodsStats.beltTypeValues.poly || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- TPU Belts Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-orange-100 dark:bg-orange-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">TPU Belts</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(finishedGoodsStats.beltTypeValues.tpu || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Timing Belts Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-teal-100 dark:bg-teal-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-teal-600 dark:text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Timing Belts</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(finishedGoodsStats.beltTypeValues.timing || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Special Belts Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-cyan-100 dark:bg-cyan-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-600 dark:text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Special Belts</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(finishedGoodsStats.beltTypeValues.special || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stock Alert Section -->
          <div class="mt-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Excel Stock Reports</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Send comprehensive Excel stock reports with die requirements via email</p>
                </div>
                <div class="flex gap-3">
                  <button 
                    @click="sendStockAlert"
                    :disabled="sendingAlert"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                  >
                    <svg v-if="sendingAlert" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ sendingAlert ? 'Sending Excel...' : 'Email Excel Report' }}
                  </button>
                  
                  <button 
                    @click="sendSmartStockAlert"
                    :disabled="sendingSmartAlert"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                  >
                    <svg v-if="sendingSmartAlert" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ sendingSmartAlert ? 'Sending Smart Excel...' : 'Email Smart Excel Report' }}
                  </button>
                  
                  <button 
                    @click="downloadExcelReport"
                    :disabled="downloadingExcel"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                  >
                    <svg v-if="downloadingExcel" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ downloadingExcel ? 'Downloading...' : 'Download Excel Report' }}
                  </button>
                </div>
              </div>
              
              <!-- Alert Status Messages -->
              <div v-if="alertMessage" class="mt-4 p-3 rounded-lg" :class="alertMessage.type === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'">
                <div class="flex items-center">
                  <svg v-if="alertMessage.type === 'success'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  <svg v-else class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="font-medium">Stock Alert:</span>
                  <span class="ml-1">{{ alertMessage.text }}</span>
                </div>
              </div>
              
              <div v-if="smartAlertMessage" class="mt-4 p-3 rounded-lg" :class="smartAlertMessage.type === 'success' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'">
                <div class="flex items-center">
                  <svg v-if="smartAlertMessage.type === 'success'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  <svg v-else class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="font-medium">Smart Alert:</span>
                  <span class="ml-1">{{ smartAlertMessage.text }}</span>
                </div>
              </div>

              <!-- Die Requirements Summary -->
              <div v-if="dieRequirements && Object.keys(dieRequirements).length > 0" class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                <div class="flex items-center mb-3">
                  <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                  </svg>
                  <h4 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Die Requirements Summary</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div v-for="(sections, beltType) in dieRequirements" :key="beltType" class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-amber-200 dark:border-amber-700">
                    <h5 class="font-medium text-gray-900 dark:text-white capitalize mb-2">{{ beltType }} Belts</h5>
                    <div v-for="section in sections" :key="section.section" class="flex justify-between items-center text-sm py-1">
                      <span class="text-gray-600 dark:text-gray-400">{{ section.section }}:</span>
                      <div class="flex items-center">
                        <span class="font-medium text-amber-700 dark:text-amber-300 mr-1">{{ section.total_dies }}</span>
                        <span class="text-xs text-gray-500">dies</span>
                        <span class="text-xs text-gray-400 ml-1">({{ section.items_count }} items)</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div v-else-if="!loadingDieRequirements" class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-green-800 dark:text-green-200 font-medium">🎉 No die requirements needed - all items are adequately stocked!</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Raw Materials Stats Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 flex-1 min-h-0">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-4 py-2 rounded-lg">Raw Materials</h2>
            <div class="flex items-center gap-3">
              <div class="flex items-center rounded-lg p-2">
                <div class="relative max-w-sm">
                  <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                  </div>
                  <input id="datepicker-raw-start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Start date">
                </div>
                <span class="mx-2 text-gray-500 dark:text-gray-400">to</span>
                <div class="relative max-w-sm">
                  <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                  </div>
                  <input id="datepicker-raw-end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="End date">
                </div>
              </div>
              <button 
                v-if="rawMaterialsStartDate || rawMaterialsEndDate"
                @click="rawMaterialsStartDate = null; rawMaterialsEndDate = null; handleRawMaterialsDateChange()"
                class="px-3 py-2 text-sm bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
              >
                Clear
              </button>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                  <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Materials</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ rawMaterialsStats.totalProducts.toLocaleString() }}</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                  <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Available</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ rawMaterialsStats.inStock.toLocaleString() }}</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                  <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Low Stock</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ rawMaterialsStats.lowStock.toLocaleString() }}</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
              <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                  <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Out of Stock</p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ rawMaterialsStats.outOfStock.toLocaleString() }}</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Raw Materials Inventory Value -->
          <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Raw Materials Inventory Value</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
              <!-- Total Value -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-purple-100 dark:bg-purple-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Total Value</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.totalValue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Carbon -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Carbon</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Carbon'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Chemical -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-blue-100 dark:bg-blue-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Chemical</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Chemical'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Cord (Combined) -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-orange-100 dark:bg-orange-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Cord (All)</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number((rawMaterialsStats.categoryValues['Cord - Cogged Belt'] || 0) + (rawMaterialsStats.categoryValues['Cord - Timing Belt'] || 0) + (rawMaterialsStats.categoryValues['Cord - Vee Belt'] || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Fabric (Combined) -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-teal-100 dark:bg-teal-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-teal-600 dark:text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Fabric (All)</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number((rawMaterialsStats.categoryValues['Fabric - Cogged Belt'] || 0) + (rawMaterialsStats.categoryValues['Fabric - Timing Belt'] || 0) + (rawMaterialsStats.categoryValues['Fabric - Vee Belt'] || 0) + (rawMaterialsStats.categoryValues['Fabric - TPU Belt'] || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Oil -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-amber-100 dark:bg-amber-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Oil</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Oil'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Others -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-slate-100 dark:bg-slate-700 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Others</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Others'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Resin -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Resin</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Resin'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Rubber -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-red-100 dark:bg-red-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Rubber</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Rubber'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- TPU -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-cyan-100 dark:bg-cyan-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-cyan-600 dark:text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">TPU</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['TPU'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Fibre Glass Cord -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-emerald-100 dark:bg-emerald-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Fibre Glass Cord</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Fibre Glass Cord'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Steel Wire -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-zinc-100 dark:bg-zinc-700 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Steel Wire</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Steel Wire'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Packing Material -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-rose-100 dark:bg-rose-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-rose-600 dark:text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Packing Material</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Packing'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>

              <!-- Open -->
              <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 sm:p-6">
                <div class="flex items-center">
                  <div class="p-2 sm:p-3 rounded-full bg-lime-100 dark:bg-lime-900 flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-lime-600 dark:text-lime-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                  </div>
                  <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 truncate">Open</p>
                    <p class="text-lg sm:text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white break-all">₹{{ Number(rawMaterialsStats.categoryValues['Open'] || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>