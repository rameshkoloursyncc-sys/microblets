<script setup lang="ts">
import { ref } from 'vue'

const sidebarCollapsed = ref(false)
const sectionSearch = ref('')
const sizeSearch = ref('')
const expandedInventory = ref(false)
const expandedFinishedInventory = ref(false)
const expandedVeeBelts = ref(false)
const expandedCoggedBelts = ref(false)
const expandedPolyBelts = ref(false)
const expandedTimingBelts = ref(false)
const expandedTimingBeltsCommercial = ref(false)
const expandedTimingBeltsNeoprene = ref(false)
const expandedTPUBelts = ref(false)
const expandedSpecialBelts = ref(false)
const expandedRawMaterials = ref(false)
const expandedRawVeeBelts = ref(false)
const expandedRawCoggedBelts = ref(false)
const expandedRawPolyBelts = ref(false)
const expandedRawTimingBelts = ref(false)
const expandedRawTPUBelts = ref(false)
const expandedRawSpecialBelts = ref(false)
const expandedRawMaterialCategories = ref(false)

const emit = defineEmits(['navigate', 'sidebar-toggle', 'search'])

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
  emit('sidebar-toggle', sidebarCollapsed.value)
}

const performCombinedSearch = () => {
  // Send both section and size for combined exact matching
  emit('search', { 
    type: 'combined', 
    sectionQuery: sectionSearch.value.trim(),
    sizeQuery: sizeSearch.value.trim()
  })
}

const performSectionSearch = () => {
  performCombinedSearch()
}

const performSizeSearch = () => {
  performCombinedSearch()
}
</script>

<template>
<nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
  <div class="px-3 py-3 lg:px-5 lg:pl-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center justify-start rtl:justify-end">
        <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar" type="button" class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
            <span class="sr-only">Open sidebar</span>
            <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
               <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
            </svg>
         </button>
        <a href="#" class="flex ms-2 md:me-24">
          <svg class="h-8 me-3 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2L2 7v10c0 5.55 3.84 10 9 11 1.16.21 2.76.21 3.91 0C20.16 27 24 22.55 24 17V7l-10-5z"/>
          </svg>
          <span class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap dark:text-white">Microbelts IMA</span>
        </a>
      </div>
      
      <!-- Universal Search Bars -->
      <div class="flex items-center gap-4">
        <!-- Section Search -->
        <form class="max-w-md">
          <label for="section-search" class="block mb-2.5 text-sm font-medium text-gray-900 sr-only dark:text-white">Search Section</label>
          <div class="relative">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
              <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
              </svg>
            </div>
            <input type="search" id="section-search" v-model="sectionSearch" @keyup.enter="performSectionSearch" class="block w-full p-2 ps-10 pe-16 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm placeholder:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" placeholder="Search Section" />
            <button type="button" @click="performSectionSearch" class="absolute end-2 top-1/2 transform -translate-y-1/2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent focus:ring-4 focus:ring-blue-300 shadow-sm font-medium rounded text-xs px-2 py-1 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-700">Search</button>
          </div>
        </form>

        <!-- Size Search -->
        <form class="max-w-md">
          <label for="size-search" class="block mb-2.5 text-sm font-medium text-gray-900 sr-only dark:text-white">Search Size</label>
          <div class="relative">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
              <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
              </svg>
            </div>
            <input type="search" id="size-search" v-model="sizeSearch" @keyup.enter="performSizeSearch" class="block w-full p-2 ps-10 pe-16 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm placeholder:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" placeholder="Search Size" />
            <button type="button" @click="performSizeSearch" class="absolute end-2 top-1/2 transform -translate-y-1/2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent focus:ring-4 focus:ring-blue-300 shadow-sm font-medium rounded text-xs px-2 py-1 focus:outline-none dark:bg-blue-600 dark:hover:bg-blue-700">Search</button>
          </div>
        </form>
      </div>
      
      <div class="flex items-center">
          <div class="flex items-center ms-3">
            <div>
              <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                <span class="sr-only">Open user menu</span>
                <img class="w-8 h-8 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="user photo">
              </button>
            </div>
            <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-sm shadow-sm dark:bg-gray-700 dark:divide-gray-600" id="dropdown-user">
              <div class="px-4 py-3" role="none">
                <p class="text-sm text-gray-900 dark:text-white" role="none">
                  Neil Sims
                </p>
                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-300" role="none">
                  neil.sims@flowbite.com
                </p>
              </div>
              <ul class="py-1" role="none">
                <li>
                  <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Dashboard</a>
                </li>
                <li>
                  <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Settings</a>
                </li>
                <li>
                  <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Earnings</a>
                </li>
                <li>
                  <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Sign out</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
    </div>
  </div>
</nav>

<aside id="logo-sidebar" class="fixed top-0 left-0 z-40 h-screen pt-20 transition-all duration-300 -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700" :class="sidebarCollapsed ? 'w-16' : 'w-80'" aria-label="Sidebar">
  <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800 relative">
    <!-- Sidebar Toggle Button -->
    <button
      @click="toggleSidebar"
      class="absolute -right-3 top-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full p-1.5 shadow-md hover:shadow-lg transition-shadow z-50"
      :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
    >
      <svg
        class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-300"
        :class="sidebarCollapsed ? 'rotate-180' : ''"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>
    <ul class="space-y-2 font-medium">
         <li>
            <a href="#" @click="$emit('navigate', 'dashboard')" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group" :title="sidebarCollapsed ? 'Dashboard' : ''">
               <svg class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                  <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/>
                  <path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/>
               </svg>
               <span v-if="!sidebarCollapsed" class="ms-3">Dashboard</span>
            </a>
         </li>
      <li>
        <button @click="$emit('navigate', 'create-product')" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group w-full" :title="sidebarCollapsed ? 'Add Product' : ''">
          <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
          <span v-if="!sidebarCollapsed" class="ms-3">Add Product</span>
        </button>
      </li>
      
      <li>
        <button @click="$emit('navigate', 'settings')" class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group w-full" :title="sidebarCollapsed ? 'Settings' : ''">
          <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
          <span v-if="!sidebarCollapsed" class="ms-3">Settings</span>
        </button>
      </li>

      <!-- Inventory hierarchy -->
      <li>
        <!-- Level 1: Inventory -->
        <button
          type="button"
          class="flex items-center w-full p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
          @click="expandedInventory = !expandedInventory"
          :title="sidebarCollapsed ? 'Inventory' : ''"
        >
          <span class="flex items-center">
            <svg class="shrink-0 w-5 h-5 text-blue-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
              <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span v-if="!sidebarCollapsed" class="ms-3">Inventory</span>
          </span>
          <svg
            v-if="!sidebarCollapsed"
            class="w-4 h-4 text-gray-500 transition-transform"
            :class="expandedInventory ? 'rotate-90' : ''"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <ul
          v-if="expandedInventory"
          class="mt-1 ml-3 space-y-1"
        >
          <!-- Level 2: Inventory (finished goods) -->
          <li>
            <button
              type="button"
              class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between ps-4"
              @click="expandedFinishedInventory = !expandedFinishedInventory"
            >
              <span>Finished Goods</span>
              <svg
                class="w-3 h-3 text-gray-500 transition-transform"
                :class="expandedFinishedInventory ? 'rotate-90' : ''"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <ul
              v-if="expandedFinishedInventory"
              class="mt-1 ml-4 space-y-1 ps-4"
            >
              <!-- Level 3: Vee Belts -->
              <li>
                <button
                  type="button"
                  class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                  @click="expandedVeeBelts = !expandedVeeBelts"
                >
                  <span>Vee Belts</span>
                  <svg
                    class="w-3 h-3 text-gray-500 transition-transform"
                    :class="expandedVeeBelts ? 'rotate-90' : ''"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                <ul
                  v-if="expandedVeeBelts"
                  class="mt-1 ml-4 space-y-1 ps-4"
                >
                  <!-- Classical Section: A, B, C, D, E -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-a-page')"
                    >
                      <span>A</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-b-page')"
                    >
                      <span>B</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-c-page')"
                    >
                      
                      <span>C</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-d-page')"
                    >
                      
                      <span>D</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-e-page')"
                    >
                      
                      <span>E</span>
                    </button>
                  </li>
                  <!-- Wedge Section: SPA, SPB, SPC, SPZ -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-spa-page')"
                    >
                      
                      <span>SPA</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-spb-page')"
                    >
                      
                      <span>SPB</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-spc-page')"
                    >
                      
                      <span>SPC</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-spz-page')"
                    >
                      
                      <span>SPZ</span>
                    </button>
                  </li>
                  <!-- Narrow Section: 3V, 5V, 8V -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-3v-page')"
                    >
                      
                      <span>3V</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-5v-page')"
                    >
                      
                      <span>5V</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-8v-page')"
                    >
                      
                      <span>8V</span>
                    </button>
                  </li>

                </ul>
              </li>

              <!-- Level 3: Cogged Belts -->
              <li>
                <button
                  type="button"
                  class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                  @click="expandedCoggedBelts = !expandedCoggedBelts"
                >
                  <span>Cogged Belts</span>
                  <svg
                    class="w-3 h-3 text-gray-500 transition-transform"
                    :class="expandedCoggedBelts ? 'rotate-90' : ''"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                <ul
                  v-if="expandedCoggedBelts"
                  class="mt-1 ml-4 space-y-1 ps-4"
                >
                  <!-- Cogged Classical: AX, BX, CX -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-ax')"
                    >
                      
                      <span>AX</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-bx')"
                    >
                      
                      <span>BX</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-cx')"
                    >
                      
                      <span>CX</span>
                    </button>
                  </li>
                  <!-- Cogged Wedge: XPA, XPB, XPC, XPZ -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-xpa')"
                    >
                      
                      <span>XPA</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-xpb')"
                    >
                      
                      <span>XPB</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-xpc')"
                    >
                      
                      <span>XPC</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-xpz')"
                    >
                      
                      <span>XPZ</span>
                    </button>
                  </li>
                  <!-- Cogged Narrow: 3VX, 5VX, -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-3vx')"
                    >
                      
                      <span>3VX</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'cogged-belts-5vx')"
                    >
                      
                      <span>5VX</span>
                    </button>
                  </li>
                </ul>
              </li>

              <!-- Level 3: Poly Belts -->
              <li>
                <button
                  type="button"
                  class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                  @click="expandedPolyBelts = !expandedPolyBelts"
                >
                  <span>Poly Belts</span>
                  <svg
                    class="w-3 h-3 text-gray-500 transition-transform"
                    :class="expandedPolyBelts ? 'rotate-90' : ''"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                <ul
                  v-if="expandedPolyBelts"
                  class="mt-1 ml-4 space-y-1 ps-4"
                >
                  <!-- Poly V-Belts: PJ, PK, PL, PM, PH -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'poly-belts-pj')"
                    >
                      
                      <span>PJ</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'poly-belts-pk')"
                    >
                      
                      <span>PK</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'poly-belts-pl')"
                    >
                      
                      <span>PL</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'poly-belts-pm')"
                    >
                      
                      <span>PM</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'poly-belts-ph')"
                    >
                      
                      <span>PH</span>
                    </button>
                  </li>
                  <!-- Poly V-Belt Double Sided: DPL, DPK -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'poly-belts-dpl')"
                    >
                      
                      <span>DPL</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'poly-belts-dpk')"
                    >
                      
                      <span>DPK</span>
                    </button>
                  </li>
                </ul>
              </li>

              <!-- Level 3: Timing Belts -->
              <li>
                <button
                  type="button"
                  class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                  @click="expandedTimingBelts = !expandedTimingBelts"
                >
                  <span>Timing Belts</span>
                  <svg
                    class="w-3 h-3 text-gray-500 transition-transform"
                    :class="expandedTimingBelts ? 'rotate-90' : ''"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                <ul
                  v-if="expandedTimingBelts"
                  class="mt-1 ml-4 space-y-1 ps-4"
                >
                  <!-- Commercial Folder -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center w-full p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                      @click="expandedTimingCommercial = !expandedTimingCommercial"
                    >
                      <span class="flex items-center gap-2">
                        
                        <span>Commercial</span>
                      </span>
                      <svg
                        class="w-3 h-3 text-gray-500 transition-transform"
                        :class="expandedTimingCommercial ? 'rotate-90' : ''"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    <ul
                      v-if="expandedTimingCommercial"
                      class="mt-1 ml-4 space-y-1 ps-4"
                    >
                      <!-- Classical Timing Belts: XL, L, H, XH, T5, T10 -->
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-xl')"
                        >
                          
                          <span>XL</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-l')"
                        >
                          
                          <span>L</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-h')"
                        >
                          
                          <span>H</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-xh')"
                        >
                          
                          <span>XH</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-t5')"
                        >
                          
                          <span>T5</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-t10')"
                        >
                          
                          <span>T10</span>
                        </button>
                      </li>
                      <!-- HTD Timing Belts: 5M, 8M, 14M -->
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-5m')"
                        >
                          
                          <span>5M</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-8m')"
                        >
                          
                          <span>8M</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-14m')"
                        >
                          
                          <span>14M</span>
                        </button>
                      </li>
                      <!-- Double-Side Timing Belts: DL, DH, D5M, D8M -->
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-dl')"
                        >
                          
                          <span>DL</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-dh')"
                        >
                          
                          <span>DH</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-d5m')"
                        >
                          
                          <span>D5M</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-d8m')"
                        >
                          
                          <span>D8M</span>
                        </button>
                      </li>
                       <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-3m')"
                        >
                          
                          <span>3M</span>
                        </button>
                      </li>
                    </ul>
                  </li>
                  <!-- Neoprene Folder -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center w-full p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                      @click="expandedTimingNeoprene = !expandedTimingNeoprene"
                    >
                      <span class="flex items-center gap-2">
                        
                        <span>Neoprene</span>
                      </span>
                      <svg
                        class="w-3 h-3 text-gray-500 transition-transform"
                        :class="expandedTimingNeoprene ? 'rotate-90' : ''"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    <ul
                      v-if="expandedTimingNeoprene"
                      class="mt-1 ml-4 space-y-1 ps-4"
                    >
                      <!-- Classical Timing Belts: XL, L, H, XH, T5, T10 -->
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-xl')"
                        >
                          
                          <span>XL</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-l')"
                        >
                          
                          <span>L</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-h')"
                        >
                          
                          <span>H</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-xh')"
                        >
                          
                          <span>XH</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-t5')"
                        >
                          
                          <span>T5</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-t10')"
                        >
                          
                          <span>T10</span>
                        </button>
                      </li>
                      <!-- HTD Timing Belts: 5M, 8M, 14M -->
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-5m')"
                        >
                          
                          <span>5M</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-8m')"
                        >
                          
                          <span>8M</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-14m')"
                        >
                          
                          <span>14M</span>
                        </button>
                      </li>
                      <!-- Double-Side Timing Belts: DL, DH, D5M, D8M -->
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-dl')"
                        >
                          
                          <span>DL</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-dh')"
                        >
                          
                          <span>DH</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-d5m')"
                        >
                          
                          <span>D5M</span>
                        </button>
                      </li>
                      <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-neoprene-d8m')"
                        >
                          
                          <span>D8M</span>
                        </button>
                      </li>
                       <li>
                        <button
                          type="button"
                          class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                          @click="$emit('navigate', 'timing-belts-commercial-3m')"
                        >
                          
                          <span>3M</span>
                        </button>
                      </li>
                    </ul>
                  </li>
                </ul>
              </li>

              <!-- Level 3: TPU Belts -->
              <li>
                <button
                  type="button"
                  class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                  @click="expandedTPUBelts = !expandedTPUBelts"
                >
                  <span>TPU Belts</span>
                  <svg
                    class="w-3 h-3 text-gray-500 transition-transform"
                    :class="expandedTPUBelts ? 'rotate-90' : ''"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                <ul
                  v-if="expandedTPUBelts"
                  class="mt-1 ml-4 space-y-1 ps-4"
                >
                  <!-- HTD TPU Belts: 5M, 8M, 8M RPP, S8M, 14M -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-t5m-page')"
                    >
                      
                      <span>5M</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-t8m-page')"
                    >
                      
                      <span>8M</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-t8m-RPP-page')"
                    >
                      
                      <span>8M RPP</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-ts8m-page')"
                    >
                      
                      <span>S8M</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-t14m-page')"
                    >
                      
                      <span>14M</span>
                    </button>
                  </li>
                  <!-- Classical TPU Belts: XL, L, H -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-txl-page')"
                    >
                      
                      <span>XL</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-tlm-page')"
                    >
                      
                      <span>L</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-thm-page')"
                    >
                      
                      <span>H</span>
                    </button>
                  </li>
                  <!-- AT Series TPU Belts: AT5, AT10, AT20 -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-at5m-page')"
                    >
                      
                      <span>AT5</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-at10m-page')"
                    >
                      
                      <span>AT10</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-t10M-page')"
                    >
                      
                      <span>T10</span>
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'tpu-belts-at20m-page')"
                    >
                      
                      <span>AT20</span>
                    </button>
                  </li>
                </ul>
              </li>

              <!-- Level 3: Special Belt and Coating Belts -->
              <li>
                <button
                  type="button"
                  class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between"
                  @click="expandedSpecialBelts = !expandedSpecialBelts"
                >
                  <span>Special Belt & Coating Belts</span>
                  <svg
                    v-if="!sidebarCollapsed"
                    class="w-3 h-3 text-gray-500 transition-transform"
                    :class="expandedSpecialBelts ? 'rotate-90' : ''"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                <ul
                  v-if="expandedSpecialBelts && !sidebarCollapsed"
                  class="mt-1 ml-4 space-y-1 ps-4"
                >
                  <!-- Level 4: Vee Belts Special -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'vee-belts-special')"
                    >
                      
                      <span>Vee Belts Special</span>
                    </button>
                  </li>

                  <!-- Level 4: Banded Cogged Belts -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'banded-cogged-belts')"
                    >
                      
                      <span>Banded Cogged Belts</span>
                    </button>
                  </li>

                  <!-- Level 4: Hybrid Belts -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'hybrid-belts')"
                    >
                      
                      <span>Hybrid Belts</span>
                    </button>
                  </li>

                  <!-- Level 4: Coating Belts -->
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'coating-belts')"
                    >
                      
                      <span>Coating Belts</span>
                    </button>
                  </li>
                </ul>
              </li>
            </ul>
          </li>

          <!-- Level 2: Raw Material -->
          <li>
            <button
              type="button"
              class="flex items-center w-full p-2 text-sm text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group justify-between ps-4"
              @click="expandedRawMaterial = !expandedRawMaterial"
            >
              <span>Raw Material</span>
              <svg
                class="w-3 h-3 text-gray-500 transition-transform"
                :class="expandedRawMaterial ? 'rotate-90' : ''"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <ul
              v-if="expandedRawMaterial"
              class="mt-1 ml-4 space-y-1 ps-4"
            >
              <!-- Raw Material Categories -->
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-carbon')"
                >
                  
                  <span>Carbon</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-chemical')"
                >
                  
                  <span>Chemical</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-cord')"
                >
                  
                  <span>Soft/Stiff Cord</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-fabric')"
                >
                  
                  <span>Fabric</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-oil')"
                >
                  
                  <span>Oil</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-others')"
                >
                  
                  <span>Others</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-resin')"
                >
                  
                  <span>Resin</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-tpu')"
                >
                  
                  <span>TPU</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-fibre-glass-cord')"
                >
                  
                  <span>Fibre Glass Cord</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-steel-wire')"
                >
                  
                  <span>Steel Wire</span>
                </button>
              </li>
              <li>
                <button
                  type="button"
                  class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="$emit('navigate', 'raw-material-packing')"
                >
                  
                  <span>Packing Material</span>
                </button>
              </li>
                  <li>
                    <button
                      type="button"
                      class="flex items-center gap-2 block w-full text-left p-2 text-xs text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
                      @click="$emit('navigate', 'raw-tpu-belts-open')"
                    >
                      
                      <span>Open</span>
                    </button>
                  </li>
                </ul>
              </li>
            </ul>
          </li>
        </ul>
   </div>
</aside>
</template>