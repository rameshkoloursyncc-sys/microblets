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
const currentView = ref('inventory')

// Stock alert functionality
const sendingAlert = ref(false)
const alertMessage = ref<{type: 'success' | 'error', text: string} | null>(null)
const sidebarCollapsed = ref(false)
const globalSectionQuery = ref('')
const globalSizeQuery = ref('')

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
  outOfStock: 0
})

// Authentication
const { user, isAuthenticated, isAdmin, initAuth, login, logout, startSessionKeepAlive } = useAuth()
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
    }
  } catch (error) {
    console.error('Auth initialization error:', error)
  } finally {
    authLoading.value = false
    console.log('Auth loading complete, authenticated:', isAuthenticated.value)
  }
})

// Watch for view changes to load stats when dashboard is accessed
watch(currentView, async (newView) => {
  if (newView === 'dashboard' && isAuthenticated.value) {
    console.log('📊 Dashboard view accessed, loading stats...')
    await loadDashboardStats()
  }
})

// Handle sidebar toggle
const handleSidebarToggle = (collapsed: boolean) => {
  sidebarCollapsed.value = collapsed
}

// Load dashboard statistics
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

    // For now, set raw materials to placeholder (no API available yet)
    rawMaterialsStats.value = {
      totalProducts: 0,
      inStock: 0,
      lowStock: 0,
      outOfStock: 0
    }

  } catch (error) {
    console.error('❌ Error loading dashboard stats:', error)
    console.error('Error details:', error.response?.data || error.message)
    
    // Fallback: try to get debug info for all belt types
    try {
      console.log('🔍 Fetching debug info for all belt types...')
      const debugResponse = await axios.get('/api/dashboard/all-belts-debug')
      console.log('🔍 All belts debug info:', debugResponse.data)
      
      // Also get individual debug info
      const individualDebugPromises = [
        axios.get('/api/dashboard/vee-belts-debug').then(r => ({ type: 'vee', data: r.data })),
        axios.get('/api/dashboard/cogged-belts-debug').then(r => ({ type: 'cogged', data: r.data })),
        axios.get('/api/dashboard/poly-belts-debug').then(r => ({ type: 'poly', data: r.data })),
        axios.get('/api/dashboard/tpu-belts-debug').then(r => ({ type: 'tpu', data: r.data })),
        axios.get('/api/dashboard/timing-belts-debug').then(r => ({ type: 'timing', data: r.data })),
        axios.get('/api/dashboard/special-belts-debug').then(r => ({ type: 'special', data: r.data }))
      ]
      
      const individualDebugResults = await Promise.allSettled(individualDebugPromises)
      individualDebugResults.forEach((result, index) => {
        if (result.status === 'fulfilled') {
          console.log(`🔍 ${result.value.type} belts debug:`, result.value.data)
        } else {
          console.error(`❌ Failed to get ${['vee', 'cogged', 'poly', 'tpu', 'timing', 'special'][index]} debug:`, result.reason)
        }
      })
      
    } catch (debugError) {
      console.error('❌ Debug requests failed:', debugError)
    }
    
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
    rawMaterialsStats.value = { totalProducts: 0, inStock: 0, lowStock: 0, outOfStock: 0 }
  }
}

// Send stock alert email
const sendStockAlert = async () => {
  sendingAlert.value = true
  alertMessage.value = null
  
  try {
    console.log('📧 Sending stock alert report...')
    
    const response = await axios.post('/api/dashboard/send-stock-alert', {
      force: true // Send even if no alerts (for testing)
    })
    
    if (response.data.success) {
      alertMessage.value = {
        type: 'success',
        text: response.data.message
      }
      console.log('✅ Stock alert sent successfully:', response.data)
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
        new Datepicker(finishedStartEl, {
          format: 'mm/dd/yyyy',
          autohide: true,
          orientation: 'bottom'
        })
      }

      if (finishedEndEl) {
        new Datepicker(finishedEndEl, {
          format: 'mm/dd/yyyy',
          autohide: true,
          orientation: 'bottom'
        })
      }

      if (rawStartEl) {
        new Datepicker(rawStartEl, {
          format: 'mm/dd/yyyy',
          autohide: true,
          orientation: 'bottom'
        })
      }

      if (rawEndEl) {
        new Datepicker(rawEndEl, {
          format: 'mm/dd/yyyy',
          autohide: true,
          orientation: 'bottom'
        })
      }
    } catch (error) {
      console.error('Error initializing datepickers:', error)
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
  'raw-material-carbon': { title: 'Raw Material - Carbon', categories: ['Carbon'] },
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
        key: `cogged-search-${globalSectionQuery.value}-${globalSizeQuery.value}` // Force re-render on change
      } 
    },
  'cogged-belts-ax': { component: CoggedBeltTable, props: { section: 'AX', title: 'AX Section Inventory' } },
  'cogged-belts-bx': { component: CoggedBeltTable, props: { section: 'BX', title: 'BX Section Inventory' } },
  'cogged-belts-cx': { component: CoggedBeltTable, props: { section: 'CX', title: 'CX Section Inventory' } },
  'cogged-belts-xpa': { component: CoggedBeltTable, props: { section: 'XPA', title: 'XPA Section Inventory' } },
  'cogged-belts-xpb': { component: CoggedBeltTable, props: { section: 'XPB', title: 'XPB Section Inventory' } },
  'cogged-belts-xpc': { component: CoggedBeltTable, props: { section: 'XPC', title: 'XPC Section Inventory' } },
  'cogged-belts-xpz': { component: CoggedBeltTable, props: { section: 'XPZ', title: 'XPZ Section Inventory' } },
  'cogged-belts-3vx': { component: CoggedBeltTable, props: { section: '3VX', title: '3VX Section Inventory' } },
  'cogged-belts-5vx': { component: CoggedBeltTable, props: { section: '5VX', title: '5VX Section Inventory' } },
  
    // Poly Belts - Using new backend-connected PolyBeltTable
    'poly-belts-search': { 
      component: PolyBeltTable, 
      props: { 
        title: 'Poly Belts Search Results', 
        section: globalSectionQuery.value,
        globalSearch: globalSizeQuery.value,
        key: `poly-search-${globalSectionQuery.value}-${globalSizeQuery.value}` // Force re-render on change
      } 
    },
    
    // TPU Belts - Using new backend-connected TpuBeltTable
    'tpu-belts-search': { 
      component: TpuBeltTable, 
      props: { 
        title: 'TPU Belts Search Results', 
        section: globalSectionQuery.value,
        globalSearch: globalSizeQuery.value,
        key: `tpu-search-${globalSectionQuery.value}-${globalSizeQuery.value}` // Force re-render on change
      } 
    },
  'poly-belts-pj': { component: PolyBeltTable, props: { section: 'PJ', title: 'PJ Section Inventory' } },
  'poly-belts-pk': { component: PolyBeltTable, props: { section: 'PK', title: 'PK Section Inventory' } },
  'poly-belts-pl': { component: PolyBeltTable, props: { section: 'PL', title: 'PL Section Inventory' } },
  'poly-belts-pm': { component: PolyBeltTable, props: { section: 'PM', title: 'PM Section Inventory' } },
  'poly-belts-ph': { component: PolyBeltTable, props: { section: 'PH', title: 'PH Section Inventory' } },
  'poly-belts-dpl': { component: PolyBeltTable, props: { section: 'DPL', title: 'DPL Section Inventory' } },
  'poly-belts-dpk': { component: PolyBeltTable, props: { section: 'DPK', title: 'DPK Section Inventory' } },
  
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

  'tpu-belts-t8m-page': { component: TpuBeltTable, props: { section: '8M', title: '8M Section Inventory' } },
  'tpu-belts-t5m-page': { component: TpuBeltTable, props: { section: '5M', title: '5M Section Inventory' } },
  'tpu-belts-t8m-RPP-page': { component: TpuBeltTable, props: { section: '8M RPP', title: '8M RPP Section Inventory' } },
  'tpu-belts-ts8m-page': { component: TpuBeltTable, props: { section: 'S8M', title: 'S8M Section Inventory' } },
  'tpu-belts-t14m-page': { component: TpuBeltTable, props: { section: '14M', title: '14M Section Inventory' } },
  'tpu-belts-txl-page': { component: TpuBeltTable, props: { section: 'XL', title: 'XL Section Inventory' } },
  'tpu-belts-tlm-page': { component: TpuBeltTable, props: { section: 'L', title: 'L Section Inventory' } },
  'tpu-belts-thm-page': { component: TpuBeltTable, props: { section: 'H', title: 'H Section Inventory' } },
  'tpu-belts-at5m-page': { component: TpuBeltTable, props: { section: 'AT5', title: 'AT5 Section Inventory' } },
  'tpu-belts-at10m-page': { component: TpuBeltTable, props: { section: 'AT10', title: 'AT10 Section Inventory' } },
  'tpu-belts-at20m-page': { component: TpuBeltTable, props: { section: 'AT20', title: 'AT20 Section Inventory' } },
  'tpu-belts-t10M-page': { component: TpuBeltTable, props: { section: 'T10', title: 'T10 Section Inventory' } },
  
  // Timing Belts - Using new backend-connected TimingBeltTable
  'timing-belts-search': { 
    component: TimingBeltTable, 
    props: { 
      title: 'Timing Belts Search Results', 
      section: globalSectionQuery.value,
      globalSearch: globalSizeQuery.value,
      key: `timing-search-${globalSectionQuery.value}-${globalSizeQuery.value}` // Force re-render on change
    } 
  },
  'timing-belts-xl': { component: TimingBeltTable, props: { section: 'XL', title: 'XL Section Inventory' } },
  'timing-belts-l': { component: TimingBeltTable, props: { section: 'L', title: 'L Section Inventory' } },
  'timing-belts-h': { component: TimingBeltTable, props: { section: 'H', title: 'H Section Inventory' } },
  'timing-belts-xh': { component: TimingBeltTable, props: { section: 'XH', title: 'XH Section Inventory' } },
  'timing-belts-t5': { component: TimingBeltTable, props: { section: 'T5', title: 'T5 Section Inventory' } },
  'timing-belts-t10': { component: TimingBeltTable, props: { section: 'T10', title: 'T10 Section Inventory' } },
  'timing-belts-3m': { component: TimingBeltTable, props: { section: '3M', title: '3M Section Inventory' } },
  'timing-belts-5m': { component: TimingBeltTable, props: { section: '5M', title: '5M Section Inventory' } },
  'timing-belts-8m': { component: TimingBeltTable, props: { section: '8M', title: '8M Section Inventory' } },
  'timing-belts-14m': { component: TimingBeltTable, props: { section: '14M', title: '14M Section Inventory' } },
  'timing-belts-dl': { component: TimingBeltTable, props: { section: 'DL', title: 'DL Section Inventory' } },
  'timing-belts-dh': { component: TimingBeltTable, props: { section: 'DH', title: 'DH Section Inventory' } },
  'timing-belts-d5m': { component: TimingBeltTable, props: { section: 'D5M', title: 'D5M Section Inventory' } },
  'timing-belts-d8m': { component: TimingBeltTable, props: { section: 'D8M', title: 'D8M Section Inventory' } },
  'timing-belts-neoprene-xl': { component: TimingBeltTable, props: { section: 'NEOPRENE-XL', title: 'Neoprene XL Section Inventory' } },
  'timing-belts-neoprene-l': { component: TimingBeltTable, props: { section: 'NEOPRENE-L', title: 'Neoprene L Section Inventory' } },
  'timing-belts-neoprene-h': { component: TimingBeltTable, props: { section: 'NEOPRENE-H', title: 'Neoprene H Section Inventory' } },
  'timing-belts-neoprene-xh': { component: TimingBeltTable, props: { section: 'NEOPRENE-XH', title: 'Neoprene XH Section Inventory' } },
  'timing-belts-neoprene-t5': { component: TimingBeltTable, props: { section: 'NEOPRENE-T5', title: 'Neoprene T5 Section Inventory' } },
  'timing-belts-neoprene-t10': { component: TimingBeltTable, props: { section: 'NEOPRENE-T10', title: 'Neoprene T10 Section Inventory' } },
  'timing-belts-neoprene-3m': { component: TimingBeltTable, props: { section: 'NEOPRENE-3M', title: 'Neoprene 3M Section Inventory' } },
  'timing-belts-neoprene-5m': { component: TimingBeltTable, props: { section: 'NEOPRENE-5M', title: 'Neoprene 5M Section Inventory' } },
  'timing-belts-neoprene-8m': { component: TimingBeltTable, props: { section: 'NEOPRENE-8M', title: 'Neoprene 8M Section Inventory' } },
  'timing-belts-neoprene-14m': { component: TimingBeltTable, props: { section: 'NEOPRENE-14M', title: 'Neoprene 14M Section Inventory' } },
  'timing-belts-neoprene-dl': { component: TimingBeltTable, props: { section: 'NEOPRENE-DL', title: 'Neoprene DL Section Inventory' } },
  'timing-belts-neoprene-dh': { component: TimingBeltTable, props: { section: 'NEOPRENE-DH', title: 'Neoprene DH Section Inventory' } },
  'timing-belts-neoprene-d5m': { component: TimingBeltTable, props: { section: 'NEOPRENE-D5M', title: 'Neoprene D5M Section Inventory' } },
  'timing-belts-neoprene-d8m': { component: TimingBeltTable, props: { section: 'NEOPRENE-D8M', title: 'Neoprene D8M Section Inventory' } },
  
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
  
    'poly-belts-pj-page': PJTable,
    'poly-belts-pk-page': PKTable,
    'poly-belts-pl-page': PLTable,
    'poly-belts-pm-page': PMTable,
    'poly-belts-ph-page': PHTable,
    'poly-belts-dpl-page': DPLTable,
    'poly-belts-dpk-page': DPKTable,
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
  
  // Check if it's a vee belt section
  const veeSections = ['A', 'B', 'C', 'D', 'E', 'SPA', 'SPB', 'SPC', 'SPZ', '3V', '5V', '8V']
  // Check if it's a cogged belt section
  const coggedSections = ['AX', 'BX', 'CX', 'XPA', 'XPB', 'XPC', 'XPZ', '3VX', '5VX', '8VX']
  // Check if it's a poly belt section
  const polySections = ['PJ', 'PK', 'PL', 'PM', 'PH', 'DPL', 'DPK']
  // Check if it's a TPU belt section
  const tpuSections = ['5M', '8M', '8M RPP', 'S8M', '14M', 'XL', 'L', 'H', 'AT5', 'AT10', 'T10', 'AT20']
  // Check if it's a timing belt section
  const timingSections = ['XL', 'L', 'H', 'XH', 'T5', 'T10', '5M', '8M', '14M', 'DL', 'DH', 'D5M', 'D8M', 'NEOPRENE-XL', 'NEOPRENE-L', 'NEOPRENE-H', 'NEOPRENE-XH', 'NEOPRENE-T5', 'NEOPRENE-T10']
  
  // Simple logic: section determines the view, size is just a filter
  if (section) {
    if (globalSizeQuery.value) {
      // Section + Size = Use search view for filtering
      if (veeSections.includes(section)) {
        currentView.value = 'vee-belts-search'
      } else if (coggedSections.includes(section)) {
        currentView.value = 'cogged-belts-search'
      } else if (polySections.includes(section)) {
        currentView.value = 'poly-belts-search'
      } else if (tpuSections.includes(section)) {
        currentView.value = 'tpu-belts-search'
      } else if (timingSections.includes(section)) {
        currentView.value = 'timing-belts-search'
      } else {
        currentView.value = 'inventory'
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
      } else if (tpuSections.includes(section)) {
        const sectionPageMap: Record<string, string> = {
          '5M': 'tpu-belts-t5m-page',
          '8M': 'tpu-belts-t8m-page',
          '8M RPP': 'tpu-belts-t8m-RPP-page',
          'S8M': 'tpu-belts-ts8m-page',
          '14M': 'tpu-belts-t14m-page',
          'XL': 'tpu-belts-txl-page',
          'L': 'tpu-belts-tlm-page',
          'H': 'tpu-belts-thm-page',
          'AT5': 'tpu-belts-at5m-page',
          'AT10': 'tpu-belts-at10m-page',
          'T10': 'tpu-belts-t10M-page',
          'AT20': 'tpu-belts-at20m-page'
        }
        currentView.value = sectionPageMap[section] || 'tpu-belts-search'
      } else if (timingSections.includes(section)) {
        const sectionPageMap: Record<string, string> = {
          'XL': 'timing-belts-xl',
          'L': 'timing-belts-l',
          'H': 'timing-belts-h',
          'XH': 'timing-belts-xh',
          'T5': 'timing-belts-t5',
          'T10': 'timing-belts-t10',
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
          'NEOPRENE-T10': 'timing-belts-neoprene-t10'
        }
        currentView.value = sectionPageMap[section] || 'timing-belts-search'
      } else {
        currentView.value = 'inventory'
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
  v-else-if="customViewMapping[currentView]"
>
  <component 
    :is="customViewMapping[currentView].component || customViewMapping[currentView]" 
    v-bind="customViewMapping[currentView].props || {}"
    :sidebar-collapsed="sidebarCollapsed"
    :key="customViewMapping[currentView].props?.key || currentView"
  />
</div>

<!-- ✅ Normal inventory table pages -->
<div v-else-if="navigationMapping[currentView]">
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
        </div>
        
        <!-- Finished Goods Stats Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-4 flex-1 min-h-0">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg">Finished Goods</h2>
            <div class="flex items-center  rounded-lg p-2 ">
              <div class="relative max-w-sm">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                  <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                </div>
                <input id="datepicker-finished-start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date start">
              </div>
              <span class="mx-4 text-gray-500 dark:text-gray-400">to</span>
              <div class="relative max-w-sm">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                  <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                </div>
                <input id="datepicker-finished-end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date end">
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
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Stock Alerts</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Send low stock and out of stock report via email</p>
                </div>
                <button 
                  @click="sendStockAlert"
                  :disabled="sendingAlert"
                  class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                >
                  <svg v-if="sendingAlert" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2v10a2 2 0 002 2z"></path>
                  </svg>
                  {{ sendingAlert ? 'Sending...' : 'Send Stock Alert' }}
                </button>
              </div>
              
              <!-- Alert Status -->
              <div v-if="alertMessage" class="mt-4 p-3 rounded-lg" :class="alertMessage.type === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'">
                <div class="flex items-center">
                  <svg v-if="alertMessage.type === 'success'" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  <svg v-else class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <span>{{ alertMessage.text }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Raw Materials Stats Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 flex-1 min-h-0">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-4 py-2 rounded-lg">Raw Materials</h2>
            <div class="flex items-center rounded-lg p-2 ">
              <div class="relative max-w-sm">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                </div>
                <input id="datepicker-raw-start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date start">
              </div>
              <span class="mx-4 text-gray-500 dark:text-gray-400">to</span>
              <div class="relative max-w-sm">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
        <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/></svg>
                </div>
                <input id="datepicker-raw-end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date end">
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
        </div>
      </div>
    </div>
    </div>
  </div>
</template>