<template>
  <div class="transition-all duration-300" :class="props.sidebarCollapsed ? 'sm:ml-16' : 'sm:ml-80'">
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          System Settings
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Manage rates, seed data, and system configurations for all belt types
        </p>
      </div>

      <!-- Belt Type Selector -->
      <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Select Belt Type
        </label>
        <select 
          v-model="selectedBeltType" 
          @change="onBeltTypeChange"
          class="w-full p-3 border rounded-lg bg-white dark:bg-gray-700 dark:text-white text-lg"
        >
          <option value="vee">Vee Belts</option>
          <option value="cogged">Cogged Belts</option>
          <option value="poly">Poly Belts</option>
          <option value="tpu">TPU Belts</option>
          <option value="timing">Timing Belts</option>
          <option value="special">Special Belts</option>
          <option value="raw">Raw Materials</option>
        </select>
      </div>

      <!-- Global Inventory Settings -->
      <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Global Inventory Settings
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Minimum Inventory Setting -->
          <div class="border rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Global Minimum Inventory Level
            </label>
            <div class="flex items-center gap-3">
              <input 
                v-model.number="globalMinInventory" 
                type="number" 
                step="0.01" 
                min="0"
                class="flex-1 p-3 border rounded bg-white dark:bg-gray-700 dark:text-white"
                placeholder="Enter minimum inventory level (e.g., 0 or 5.00)"
              />
              <button 
                @click="updateGlobalMinInventory" 
                class="px-4 py-3 bg-orange-600 text-white rounded hover:bg-orange-700"
                :disabled="loading || globalMinInventory === null || globalMinInventory === undefined"
              >
                Apply to All
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">
              This will set the reorder level for all products in all belt types (Vee, Cogged, Poly, TPU, Timing, Special). You can set this to 0 to disable low stock warnings.
            </p>
          </div>
        </div>

        <!-- Belt Type Specific Min Inventory -->
        <div class="mt-6 border-t pt-4">
          <h3 class="font-medium text-gray-900 dark:text-white mb-3">Belt Type Specific Settings</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div v-for="(config, beltType) in beltTypeConfig" :key="beltType" class="border rounded p-3">
              <h4 class="font-medium text-sm mb-2">{{ config.name }}</h4>
              <div class="flex items-center gap-2">
                <input 
                  v-model.number="specificMinInventory[beltType]" 
                  type="number" 
                  step="0.01" 
                  min="0"
                  class="flex-1 p-2 border rounded text-sm"
                  :placeholder="globalMinInventory !== null && globalMinInventory !== undefined ? globalMinInventory.toString() : '0'"
                />
                <button 
                  @click="updateSpecificMinInventory(beltType)" 
                  class="px-2 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs"
                  :disabled="loading"
                >
                  Set
                </button>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                Products: {{ beltTypeStats[beltType]?.total || 0 }}
              </p>
            </div>
          </div>
        </div>
      </div>

      
        <!-- Global Die Settings -->
    <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          {{ beltTypeConfig[selectedBeltType].name }} Die Configuration
        </h2>
        
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Configure how much stock each die produces for each section. This is used for calculating die requirements in smart alerts.
        </p>
        
        <!-- Debug info (remove in production) -->
        <!-- <div class="mb-4 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs">
          <strong>Debug:</strong> 
          Loaded configs: {{ Object.keys(allDieConfigurations).length }} belt types, 
          Current belt ({{ selectedBeltType }}): {{ Object.keys(currentDefaultDieConfigs).length }} sections,
          Input values: {{ Object.keys(dieConfigurations).length }} sections
        </div> -->
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="section in currentSections" :key="section" class="border rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ section }} Section
            </label>

            <div class="mb-2">
              <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                Stock per Die:
              </label>
              <input 
                v-model.number="dieConfigurations[section]" 
                type="number" 
                step="0.01" 
                min="0.01"
                class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white text-sm"
                :placeholder="(currentDefaultDieConfigs[section] || 30).toString()"
              />
            </div>
            
            <div class="text-xs text-gray-500 mb-2">
              Current: {{ currentDefaultDieConfigs[section] || dieConfigurations[section] || 30 }} units per die
            </div>
            
            <button 
              @click="updateDieConfiguration(section)" 
              class="w-full px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
              :disabled="loading"
            >
              Update Die Config
            </button>
          </div>
        </div>

        <!-- Bulk Actions -->
        <div class="mt-6 flex gap-3">
          <button 
            @click="loadDieConfigurations" 
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
            :disabled="loading"
          >
            {{ loading ? 'Loading...' : 'Refresh Configurations' }}
          </button>
          <button 
            @click="seedDefaultDieConfigurations" 
            class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm"
            :disabled="loading"
          >
            Reset to Defaults
          </button>
        </div>
      </div>



      <!-- Rate Formula Management -->
      <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          {{ beltTypeConfig[selectedBeltType].name }} Rate Formula Management
        </h2>
        
       
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="section in currentSections" :key="section" class="border rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ section }} Section Formula
            </label>
            
            <div class="mb-2 text-xs text-gray-600 dark:text-gray-400">
              <span v-if="selectedBeltType === 'poly'">size ÷ divisor × multiplier</span>
              <span v-else-if="selectedBeltType === 'timing'">(size × type × 450 × multiplier) + (size × total_mm × multiplier)</span>
              <span v-else>size ÷ divisor × multiplier</span>
            </div>
            
            <!-- Divisor input (for all sections) -->
            <div class="mb-2">
              <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                {{ selectedBeltType === 'poly' ? 'Divisor (size):' : 'Divisor (size):' }}
              </label>
              <input 
                v-model.number="divisors[section]" 
                type="number" 
                step="0.1" 
                min="0.1"
                class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white text-sm"
                :placeholder="currentDefaultDivisors[section] || (selectedBeltType === 'poly' ? '25.4' : '1')"
              />
            </div>
            
            <!-- Type Multiplier input (for timing belts only) -->
            <div v-if="selectedBeltType === 'timing'" class="mb-2">
              <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                Type Multiplier (replaces 450):
              </label>
              <input 
                v-model.number="typeMultipliers[section]" 
                type="number" 
                step="1" 
                min="1"
                class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white text-sm"
                :placeholder="currentDefaultTypeMultipliers[section] || '450'"
              />
            </div>
            
            <!-- Multiplier input -->
            <div class="mb-2">
              <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">
                {{ selectedBeltType === 'poly' ? 'Multiplier:' : 'Multiplier:' }}
              </label>
              <input 
                v-model.number="formulas[section]" 
                type="number" 
                step="0.01" 
                min="0"
                class="w-full p-2 border rounded bg-white dark:bg-gray-700 dark:text-white"
                :placeholder="currentDefaultFormulas[section] || '0'"
              />
            </div>
            
            <button 
              @click="updateSectionFormula(section)" 
              class="w-full px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
              :disabled="loading"
            >
              Update Formula
            </button>
            
            <div class="mt-2 text-xs text-gray-500">
              Products: {{ sectionCounts[section] || 0 }}
              <br>Current: {{ getCurrentFormulaText(section) }}
            </div>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <button 
            @click="updateAllFormulas" 
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
            :disabled="loading"
          >
            Update All Formulas
          </button>
          <button 
            @click="resetFormulasToDefaults" 
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
            :disabled="loading"
          >
            Reset to Defaults
          </button>
          <button 
            @click="recalculateAllRates" 
            class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700"
            :disabled="loading"
          >
            Recalculate All Rates
          </button>
        </div>
      </div>

      <!-- Data Seeding -->
     <!-- <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          {{ beltTypeConfig[selectedBeltType].name }} Data Seeding
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="section in currentSections" :key="`seed-${section}`" class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-medium text-gray-900 dark:text-white">{{ section }} Section</h3>
              <span class="text-sm text-gray-500">{{ sectionCounts[section] || 0 }} products</span>
            </div>
            
            <div class="space-y-2">
              <button 
                @click="seedSection(section)" 
                class="w-full px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                :disabled="loading || !hasJsonFile(section)"
              >
                {{ hasJsonFile(section) ? 'Seed from JSON' : 'No JSON File' }}
              </button>
              
              <button 
                @click="clearSection(section)" 
                class="w-full px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                :disabled="loading"
              >
                Clear Section
              </button>
            </div>
          </div>
        </div>

        <div class="mt-6 border-t pt-4">
          <h3 class="font-medium text-gray-900 dark:text-white mb-3">Bulk Operations</h3>
          <div class="flex gap-2 flex-wrap">
            <button 
              @click="seedAllSections" 
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
              :disabled="loading"
            >
              Seed All Sections
            </button>
            <button 
              @click="clearAllData" 
              class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
              :disabled="loading"
            >
              Clear All Data
            </button>
            <button 
              @click="exportAllData" 
              class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700"
              :disabled="loading"
            >
              Export All Data
            </button>
          </div>
        </div>
      </div> -->


    </div>

    <!--  Notifications -->
      <div class="fixed right-4 top-4 space-y-3 z-50">
        <div v-for="n in notifications" :key="n.id" class="rounded shadow p-3 max-w-sm"
             :class="n.type === 'success' ? 'bg-green-100 text-green-800' : n.type === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
          <div class="font-semibold">{{ n.title }}</div>
          <div class="text-sm">{{ n.message }}</div>
        </div>
      </div> 
           
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios, { Axios } from 'axios'


const props = defineProps<{
  sidebarCollapsed?: boolean
}>()


interface Notification { id: number; type: 'success'|'error'|'warning'; title: string; message: string }

const loading = ref(false)
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

// Selected belt type
const selectedBeltType = ref('tpu')

// Belt type configurations
const beltTypeConfig = {
  vee: {
    name: 'Vee Belts',
    apiEndpoint: '/api/vee-belts',
    sections: ['A', 'B', 'C', 'D', 'E', 'SPA', 'SPB', 'SPC', 'SPZ', '3V', '5V', '8V'],
    formulaType: 'size_divide_multiply', // rate = (size ÷ divisor) × multiplier
    defaultFormulas: {
      'A': 1.05, 'B': 1.15, 'C': 1.25, 'D': 1.35, 'E': 1.45,
      'SPA': 1.10, 'SPB': 1.20, 'SPC': 1.30, 'SPZ': 1.00,
      '3V': 1.50, '5V': 1.87, '8V': 2.50
    },
    defaultDivisors: {
      'A': 1, 'B': 1, 'C': 1, 'D': 1, 'E': 1,
      'SPA': 1, 'SPB': 1, 'SPC': 1, 'SPZ': 1,
      '3V': 10, '5V': 10, '8V': 10
    },
    sectionsWithDivisor: ['A', 'B', 'C', 'D', 'E', 'SPA', 'SPB', 'SPC', 'SPZ', '3V', '5V', '8V'],
    jsonFiles: {
      'A': 'AProducts.json', 'B': 'BProducts.json', 'C': 'CProducts.json',
      'D': 'DProducts.json', 'E': 'EProducts.json', 'SPA': 'SPAProducts.json',
      'SPB': 'SPBProducts.json', 'SPC': 'SPCProducts.json', 'SPZ': 'SPZProducts.json',
      '3V': '3VProducts.json', '5V': '5VProducts.json', '8V': '8VProducts.json'
    }
  },
  cogged: {
    name: 'Cogged Belts',
    apiEndpoint: '/api/cogged-belts',
    sections: ['AX', 'BX', 'CX', 'XPA', 'XPB', 'XPC', 'XPZ', '3VX', '5VX'],
    formulaType: 'size_divide_multiply', // rate = (size ÷ divisor) × multiplier
    defaultFormulas: {
      'AX': 1.95, 'BX': 3.45, 'CX': 5.68,
      'XPA': 1.85, 'XPB': 2.95, 'XPC': 4.25, 'XPZ': 1.45,
      '3VX': 2.15, '5VX': 3.25
    },
    defaultDivisors: {
      'AX': 1, 'BX': 1, 'CX': 1,
      'XPA': 1, 'XPB': 1, 'XPC': 1, 'XPZ': 1,
      '3VX': 10, '5VX': 10
    },
    sectionsWithDivisor: ['AX', 'BX', 'CX', 'XPA', 'XPB', 'XPC', 'XPZ', '3VX', '5VX'],
    jsonFiles: {
      'AX': 'AXProducts.json', 'BX': 'BXProducts.json', 'CX': 'CXProducts.json',
      'XPA': 'XPAProducts.json', 'XPB': 'XPBProducts.json', 'XPC': 'XPCProducts.json',
      'XPZ': 'XPZProducts.json', '3VX': '3VXProducts.json', '5VX': '5VXProducts.json'
    }
  },
  poly: {
    name: 'Poly Belts',
    apiEndpoint: '/api/poly-belts',
    sections: ['PJ', 'PK', 'PL', 'PM', 'PH', 'DPL', 'DPK'],
    formulaType: 'ribs_divide_multiply', // rate_per_rib = size ÷ divisor × multiplier
    defaultFormulas: {
      'PJ': 0.36, 'PK': 0.59, 'PL': 0.85, 'PM': 1.25, 'PH': 1.85,
      'DPL': 1.15, 'DPK': 0.89
    },
    defaultDivisors: {
      'PJ': 25.4, 'PK': 25.4, 'PL': 25.4, 'PM': 25.4, 'PH': 25.4,
      'DPL': 25.4, 'DPK': 25.4
    },
    sectionsWithDivisor: ['PJ', 'PK', 'PL', 'PM', 'PH', 'DPL', 'DPK'],
    jsonFiles: {
      'PK': 'PKProducts.json', 'PL': 'PLProducts.json'
    }
  },
  tpu: {
    name: 'TPU Belts',
    apiEndpoint: '/api/tpu-belts',
    sections: ['5M', '8M', '8M RPP', 'S8M', '14M', 'XL', 'L', 'H', 'AT5', 'AT10', 'T10', 'AT20'],
    formulaType: 'size_divide_multiply', // rate = (size ÷ divisor) × multiplier
    defaultFormulas: {
      '5M': 2.50, '8M': 3.20, '8M RPP': 3.50, 'S8M': 4.00, '14M': 5.50,
      'XL': 2.80, 'L': 3.00, 'H': 3.20, 'AT5': 2.00, 'AT10': 3.80, 'T10': 4.00, 'AT20': 6.50
    },
    defaultDivisors: {
      '5M': 1, '8M': 1, '8M RPP': 1, 'S8M': 1, '14M': 1,
      'XL': 1, 'L': 1, 'H': 1, 'AT5': 1, 'AT10': 1, 'T10': 1, 'AT20': 1
    },
    sectionsWithDivisor: ['5M', '8M', '8M RPP', 'S8M', '14M', 'XL', 'L', 'H', 'AT5', 'AT10', 'T10', 'AT20'],
    jsonFiles: {
      '5M': 'TPU5MProducts.json', '8M': 'TPU8MProducts.json', 'S8M': 'TPUS8MProducts.json',
      'H': 'TPUHProducts.json', 'AT10': 'TPUAT10Products.json', 'T10': 'TPUT10Products.json',
      'AT20': 'TPUAT20Products.json'
    }
  },
  timing: {
    name: 'Timing Belts',
    apiEndpoint: '/api/timing-belts',
    sections: ['XL', 'L', 'H', 'XH', 'T5', 'T10', '3M', '5M', '8M', '14M', 'DL', 'DH', 'D5M', 'D8M', 'NEOPRENE-XL', 'NEOPRENE-L', 'NEOPRENE-H', 'NEOPRENE-XH', 'NEOPRENE-T5', 'NEOPRENE-T10', 'NEOPRENE-3M', 'NEOPRENE-5M', 'NEOPRENE-8M', 'NEOPRENE-14M', 'NEOPRENE-DL', 'NEOPRENE-DH', 'NEOPRENE-D5M', 'NEOPRENE-D8M'],
    formulaType: 'timing_belt_formula', // Special formula: (size * type * type_multiplier * multiplier) + (size * total_mm * multiplier)
    defaultFormulas: {
      'XL': 0.0094, 'L': 0.0119, 'H': 0.0117, 'XH': 0.0231, 'T5': 0.0049, 'T10': 0.0053,
      '3M': 0.0054, '5M': 0.0062, '8M': 0.0068, '14M': 0.0114, 'DL': 0.0269, 'DH': 0.0263, 'D5M': 0.0139, 'D8M': 0.0153,
      'NEOPRENE-XL': 0.0105, 'NEOPRENE-L': 0.0134, 'NEOPRENE-H': 0.0131, 'NEOPRENE-XH': 0.0259, 'NEOPRENE-T5': 0.0055, 'NEOPRENE-T10': 0.0059,
      'NEOPRENE-3M': 0.006, 'NEOPRENE-5M': 0.0062, 'NEOPRENE-8M': 0.0076, 'NEOPRENE-14M': 0.0128, 'NEOPRENE-DL': 0.0301, 'NEOPRENE-DH': 0.0294, 'NEOPRENE-D5M': 0.0155, 'NEOPRENE-D8M': 0.0171
    },
    defaultDivisors: {
      'XL': 1, 'L': 1, 'H': 1, 'XH': 1, 'T5': 1, 'T10': 1,
      '3M': 1, '5M': 1, '8M': 1, '14M': 1, 'DL': 1, 'DH': 1, 'D5M': 1, 'D8M': 1,
      'NEOPRENE-XL': 1, 'NEOPRENE-L': 1, 'NEOPRENE-H': 1, 'NEOPRENE-XH': 1, 'NEOPRENE-T5': 1, 'NEOPRENE-T10': 1,
      'NEOPRENE-3M': 1, 'NEOPRENE-5M': 1, 'NEOPRENE-8M': 1, 'NEOPRENE-14M': 1, 'NEOPRENE-DL': 1, 'NEOPRENE-DH': 1, 'NEOPRENE-D5M': 1, 'NEOPRENE-D8M': 1
    },
    defaultTypeMultipliers: {
      'XL': 450, 'L': 450, 'H': 450, 'XH': 430, 'T5': 450, 'T10': 450,
      '3M': 450, '5M': 450, '8M': 450, '14M': 430, 'DL': 200, 'DH': 200, 'D5M': 200, 'D8M': 200,
      'NEOPRENE-XL': 450, 'NEOPRENE-L': 450, 'NEOPRENE-H': 450, 'NEOPRENE-XH': 430, 'NEOPRENE-T5': 450, 'NEOPRENE-T10': 450,
      'NEOPRENE-3M': 450, 'NEOPRENE-5M': 450, 'NEOPRENE-8M': 450, 'NEOPRENE-14M': 430, 'NEOPRENE-DL': 200, 'NEOPRENE-DH': 200, 'NEOPRENE-D5M': 200, 'NEOPRENE-D8M': 200
    },
    sectionsWithDivisor: ['XL', 'L', 'H', 'XH', 'T5', 'T10', '3M', '5M', '8M', '14M', 'DL', 'DH', 'D5M', 'D8M', 'NEOPRENE-XL', 'NEOPRENE-L', 'NEOPRENE-H', 'NEOPRENE-XH', 'NEOPRENE-T5', 'NEOPRENE-T10', 'NEOPRENE-3M', 'NEOPRENE-5M', 'NEOPRENE-8M', 'NEOPRENE-14M', 'NEOPRENE-DL', 'NEOPRENE-DH', 'NEOPRENE-D5M', 'NEOPRENE-D8M'],
    jsonFiles: {
      'XL': 'XLProducts.json', 'L': 'TimingLProducts.json', 'H': 'TimingHProducts.json', 'XH': 'TimingXHProducts.json',
      'T5': 'TimingT5Products.json', 'T10': 'TimingT10Products.json',
      '3M': 'Timing3MProducts.json', '5M': 'Timing5MProducts.json', '8M': 'Timing8MProducts.json', '14M': 'Timing14MProducts.json',
      'DL': 'TimingDLProducts.json', 'DH': 'TimingDHProducts.json', 'D5M': 'TimingD5MProducts.json', 'D8M': 'TimingD8MProducts.json',
      'NEOPRENE-XL': 'NeopreneXLProducts.json', 'NEOPRENE-L': 'NeopreneLProducts.json', 'NEOPRENE-H': 'NeopreneHProducts.json',
      'NEOPRENE-XH': 'NeopreneXHProducts.json', 'NEOPRENE-T5': 'NeopreneT5Products.json', 'NEOPRENE-T10': 'NeopreneT10Products.json',
      'NEOPRENE-3M': 'Neoprene3MProducts.json', 'NEOPRENE-5M': 'Neoprene5MProducts.json', 'NEOPRENE-8M': 'Neoprene8MProducts.json', 'NEOPRENE-14M': 'Neoprene14MProducts.json',
      'NEOPRENE-DL': 'NeopreneDLProducts.json', 'NEOPRENE-DH': 'NeopreneDHProducts.json', 'NEOPRENE-D5M': 'NeopreneD5MProducts.json', 'NEOPRENE-D8M': 'NeopreneD8MProducts.json'
    }
  },
  special: {
    name: 'Special Belts',
    apiEndpoint: '/api/special-belts',
    sections: ['Conical C', 'Harvester', 'RAX', 'RBX', 'R3VX', 'R5VX', '8M PK', '8M PL'],
    formulaType: 'size_divide_multiply', // rate = (size ÷ divisor) × multiplier
    defaultFormulas: {
      'Conical C': 4.50, 'Harvester': 5.20, 'RAX': 3.80, 'RBX': 4.20,
      'R3VX': 3.50, 'R5VX': 4.80, '8M PK': 3.20, '8M PL': 3.60
    },
    defaultDivisors: {
      'Conical C': 1, 'Harvester': 1, 'RAX': 1, 'RBX': 1,
      'R3VX': 1, 'R5VX': 1, '8M PK': 1, '8M PL': 1
    },
    sectionsWithDivisor: ['Conical C', 'Harvester', 'RAX', 'RBX', 'R3VX', 'R5VX', '8M PK', '8M PL'],
    jsonFiles: {
      'Conical C': 'ConicalCProducts.json', 'Harvester': 'HarvesterProducts.json',
      'RAX': 'RAXProducts.json', 'RBX': 'RBXProducts.json'
    }
  },
  raw: {
    name: 'Raw Materials',
    apiEndpoint: '/api/rawcarbon',
    sections: [
      'Carbon', 
      'Chemical', 
      'Cord - Cogged Belt', 
      'Cord - Timing Belt', 
      'Cord - Vee Belt',
      'Fabric - Cogged Belt', 
      'Fabric - Timing Belt', 
      'Fabric - Vee Belt', 
      'Fabric - TPU Belt',
      'Oil', 
      'Others', 
      'Resin',
      'Rubber',
      'TPU', 
      'Fibre Glass Cord', 
      'Steel Wire', 
      'Packing',
      'Open'
    ],
    formulaType: 'simple_rate', // Raw materials use direct rate, no formula calculation
    defaultFormulas: {
      'Carbon': 1.0, 
      'Chemical': 1.0, 
      'Cord - Cogged Belt': 1.0, 
      'Cord - Timing Belt': 1.0, 
      'Cord - Vee Belt': 1.0,
      'Fabric - Cogged Belt': 1.0, 
      'Fabric - Timing Belt': 1.0, 
      'Fabric - Vee Belt': 1.0, 
      'Fabric - TPU Belt': 1.0,
      'Oil': 1.0, 
      'Others': 1.0,
      'Resin': 1.0,
      'Rubber': 1.0,
      'TPU': 1.0, 
      'Fibre Glass Cord': 1.0, 
      'Steel Wire': 1.0, 
      'Packing': 1.0,
      'Open': 1.0
    },
    defaultDivisors: {
      'Carbon': 1, 
      'Chemical': 1, 
      'Cord - Cogged Belt': 1, 
      'Cord - Timing Belt': 1, 
      'Cord - Vee Belt': 1,
      'Fabric - Cogged Belt': 1, 
      'Fabric - Timing Belt': 1, 
      'Fabric - Vee Belt': 1, 
      'Fabric - TPU Belt': 1,
      'Oil': 1, 
      'Others': 1,
      'Resin': 1,
      'Rubber': 1,
      'TPU': 1, 
      'Fibre Glass Cord': 1, 
      'Steel Wire': 1, 
      'Packing': 1,
      'Open': 1
    },
    sectionsWithDivisor: [
      'Carbon', 
      'Chemical', 
      'Cord - Cogged Belt', 
      'Cord - Timing Belt', 
      'Cord - Vee Belt',
      'Fabric - Cogged Belt', 
      'Fabric - Timing Belt', 
      'Fabric - Vee Belt', 
      'Fabric - TPU Belt',
      'Oil', 
      'Others', 
      'Resin',
      'Rubber',
      'TPU', 
      'Fibre Glass Cord', 
      'Steel Wire', 
      'Packing',
      'Open'
    ],
    jsonFiles: {
      'Carbon': 'RawCarbonProducts.json',
      'Chemical': 'RawChemicalProducts.json',
      'Cord - Cogged Belt': 'RawCordCoggedBeltProducts.json',
      'Cord - Timing Belt': 'RawCordTimingBeltProducts.json',
      'Cord - Vee Belt': 'RawCordVeeBeltProducts.json',
      'Fabric - Cogged Belt': 'RawFabricCoggedBeltProducts.json',
      'Fabric - Timing Belt': 'RawFabricTimingBeltProducts.json',
      'Fabric - Vee Belt': 'RawFabricVeeBeltProducts.json',
      'Fabric - TPU Belt': 'RawFabricTPUBeltProducts.json',
      'Oil': 'RawOilProducts.json',
      'Others': 'RawOthersProducts.json',
      'Resin': 'RawResinProducts.json',
      'Rubber': 'RawRubberProducts.json',
      'TPU': 'RawTPUProducts.json',
      'Fibre Glass Cord': 'RawFibreGlassCordProducts.json',
      'Steel Wire': 'RawSteelWireProducts.json',
      'Packing': 'RawPackingProducts.json',
      'Open': 'RawOpenProducts.json'
    }
  }
}

// Current formulas (editable)
const formulas = ref<Record<string, number>>({})
const divisors = ref<Record<string, number>>({})
const typeMultipliers = ref<Record<string, number>>({})

const allFormulas = ref<Record<string, number>>({})

// Die configuration variables
const dieConfigurations = ref<Record<string, number>>({})
const allDieConfigurations = ref<Record<string, Record<string, number>>>({})


// Global inventory settings
const globalMinInventory = ref<number>(0)
const specificMinInventory = ref<Record<string, number>>({})
const globalStats = ref({
  totalProducts: 0,
  lowStock: 0,
  outOfStock: 0,
  wellStocked: 0
})
const beltTypeStats = ref<Record<string, { total: number; lowStock: number; outOfStock: number }>>({})

// Section counts and statistics
const sectionCounts = ref<Record<string, number>>({})
const totalProducts = ref(0)
const totalValue = ref(0)

// Computed properties for current belt type
const currentSections = computed(() => beltTypeConfig[selectedBeltType.value].sections)
const currentDefaultFormulas = computed(() => beltTypeConfig[selectedBeltType.value].defaultFormulas)
const currentDefaultDivisors = computed(() => beltTypeConfig[selectedBeltType.value].defaultDivisors)
const currentDefaultTypeMultipliers = computed(() => beltTypeConfig[selectedBeltType.value].defaultTypeMultipliers || {})
const currentSectionsWithDivisor = computed(() => beltTypeConfig[selectedBeltType.value].sectionsWithDivisor)
const currentJsonFiles = computed(() => beltTypeConfig[selectedBeltType.value].jsonFiles)
const currentApiEndpoint = computed(() => beltTypeConfig[selectedBeltType.value].apiEndpoint)
const currentFormulaType = computed(() => beltTypeConfig[selectedBeltType.value].formulaType)
const currentDefaultDieConfigs = computed(() => allDieConfigurations.value[selectedBeltType.value] || {})

const activeSections = computed(() => {
  return Object.values(sectionCounts.value).filter(count => count > 0).length
})

const hasJsonFile = (section: string) => {
  return section in currentJsonFiles.value
}

// Check if section has divisor (now all sections have divisors)
const hasDivisor = (section: string) => {
  return true // All sections now have configurable divisors
}

// Get current formula text for display
const getCurrentFormulaText = (section: string) => {
  const multiplier = formulas.value[section] || currentDefaultFormulas.value[section] || 0
  const divisor = divisors.value[section] || currentDefaultDivisors.value[section] || (selectedBeltType.value === 'poly' ? 25.4 : 1)
  
  if (currentFormulaType.value === 'ribs_divide_multiply') {
    return `size÷${divisor}×${multiplier}`
  } else if (currentFormulaType.value === 'timing_belt_formula') {
    const typeMultiplier = typeMultipliers.value[section] || currentDefaultTypeMultipliers.value[section] || 450
    return `(size×type×${typeMultiplier}×${multiplier})+(size×total_mm×${multiplier})`
  } else {
    return `size÷${divisor}×${multiplier}`
  }
}

// Belt type change handler
const onBeltTypeChange = () => {
  // Reset formulas and divisors to defaults for new belt type
  formulas.value = { ...currentDefaultFormulas.value }
  divisors.value = { ...currentDefaultDivisors.value }
  typeMultipliers.value = { ...currentDefaultTypeMultipliers.value }
  // Reset die configurations for new belt type
  dieConfigurations.value = { ...allDieConfigurations.value[selectedBeltType.value] || {} }
  
  // If no die configurations loaded yet, populate with current values from database
  if (Object.keys(dieConfigurations.value).length === 0 && Object.keys(allDieConfigurations.value).length > 0) {
    dieConfigurations.value = { ...allDieConfigurations.value[selectedBeltType.value] || {} }
  }
  
  // Load statistics for new belt type
  updateCurrentFormulaFromLoaded()
  loadStatistics()
}

// Load statistics
const loadStatistics = async () => {
  try {
    // Get all products without pagination for statistics
    const response = await axios.get(`${currentApiEndpoint.value}?per_page=10000`)
    
    // Handle different response formats
    let products = []
    if (selectedBeltType.value === 'tpu' || selectedBeltType.value === 'timing' || selectedBeltType.value === 'special') {
      // TPU, Timing, and Special belts return simple array
      products = response.data
    } else {
      // Other belt types return paginated response
      products = response.data.data || []
    }
    
    // Reset counts
    sectionCounts.value = {}
    totalProducts.value = products.length
    
    // Calculate total value based on belt type
    if (selectedBeltType.value === 'poly') {
      // Poly belts use ribs × rate_per_rib
      totalValue.value = products.reduce((sum: number, p: any) => sum + Number(p.value), 0)
    } else if (selectedBeltType.value === 'timing') {
      // Timing belts use value
      totalValue.value = products.reduce((sum: number, p: any) => sum + Number(p.value || 0), 0)
    } else if (selectedBeltType.value === 'special') {
      // Special belts use value
      totalValue.value = products.reduce((sum: number, p: any) => sum + Number(p.value || 0), 0)
    } else {
      // Other belt types use standard value calculation
      totalValue.value = products.reduce((sum: number, p: any) => sum + Number(p.value), 0)
    }
    
    // Count by section
    products.forEach((product: any) => {
      sectionCounts.value[product.section] = (sectionCounts.value[product.section] || 0) + 1
    })
    
  } catch (err: any) {
    console.error('Statistics loading error:', err)
    showNotification('error', 'Error', `Failed to load statistics: ${err.response?.data?.message || err.message}`)
  }
}

const loadAllFormulas = async ()=>{
  try{

  const response = await axios.get('/api/rate-formulas/all')
  allFormulas.value = response.data

  console.log('reso[pes formrula s]', response, allFormulas)

  updateCurrentFormulaFromLoaded()

  }catch(e){
    console.log('error :', e);
  }
}


const updateCurrentFormulaFromLoaded = ()=>{

  const categoryMap = {
    'vee' : "vee_belts",
    "poly" :"poly_belts",
    "cogged":"cogged_belts",
    "tpu":"tpu_belts",
    "timing":"timing_belts",
    "special":"special_belts"
  }


  const category = categoryMap[selectedBeltType.value]
  const categoryFormulas = allFormulas.value[category] || {}


  // Reset to defaults first
  formulas.value = { ...currentDefaultFormulas.value }
  divisors.value = { ...currentDefaultDivisors.value }
  typeMultipliers.value = { ...currentDefaultTypeMultipliers.value }
  
  // Override with database values if they exist
  Object.keys(categoryFormulas).forEach(section => {
    const formula = categoryFormulas[section]
    if (formula.multiplier) formulas.value[section] = formula.multiplier
    if (formula.divisor) divisors.value[section] = formula.divisor
    if (formula.type_multiplier) typeMultipliers.value[section] = formula.type_multiplier
  })



}







// Update formula for specific section
const updateSectionFormula = async (section: string) => {
  if (!formulas.value[section] || formulas.value[section] <= 0) {
    showNotification('error', 'Invalid Formula', 'Formula multiplier must be greater than 0')
    return
  }
  
  // Validate divisor (now required for all sections)
  if (!divisors.value[section] || divisors.value[section] <= 0) {
    showNotification('error', 'Invalid Divisor', 'Divisor must be greater than 0')
    return
  }
  
  // Validate type multiplier for timing belts
  if (selectedBeltType.value === 'timing' && (!typeMultipliers.value[section] || typeMultipliers.value[section] <= 0)) {
    showNotification('error', 'Invalid Type Multiplier', 'Type multiplier must be greater than 0')
    return
  }
  
  loading.value = true
  try {
    let formulaString = ''
    const divisor = divisors.value[section] || currentDefaultDivisors.value[section] || (selectedBeltType.value === 'poly' ? 25.4 : 1)
    
    if (currentFormulaType.value === 'ribs_divide_multiply') {
      formulaString = `ribs/${divisor}*${formulas.value[section]}`
    } else if (currentFormulaType.value === 'timing_belt_formula') {
      // For timing belts, include the type multiplier in the formula
      const typeMultiplier = typeMultipliers.value[section] || currentDefaultTypeMultipliers.value[section] || 450
      formulaString = `size*type*${typeMultiplier}*${formulas.value[section]}+size*total_mm*${formulas.value[section]}`
    } else {
      formulaString = `size/${divisor}*${formulas.value[section]}`
    }
    
    // Update rate formula in database
    const response = await axios.post('/api/rate-formulas/update', {
      category: selectedBeltType.value === 'vee' ? 'vee_belts' : 
                selectedBeltType.value === 'cogged' ? 'cogged_belts' :
                selectedBeltType.value === 'poly' ? 'poly_belts' : 
                selectedBeltType.value === 'timing' ? 'timing_belts' :
                selectedBeltType.value === 'special' ? 'special_belts' : 'tpu_belts',
      section,
      formula: formulaString
    })
    
    showNotification('success', 'Formula Updated', `Updated formula for ${section} section`)
    
    // Recalculate rates for this section
    await recalculateSectionRates(section)
    
  } catch (err: any) {
    showNotification('error', 'Update Failed', err.response?.data?.message || 'Failed to update formula')
  } finally {
    await loadAllFormulas()
    loading.value = false
  }
}

// Update all formulas
const updateAllFormulas = async () => {
  loading.value = true
  try {
    for (const section of currentSections.value) {
      if (formulas.value[section] && formulas.value[section] > 0) {
        await updateSectionFormula(section)
      }
    }
    showNotification('success', 'All Formulas Updated', 'Updated formulas for all sections')
  } finally {
    loading.value = false
  }
}

// Reset to default formulas
const resetFormulasToDefaults = () => {
  formulas.value = { ...currentDefaultFormulas.value }
  divisors.value = { ...currentDefaultDivisors.value }
  showNotification('success', 'Reset Complete', 'Formulas and divisors reset to default values')
}

// Recalculate rates for specific section
const recalculateSectionRates = async (section: string) => {
  try {
    await axios.post(`${currentApiEndpoint.value}/recalculate-section-rates`, {
      section
    })
    await loadStatistics()
  } catch (err: any) {
    console.error('Recalculation error:', err)
  }
}

// Recalculate all rates based on current formulas
const recalculateAllRates = async () => {
  if (!confirm('Recalculate all rates based on current formulas? This will update all product rates.')) return
  
  loading.value = true
  try {
    await axios.post(`${currentApiEndpoint.value}/recalculate-all-rates`)
    showNotification('success', 'Rates Recalculated', 'All rates recalculated based on formulas')
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Recalculation Failed', err.response?.data?.message || 'Failed to recalculate rates')
  } finally {
    loading.value = false
  }
}

// Seed specific section
const seedSection = async (section: string) => {
  if (!hasJsonFile(section)) {
    showNotification('error', 'No JSON File', `No JSON file available for ${section} section`)
    return
  }
  
  loading.value = true
  try {
    // Call the backend to seed from JSON file
    const response = await axios.post(`${currentApiEndpoint.value}/seed-section`, {
      section,
      filename: currentJsonFiles.value[section]
    })
    
    showNotification('success', 'Seeding Complete', `Seeded ${section} section from JSON`)
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Seeding Failed', err.response?.data?.message || 'Failed to seed section')
  } finally {
    loading.value = false
  }
}

// Clear specific section
const clearSection = async (section: string) => {
  if (!confirm(`Are you sure you want to clear all ${section} products?`)) return
  
  loading.value = true
  try {
    await axios.delete(`${currentApiEndpoint.value}/clear-section/${section}`)
    showNotification('success', 'Section Cleared', `Cleared all ${section} products`)
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Clear Failed', err.response?.data?.message || 'Failed to clear section')
  } finally {
    loading.value = false
  }
}

// Seed all sections
const seedAllSections = async () => {
  if (!confirm('Seed all sections from JSON files?')) return
  
  loading.value = true
  try {
    for (const section of Object.keys(currentJsonFiles.value)) {
      await seedSection(section)
    }
    showNotification('success', 'All Sections Seeded', 'Seeded all available sections')
  } finally {
    loading.value = false
  }
}

// Clear all data
const clearAllData = async () => {
  const beltTypeName = beltTypeConfig[selectedBeltType.value].name
  if (!confirm(`Are you sure you want to clear ALL ${beltTypeName} data?`)) return
  
  loading.value = true
  try {
    await axios.delete(`${currentApiEndpoint.value}/clear-all`)
    showNotification('success', 'All Data Cleared', `Cleared all ${beltTypeName} data`)
    await loadStatistics()
    
  } catch (err: any) {
    showNotification('error', 'Clear Failed', err.response?.data?.message || 'Failed to clear data')
  } finally {
    loading.value = false
  }
}

// Export all data
const exportAllData = async () => {
  try {
    const response = await axios.get(`${currentApiEndpoint.value}?per_page=10000`)
    
    // Handle different response formats
    let data = []
    if (selectedBeltType.value === 'tpu' || selectedBeltType.value === 'timing' || selectedBeltType.value === 'special') {
      data = response.data
    } else {
      data = response.data.data || []
    }
    
    const beltTypeName = beltTypeConfig[selectedBeltType.value].name.toLowerCase().replace(' ', '-')
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${beltTypeName}-export-${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    
    showNotification('success', 'Export Complete', `Exported ${data.length} products`)
    
  } catch (err: any) {
    showNotification('error', 'Export Failed', 'Failed to export data')
  }
}

// Global minimum inventory functions
const updateGlobalMinInventory = async () => {
  if (globalMinInventory.value === null || globalMinInventory.value === undefined || globalMinInventory.value < 0) {
    showNotification('error', 'Invalid Value', 'Minimum inventory must be 0 or greater')
    return
  }

  if (!confirm(`Set minimum inventory level to ${globalMinInventory.value} for ALL belt types? This will update reorder levels for all products.`)) {
    return
  }

  loading.value = true
  try {
    // Update all belt types
    const beltTypes = ['vee', 'cogged', 'poly', 'tpu', 'timing', 'special']
    let totalUpdated = 0

    for (const beltType of beltTypes) {
      const endpoint = beltTypeConfig[beltType].apiEndpoint
      const response = await axios.post(`${endpoint}/update-global-min-inventory`, {
        min_inventory: globalMinInventory.value
      })
      totalUpdated += response.data.updated_count || 0
    }

    showNotification('success', 'Global Update Complete', `Updated minimum inventory for ${totalUpdated} products across all belt types`)
    await loadGlobalStats()
    
  } catch (err: any) {
    showNotification('error', 'Update Failed', err.response?.data?.message || 'Failed to update global minimum inventory')
  } finally {
    loading.value = false
  }
}

const updateSpecificMinInventory = async (beltType: string) => {
  const minValue = specificMinInventory.value[beltType]
  if (minValue === null || minValue === undefined || minValue < 0) {
    showNotification('error', 'Invalid Value', 'Minimum inventory must be 0 or greater')
    return
  }

  if (!confirm(`Set minimum inventory level to ${minValue} for ${beltTypeConfig[beltType].name}?`)) {
    return
  }

  loading.value = true
  try {
    const endpoint = beltTypeConfig[beltType].apiEndpoint
    const response = await axios.post(`${endpoint}/update-global-min-inventory`, {
      min_inventory: minValue
    })

    showNotification('success', 'Update Complete', `Updated minimum inventory for ${response.data.updated_count || 0} ${beltTypeConfig[beltType].name} products`)
    await loadGlobalStats()
    
  } catch (err: any) {
    showNotification('error', 'Update Failed', err.response?.data?.message || 'Failed to update minimum inventory')
  } finally {
    loading.value = false
  }
}

const loadGlobalStats = async () => {
  try {
    const beltTypes = ['vee', 'cogged', 'poly', 'tpu', 'timing', 'special']
    let totalProducts = 0
    let totalLowStock = 0
    let totalOutOfStock = 0
    let totalWellStocked = 0

    // Reset belt type stats
    beltTypeStats.value = {}

    for (const beltType of beltTypes) {
      try {
        const endpoint = beltTypeConfig[beltType].apiEndpoint
        const response = await axios.get(`${endpoint}?per_page=10000`)
        
        // Handle different response formats
        let products = []
        if (beltType === 'tpu' || beltType === 'timing' || beltType === 'special') {
          products = response.data || []
        } else {
          products = response.data.data || []
        }

        // Calculate stats for this belt type
        const outOfStock = products.filter((p: any) => {
          if (beltType === 'timing') {
            // Timing belts: use total_mm
            return (p.total_mm || 0) === 0
          } else if (beltType === 'special') {
            // Special belts: use balance_stock
            return (p.balance_stock || 0) === 0
          } else {
            // Other belt types: use balance_stock or meter
            return (p.balance_stock || p.meter || 0) === 0
          }
        }).length
        
        const lowStock = products.filter((p: any) => {
          let stock = 0
          if (beltType === 'timing') {
            stock = p.category === 'Commercial' ? (p.total_mm || 0) : (p.full_sleeve || 0)
          } else if (beltType === 'special') {
            stock = p.balance_stock || 0
          } else {
            stock = p.balance_stock || p.meter || 0
          }
          const reorderLevel = p.reorder_level || 5
          return stock > 0 && stock <= reorderLevel
        }).length
        const wellStocked = products.length - outOfStock - lowStock

        beltTypeStats.value[beltType] = {
          total: products.length,
          lowStock,
          outOfStock
        }

        totalProducts += products.length
        totalLowStock += lowStock
        totalOutOfStock += outOfStock
        totalWellStocked += wellStocked

      } catch (err) {
        console.error(`Error loading stats for ${beltType}:`, err)
        beltTypeStats.value[beltType] = { total: 0, lowStock: 0, outOfStock: 0 }
      }
    }

    globalStats.value = {
      totalProducts,
      lowStock: totalLowStock,
      outOfStock: totalOutOfStock,
      wellStocked: totalWellStocked
    }

  } catch (err: any) {
    console.error('Error loading global stats:', err)
    showNotification('error', 'Stats Error', 'Failed to load global statistics')
  }
}

// Die Configuration Methods
const loadDieConfigurations = async () => {
  try {
    const response = await axios.get('/api/die-configurations')
    if (response.data.success) {
      // Transform the API response to match our expected structure
      const transformedData: Record<string, Record<string, number>> = {}
      
      for (const [beltType, sections] of Object.entries(response.data.data)) {
        transformedData[beltType] = {}
        if (Array.isArray(sections)) {
          sections.forEach((config: any) => {
            transformedData[beltType][config.section] = parseFloat(config.stock_per_die)
          })
        }
      }
      
      allDieConfigurations.value = transformedData
      // Set current die configurations for selected belt type
      dieConfigurations.value = { ...allDieConfigurations.value[selectedBeltType.value] || {} }
      
      console.log('Die configurations loaded:', transformedData)
      console.log('Current belt type configurations:', dieConfigurations.value)
    }
  } catch (error: any) {
    console.error('Error loading die configurations:', error)
    showNotification('error', 'Error', 'Failed to load die configurations')
  }
}

const updateDieConfiguration = async (section: string) => {
  if (!dieConfigurations.value[section] || dieConfigurations.value[section] <= 0) {
    showNotification('error', 'Invalid Value', 'Stock per die must be greater than 0')
    return
  }

  loading.value = true
  try {
    const response = await axios.post('/api/die-configurations', {
      belt_type: selectedBeltType.value,
      section: section,
      stock_per_die: dieConfigurations.value[section],
      notes: `Updated via settings page on ${new Date().toLocaleString()}`
    })

    if (response.data.success) {
      // Update local data structures
      if (!allDieConfigurations.value[selectedBeltType.value]) {
        allDieConfigurations.value[selectedBeltType.value] = {}
      }
      allDieConfigurations.value[selectedBeltType.value][section] = dieConfigurations.value[section]
      
      showNotification('success', 'Success', `Die configuration for ${section} section updated successfully`)
    }
  } catch (error: any) {
    console.error('Error updating die configuration:', error)
    showNotification('error', 'Error', 'Failed to update die configuration')
  } finally {
    loading.value = false
  }
}

const seedDefaultDieConfigurations = async () => {
  if (!confirm('Reset all die configurations to default values? This will overwrite existing configurations.')) return
  
  loading.value = true
  try {
    const response = await axios.post('/api/die-configurations/seed-defaults')
    if (response.data.success) {
      await loadDieConfigurations()
      // Also update current die configurations for selected belt type
      dieConfigurations.value = { ...allDieConfigurations.value[selectedBeltType.value] || {} }
      showNotification('success', 'Success', 'Default die configurations seeded successfully')
    }
  } catch (error: any) {
    console.error('Error seeding default die configurations:', error)
    showNotification('error', 'Error', 'Failed to seed default die configurations')
  } finally {
    loading.value = false
  }
}

onMounted(async() => {
  // Initialize with default formulas and divisors for TPU belts
  formulas.value = { ...currentDefaultFormulas.value }
  divisors.value = { ...currentDefaultDivisors.value }
  typeMultipliers.value = { ...currentDefaultTypeMultipliers.value }
  loadStatistics()
  loadGlobalStats()
  await loadAllFormulas()
  await loadDieConfigurations()
})
</script>