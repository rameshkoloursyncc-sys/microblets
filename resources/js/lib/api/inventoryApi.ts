import spaProductsSeed from '../../mock/spaProducts.json'

// Shape must stay in sync with the Product interface in FlowbiteTable.vue
export interface InventoryProduct {
  id: number
  name: string
  category: string
  sku: string
  section?: string
  size: string
  stock: number
  reorder_level: number
  rate: number
  value: number
  in_qty?: number | null
  out_qty?: number | null
  items_per_sleve?: number
  remark?: string
}

/**
 * Mocked API call for fetching inventory products.
 * Later you can replace this with a real `fetch('/api/inventory')` call
 * without changing the UI components.
 */
export async function fetchInventoryProducts (): Promise<InventoryProduct[]> {
  // Simulate network latency
  await new Promise(resolve => setTimeout(resolve, 300))

  // In real backend version, this would be a fetch/axios call.
  // For now, we return the JSON mock as if it came from the server.
  return spaProductsSeed as InventoryProduct[]
}


