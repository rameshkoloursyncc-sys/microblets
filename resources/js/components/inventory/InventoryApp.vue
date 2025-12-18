<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import FlowbiteTable from './FlowbiteTable_clean.vue'
import Sidebar from './SideBar.vue'
import CreateProduct from './CreateProduct.vue'
import { Datepicker } from 'flowbite'
import VeeBeltTable from './VeeBeltTable.vue'
import CoggedBeltTable from './CoggedBeltTable.vue'
import PolyBeltTable from './PolyBeltTable.vue'
import ATable from './tables/veebelts/A_table.vue'
import BTable from './tables/veebelts/B_table.vue'
import CTable from './tables/veebelts/C_Table.vue'
import DTable from './tables/veebelts/D_table.vue'
import ETable from './tables/veebelts/E_table.vue'
import SPATable from './tables/veebelts/SPA_table.vue'
import SPBTable from './tables/veebelts/SPB_table.vue'
import SPCTable from './tables/veebelts/SPC_table.¯vue'
import SPZTable from './tables/veebelts/SPZ_table.vue'
import V3VTable from './tables/veebelts/3V_table.vue'
import V5VTable from './tables/veebelts/5V_table.vue'
import V8VTable from './tables/veebelts/8V_table.vue'
import T5MTable from './tables/tpubelts/T5M_table.vue'
import T8MTable from './tables/tpubelts/T8M_table.vue'
import T8MRPPTable from './tables/tpubelts/T8m_RPP_table.vue'
import TS8MTable from './tables/tpubelts/TS8M_table.vue'
import TpuBeltTable from './TpuBeltTable.vue'
import T14MTable from './tables/tpubelts/T14M_table.vue'
import TXLTable from './tables/tpubelts/XL_table.vue'
import TLTable from './tables/tpubelts/L_table.vue'
import THTable from './tables/tpubelts/H_table.vue'
import AT5Table from './tables/tpubelts/AT5_table.vue'
import AT10Table from './tables/tpubelts/AT10_table.vue'
import T10Table from './tables/tpubelts/T10_table.vue'
import AT20Table from './tables/tpubelts/AT20_table.vue'
import DPKTable from './tables/polybelts/DPK_table.vue'
import DPLTable from './tables/polybelts/DPL_table.vue'
import PHTable from './tables/polybelts/PH_table.vue'
import PJTable from './tables/polybelts/PJ_table.vue'
import PKTable from './tables/polybelts/PK_table.vue'
import PLTable from './tables/polybelts/PL_table.vue'
import PMTable from './tables/polybelts/PM_table.vue'
import FiveVXTable from './tables/veebelts/5VX_table.vue'
import SettingsPage from './SettingsPage.vue'
const currentView = ref('inventory')
const sidebarCollapsed = ref(false)
const globalSectionQuery = ref('')
const globalSizeQuery = ref('')

// Handle sidebar toggle
const handleSidebarToggle = (collapsed: boolean) => {
  sidebarCollapsed.value = collapsed
}

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
  // Individual Timing Belt Types - Commercial
  'timing-belts-commercial-xl': { title: 'Commercial XL Timing Belts Inventory', categories: ['XL Section'] },
  'timing-belts-commercial-l': { title: 'Commercial L Timing Belts Inventory', categories: ['L Section'] },
  'timing-belts-commercial-h': { title: 'Commercial H Timing Belts Inventory', categories: ['H Section'] },
  'timing-belts-commercial-xh': { title: 'Commercial XH Timing Belts Inventory', categories: ['XH Section'] },
  'timing-belts-commercial-t5': { title: 'Commercial T5 Timing Belts Inventory', categories: ['T5 Section'] },
  'timing-belts-commercial-t10': { title: 'Commercial T10 Timing Belts Inventory', categories: ['T10 Section'] },
  'timing-belts-commercial-5m': { title: 'Commercial 5M Timing Belts Inventory', categories: ['5M Section'] },
  'timing-belts-commercial-8m': { title: 'Commercial 8M Timing Belts Inventory', categories: ['8M Section'] },
  'timing-belts-commercial-14m': { title: 'Commercial 14M Timing Belts Inventory', categories: ['14M Section'] },
  'timing-belts-commercial-dl': { title: 'Commercial DL Timing Belts Inventory', categories: ['DL Section'] },
  'timing-belts-commercial-dh': { title: 'Commercial DH Timing Belts Inventory', categories: ['DH Section'] },
  'timing-belts-commercial-d5m': { title: 'Commercial D5M Timing Belts Inventory', categories: ['D5M Section'] },
  'timing-belts-commercial-d8m': { title: 'Commercial D8M Timing Belts Inventory', categories: ['D8M Section'] },
  // Individual Timing Belt Types - Neoprene
  'timing-belts-neoprene-xl': { title: 'Neoprene XL Timing Belts Inventory', categories: ['XL Section'] },
  'timing-belts-neoprene-l': { title: 'Neoprene L Timing Belts Inventory', categories: ['L Section'] },
  'timing-belts-neoprene-h': { title: 'Neoprene H Timing Belts Inventory', categories: ['H Section'] },
  'timing-belts-neoprene-xh': { title: 'Neoprene XH Timing Belts Inventory', categories: ['XH Section'] },
  'timing-belts-neoprene-t5': { title: 'Neoprene T5 Timing Belts Inventory', categories: ['T5 Section'] },
  'timing-belts-neoprene-t10': { title: 'Neoprene T10 Timing Belts Inventory', categories: ['T10 Section'] },
  'timing-belts-neoprene-5m': { title: 'Neoprene 5M Timing Belts Inventory', categories: ['5M Section'] },
  'timing-belts-neoprene-8m': { title: 'Neoprene 8M Timing Belts Inventory', categories: ['8M Section'] },
  'timing-belts-neoprene-14m': { title: 'Neoprene 14M Timing Belts Inventory', categories: ['14M Section'] },
  'timing-belts-neoprene-dl': { title: 'Neoprene DL Timing Belts Inventory', categories: ['DL Section'] },
  'timing-belts-neoprene-dh': { title: 'Neoprene DH Timing Belts Inventory', categories: ['DH Section'] },
  'timing-belts-neoprene-d5m': { title: 'Neoprene D5M Timing Belts Inventory', categories: ['D5M Section'] },
  'timing-belts-neoprene-d8m': { title: 'Neoprene D8M Timing Belts Inventory', categories: ['D8M Section'] },
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
const customViewMapping = computed(() => ({
  // Vee Belts Search - All sections
  'vee-belts-search': { component: VeeBeltTable, props: { title: 'Vee Belts Search Results', globalSearch: globalSectionQuery.value || globalSizeQuery.value } },
  // Vee Belts - Using new backend-connected VeeBeltTable
  'vee-belts-a-page': { component: VeeBeltTable, props: { section: 'A', title: 'A Section Inventory' } },
  
  // Cogged Belts - Using new backend-connected CoggedBeltTable
  'cogged-belts-search': { component: CoggedBeltTable, props: { title: 'Cogged Belts Search Results', globalSearch: globalSectionQuery.value || globalSizeQuery.value } },
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
  'poly-belts-search': { component: PolyBeltTable, props: { title: 'Poly Belts Search Results', globalSearch: globalSectionQuery.value || globalSizeQuery.value } },
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
  
  // Settings page
  'settings': { component: SettingsPage, props: {} },
  
  'poly-belts-pj-page': PJTable,
  'poly-belts-pk-page': PKTable,
  'poly-belts-pl-page': PLTable,
  'poly-belts-pm-page': PMTable,
  'poly-belts-ph-page': PHTable,
  'poly-belts-dpl-page': DPLTable,
  'poly-belts-dpk-page': DPKTable,
}))
const handleNavigation = (view: string) => {
  currentView.value = view
  // Clear global search when navigating to specific sections
  if (view !== 'inventory') {
    globalSectionQuery.value = ''
    globalSizeQuery.value = ''
  }
  // Initialize datepickers when navigating to dashboard
  if (view === 'dashboard') {
    initializeDatepickers()
  }
}

const handleSearch = (searchData: { type: string; sectionQuery?: string; sizeQuery?: string }) => {
  // Set global search parameters for combined search
  globalSectionQuery.value = searchData.sectionQuery || ''
  globalSizeQuery.value = searchData.sizeQuery || ''
  
  // Navigate to vee-belts-search view to show all vee belts search results
  currentView.value = 'vee-belts-search'
  
  console.log('Vee Belts search activated:', {
    section: globalSectionQuery.value,
    size: globalSizeQuery.value
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
    <Sidebar @navigate="handleNavigation" @sidebar-toggle="handleSidebarToggle" @search="handleSearch" />
    <div v-if="currentView === 'inventory'">
      <FlowbiteTable 
        :globalSectionQuery="globalSectionQuery"
        :globalSizeQuery="globalSizeQuery"
        @clear-global-search="clearGlobalSearch"
      />
    </div>
    <div v-else-if="currentView === 'spa-groups' || currentView === 'spa-l-groups'">
      <FlowbiteTable
        title="SPA Section Inventory"
        initialCategory="SPA Section"
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
    :key="currentView"
  />
</div>

<!-- ✅ Normal inventory table pages -->
<div v-else-if="navigationMapping[currentView]">
  <FlowbiteTable
    :title="navigationMapping[currentView].title"
    :initialCategories="navigationMapping[currentView].categories"
  />
</div>

    <div v-else-if="currentView === 'create-product'" :class="sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'" class="transition-all duration-300">
      <CreateProduct />
    </div>
    <div v-else-if="currentView === 'dashboard'" :class="sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'" class="transition-all duration-300">
      <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">1,247</p>
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">892</p>
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">23</p>
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">12</p>
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">456</p>
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">389</p>
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">34</p>
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
                  <p class="text-2xl font-semibold text-gray-900 dark:text-white">8</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>