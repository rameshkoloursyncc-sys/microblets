<template>
  <div>
    <div class="p-6 mt-14 min-h-screen bg-gray-50 dark:bg-gray-900">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Create Product</h1>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <form @submit.prevent="saveNewProduct" class="grid gap-4 grid-cols-1 md:grid-cols-2">
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">Product Name</label>
            <input v-model="form.name" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
          </div>
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">SKU</label>
            <input v-model="form.sku" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
          </div>
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">Dimension</label>
            <input v-model="form.dimension" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">Stock</label>
            <input v-model.number="form.stock" type="number" min="0" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">Reorder Level</label>
            <input v-model.number="form.reorder_level" type="number" min="0" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">Rate ($)</label>
            <input v-model.number="form.rate" type="number" step="0.01" min="0" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">Value ($)</label>
            <input v-model.number="form.value" type="number" step="0.01" min="0" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div>
            <label class="block mb-1 text-sm text-gray-700 dark:text-gray-200">Items per Sleeve</label>
            <input v-model.number="form.items_per_sleve" type="number" min="1" class="w-full p-2 border rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div class="md:col-span-2 text-right">
            <button type="submit" :disabled="!form.name || !form.sku" class="px-4 py-2 bg-blue-600 text-white rounded">Create Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const form = ref({
  name: '',
  sku: '',
  dimension: '',
  stock: 0,
  reorder_level: 0,
  rate: 0,
  value: 0,
  items_per_sleve: null as number | null
})

const saveNewProduct = async () => {
  try {
    const res = await fetch('/api/products', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(form.value)
    })
    if (res.ok) {
      // redirect back to inventory or notify
      alert('Product created')
      // reset form
      form.value = { name: '', sku: '', dimension: '', stock: 0, reorder_level: 0, rate: 0, value: 0, items_per_sleve: null }
    } else {
      const data = await res.json().catch(() => ({}))
      console.error('Failed create', data)
      alert('Failed to create product')
    }
  } catch (e) {
    console.error(e)
    alert('Network error')
  }
}
</script>

<style scoped>
/* minimal spacing adjustments */
.sm\:ml-64 { margin-left: 16rem; }
</style>
