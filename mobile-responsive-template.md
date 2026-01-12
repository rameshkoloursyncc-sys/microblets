# Mobile Responsive Belt Table Template - FINAL VERSION

## Key Requirements:
1. **Table view on mobile** (no cards)
2. **Compact header** (smaller spacing)
3. **Filters reach top** before becoming sticky
4. **Only filters are sticky**

## 1. Container Structure

```vue
<div class="p-3 sm:p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
  <!-- Header - Scrollable (More Compact) -->
  <div class="mb-2 sm:mb-4">
    <h1 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
      {{ title }}
    </h1>
    <p class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm">
      Description text
    </p>
  </div>

  <!-- Summary Stats - Scrollable (More Compact) -->
  <div class="mb-2 sm:mb-4 overflow-x-auto">
    <div class="flex gap-2 sm:gap-4 pb-2 min-w-max sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:min-w-0">
      <!-- Compact stats cards -->
    </div>
  </div>

  <!-- Filters - STICKY (Only this section) -->
  <div class="sticky top-14 z-30 bg-gray-50 dark:bg-gray-900 pb-2 sm:pb-4">
    <div class="mb-2 sm:mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-md p-2 sm:p-3">
      <!-- Compact filter content -->
    </div>
  </div>

  <!-- Table - Single view for all devices -->
</div>
```

## 2. Compact Stats Section

```vue
<!-- Summary Stats (Scrollable on Mobile, More Compact) -->
<div class="mb-2 sm:mb-4 overflow-x-auto">
  <div class="flex gap-2 sm:gap-4 pb-2 min-w-max sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:min-w-0">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-2 sm:p-4 min-w-[140px] sm:min-w-0">
      <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Total Products</div>
      <div class="text-sm sm:text-xl font-bold text-gray-900 dark:text-white">{{ visibleProducts.length }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-2 sm:p-4 min-w-[140px] sm:min-w-0">
      <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Total Stock/Value</div>
      <div class="text-sm sm:text-xl font-bold text-blue-600 dark:text-blue-400">{{ totalStock || totalValue }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-2 sm:p-4 min-w-[140px] sm:min-w-0">
      <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Total Value</div>
      <div class="text-sm sm:text-xl font-bold text-green-600 dark:text-green-400">₹{{ Number(totalValue || 0).toFixed(2) }}</div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-2 sm:p-4 min-w-[140px] sm:min-w-0">
      <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Out of Stock</div>
      <div class="text-sm sm:text-xl font-bold text-red-600 dark:text-red-400">{{ outOfStockCount || lowStockCount }}</div>
    </div>
  </div>
</div>
```

## 3. Compact Sticky Filters

```vue
<!-- Filters - STICKY (Only This Section, More Compact) -->
<div class="sticky top-14 z-30 bg-gray-50 dark:bg-gray-900 pb-2 sm:pb-4">
  <div class="mb-2 sm:mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-md p-2 sm:p-3">
    <div class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-1 sm:gap-2">
      <!-- Search -->
      <input 
        v-model="searchTerm" 
        placeholder="Search section / size" 
        class="w-full sm:w-auto px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm border rounded bg-white dark:bg-gray-700 dark:text-white"
      />
      
      <!-- Action Buttons -->
      <div class="w-full sm:w-auto sm:ml-auto flex items-center gap-1 sm:gap-2">
        <button @click="showCreateModal = true" class="w-full sm:w-auto px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
          Create Product
        </button>
      </div>
    </div>
  </div>
</div>
```

## 4. Single Table View (Mobile + Desktop)

```vue
<!-- Table - Single View for All Devices -->
<div class="flex-1 bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
  <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-300px)]">
    <table class="w-full text-xs sm:text-sm text-left text-gray-600 dark:text-gray-300">
      <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase sticky top-0 z-20">
        <tr>
          <th class="py-2 sm:py-3 px-1 sm:px-3">Section</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3">Size</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 text-center">Stock</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 text-center">IN</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 text-center">OUT</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 text-center hidden sm:table-cell">Min Inv</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 text-right">Rate</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 text-right">Value</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 hidden sm:table-cell">Remark</th>
          <th class="py-2 sm:py-3 px-1 sm:px-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in visibleProducts" :key="p.id" class="border-t hover:bg-gray-50 dark:hover:bg-gray-700">
          <td class="py-1 sm:py-2 px-1 sm:px-3">
            <div v-if="editingCell === `${p.id}-section`">
              <input v-model="editValue" @keyup.enter="saveCell(p, 'section')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded text-xs" />
            </div>
            <div v-else @click="startEdit(p, 'section')" class="cursor-pointer font-bold text-black dark:text-white text-xs sm:text-sm">{{ p.section }}</div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3">
            <div v-if="editingCell === `${p.id}-size`">
              <input v-model="editValue" @keyup.enter="saveCell(p, 'size')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded text-xs" />
            </div>
            <div v-else @click="startEdit(p, 'size')" class="cursor-pointer font-bold text-black dark:text-white text-xs sm:text-sm">{{ p.size }}</div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 text-center">
            <div v-if="editingCell === `${p.id}-balance_stock`">
              <input v-model.number="editValue" type="number" @keyup.enter="saveCell(p, 'balance_stock')" @keyup.esc="cancelEdit" class="w-16 sm:w-20 p-1 border rounded text-center text-xs" />
            </div>
            <div v-else @click="startEdit(p, 'balance_stock')" class="cursor-pointer">
              <span class="font-bold text-xs sm:text-sm" :class="getStockClass(p)">{{ p.balance_stock }}</span>
            </div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 text-center">
            <div v-if="editingCell === `${p.id}-in_qty`">
              <input 
                v-model="editValue" 
                type="number" 
                min="0"
                @keyup.enter="performInOut(p, 'IN')" 
                @keyup.esc="cancelEdit" 
                class="w-12 sm:w-16 p-1 border rounded text-center bg-green-50 text-xs" 
                placeholder="IN"
              />
            </div>
            <div v-else @click="startEdit(p, 'in_qty')" class="cursor-pointer hover:bg-green-50 px-1 py-1 rounded">
              <span class="text-green-600 font-medium text-xs">+</span>
            </div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 text-center">
            <div v-if="editingCell === `${p.id}-out_qty`">
              <input 
                v-model="editValue" 
                type="number" 
                min="0"
                @keyup.enter="performInOut(p, 'OUT')" 
                @keyup.esc="cancelEdit" 
                class="w-12 sm:w-16 p-1 border rounded text-center bg-red-50 text-xs" 
                placeholder="OUT"
              />
            </div>
            <div v-else @click="startEdit(p, 'out_qty')" class="cursor-pointer hover:bg-red-50 px-1 py-1 rounded">
              <span class="text-red-600 font-medium text-xs">-</span>
            </div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 text-center hidden sm:table-cell">
            <div v-if="editingCell === `${p.id}-reorder_level`">
              <input v-model.number="editValue" type="number" min="0" 
                     @keyup.enter="saveCell(p, 'reorder_level')" 
                     @keyup.esc="cancelEdit" 
                     class="w-16 sm:w-20 p-1 border rounded text-center text-xs" />
            </div>
            <div v-else @click="startEdit(p, 'reorder_level')" class="cursor-pointer text-xs">{{ p.reorder_level ?? 'N/A' }}</div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 text-right">
            <div v-if="editingCell === `${p.id}-rate`">
              <input v-model.number="editValue" type="number" step="0.01" @keyup.enter="saveCell(p, 'rate')" @keyup.esc="cancelEdit" class="w-16 sm:w-24 p-1 border rounded text-right text-xs" />
            </div>
            <div v-else @click="startEdit(p, 'rate')" class="cursor-pointer text-xs">₹{{ Number(p.rate).toFixed(2) }}</div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 text-right text-xs font-medium text-green-600">₹{{ Number(p.value).toFixed(2) }}</td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 hidden sm:table-cell">
            <div v-if="editingCell === `${p.id}-remark`">
              <input v-model="editValue" @keyup.enter="saveCell(p, 'remark')" @keyup.esc="cancelEdit" class="w-full p-1 border rounded text-xs" />
            </div>
            <div v-else @click="startEdit(p, 'remark')" class="cursor-pointer text-xs">{{ p.remark || '-' }}</div>
          </td>

          <td class="py-1 sm:py-2 px-1 sm:px-3 text-center">
            <div class="flex items-center justify-center gap-1">
              <button @click="onDelete(p.id)" class="text-red-600 px-1 hover:text-red-800 text-xs">Del</button>
              <button @click="showHistory(p)" class="text-blue-600 px-1 hover:text-blue-800 text-xs">Hist</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
```

## 5. Step-by-Step Manual Application

### For Each Belt Table File:

1. **Update Container Padding** (already done):
   ```vue
   <div class="p-3 sm:p-6 mt-14...">
   ```

2. **Make Header More Compact**:
   ```vue
   <!-- Change from: -->
   <div class="mb-4 sm:mb-6">
     <h1 class="text-xl sm:text-2xl...">
   <!-- To: -->
   <div class="mb-2 sm:mb-4">
     <h1 class="text-lg sm:text-xl...">
     <p class="text-xs sm:text-sm...">
   ```

3. **Make Stats More Compact**:
   ```vue
   <!-- Change from: -->
   <div class="mb-4 overflow-x-auto">
     <div class="flex gap-4 pb-2...">
       <div class="...p-4 min-w-[180px]...">
         <div class="text-sm...">
         <div class="text-xl sm:text-2xl...">
   <!-- To: -->
   <div class="mb-2 sm:mb-4 overflow-x-auto">
     <div class="flex gap-2 sm:gap-4 pb-2...">
       <div class="...p-2 sm:p-4 min-w-[140px]...">
         <div class="text-xs sm:text-sm...">
         <div class="text-sm sm:text-xl...">
   ```

4. **Make Filters More Compact**:
   ```vue
   <!-- Change from: -->
   <div class="sticky top-14 z-30 bg-gray-50 dark:bg-gray-900 pb-4">
     <div class="mb-4 bg-white...p-3">
       <div class="...gap-2">
         <input class="...px-3 py-1.5 text-sm...">
   <!-- To: -->
   <div class="sticky top-14 z-30 bg-gray-50 dark:bg-gray-900 pb-2 sm:pb-4">
     <div class="mb-2 sm:mb-4 bg-white...p-2 sm:p-3">
       <div class="...gap-1 sm:gap-2">
         <input class="...px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm...">
   ```

5. **Remove Card View, Use Single Table**:
   ```vue
   <!-- Remove: -->
   <div class="block md:hidden">...</div>
   <div class="hidden md:block">...</div>
   
   <!-- Replace with: -->
   <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-300px)]">
     <table class="w-full text-xs sm:text-sm...">
   ```

6. **Make Table Mobile-Friendly**:
   ```vue
   <!-- Update table cells: -->
   <th class="py-2 sm:py-3 px-1 sm:px-3">
   <td class="py-1 sm:py-2 px-1 sm:px-3">
   
   <!-- Hide some columns on mobile: -->
   <th class="...hidden sm:table-cell">
   <td class="...hidden sm:table-cell">
   
   <!-- Make inputs smaller: -->
   <input class="w-16 sm:w-20 p-1 border rounded text-center text-xs">
   
   <!-- Compact buttons: -->
   <button class="text-red-600 px-1 hover:text-red-800 text-xs">Del</button>
   ```

## Files to Update:

- ✅ VeeBeltTable.vue (DONE)
- ✅ TimingBeltTable.vue (DONE)  
- 🔄 TpuBeltTable.vue (Needs full update)
- ❌ CoggedBeltTable.vue
- ❌ PolyBeltTable.vue
- ❌ SpecialBeltTable.vue

## Key Features:

1. **Compact Design**: Smaller spacing, text sizes, padding
2. **Table on Mobile**: No cards, single table view for all devices
3. **Sticky Filters Only**: Header and stats scroll away, filters stick
4. **Touch-Friendly**: Smaller but still tappable buttons
5. **Hidden Columns**: Some columns hidden on mobile to save space
6. **Horizontal Scroll**: Table scrolls horizontally on small screens

## Testing:
1. Header and stats should scroll away quickly (compact spacing)
2. Filters should stick to top immediately when scrolling
3. Table should be usable on mobile with horizontal scroll
4. All interactive elements should work on touch devices