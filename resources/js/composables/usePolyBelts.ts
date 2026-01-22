import { ref, computed } from 'vue'
import axios from '../lib/axios'


export interface StockAlert {
    id: number
  belt_type: string
  section: string
  product_id: number
  product_sku: string
  current_stock: number
  reorder_level: number
  stock_per_die: number
  dies_needed: number
  alert_sent: boolean
  alert_sent_at: string | null
  is_active: boolean
  alert_history: any[]
}







export interface PolyBelt {
  id: number
  section: string
  size: number
  ribs: number
  in_ribs?: number
  out_ribs?: number
  reorder_level: number
  rate_per_rib: number
  rate: number
  value: number
  remark?: string
  sku: string
  category: string
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  stock_alert?: StockAlert | null
}

export interface Transaction {
  id: number
  category: string
  product_id: number
  type: 'IN' | 'OUT' | 'EDIT'
  quantity?: number
  stock_before: number
  stock_after: number
  rate: number
  description: string
  user_id: number
  user?: { id: number; name: string }
  created_at: string
}

export function usePolyBelts(section?: string) {
  const products = ref<PolyBelt[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  console.log('usePolyBelts initialized with section:', section)

  // Fetch products
  const fetchProducts = async () => {
    loading.value = true
    error.value = null
    try {
      const url = section 
        ? `/api/poly-belts/section/${section}`
        : '/api/poly-belts?paginate=false'
      
      console.log('Fetching from URL:', url, 'for section:', section)
      const response = await axios.get(url)
      console.log('Response received:', response.status, 'Data length:', response.data?.length)
      
      // Transform data to ensure numeric types
      products.value = response.data.map((item: any) => ({
        ...item,
        size: Number(item.size || 0),
        ribs: Number(item.ribs || 0),
        in_ribs: Number(item.in_ribs || 0),
        out_ribs: Number(item.out_ribs || 0),
        reorder_level: Number(item.reorder_level || 0),
        rate_per_rib: Number(item.rate_per_rib || 0),
        value: Number(item.value || 0),
      }))
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch products'
      console.error('Fetch error:', err)
      console.error('Error details:', err.response?.data)
    } finally {
      loading.value = false
    }
  }

  // Create product
  const createProduct = async (data: Partial<PolyBelt>) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/api/poly-belts', data)
      await fetchProducts() // Refresh list
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create product'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update product
  const updateProduct = async (id: number, data: Partial<PolyBelt>) => {
    loading.value = true
    error.value = null
    try {
      console.log('🔄 Updating product:', id, 'with data:', data)
      const response = await axios.put(`/api/poly-belts/${id}`, data)
      console.log('✅ Update response:', response.data)
      
      // Extract the product data from the response (it's nested under 'product')
      const productData = response.data.product || response.data
      console.log('📦 Product data extracted:', productData)
      
      // Update the specific product in the local array instead of refetching all
      const index = products.value.findIndex(p => p.id === id)
      if (index !== -1) {
        const updatedProduct = {
          ...products.value[index],
          ...productData,
          // Ensure numeric types with fallbacks
          size: Number(productData.size || products.value[index].size || 0),
          ribs: Number(productData.ribs || products.value[index].ribs || 0),
          rate_per_rib: Number(productData.rate_per_rib || products.value[index].rate_per_rib || 0),
          value: Number(productData.value || products.value[index].value || 0),
        }
        console.log('📊 Updated product in local array:', updatedProduct)
        products.value[index] = updatedProduct
      }
      
      return productData
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update product'
      console.error('❌ Update error:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete product
  const deleteProduct = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await axios.delete(`/api/poly-belts/${id}`)
      await fetchProducts() // Refresh list
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete product'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Bulk import
  const bulkImport = async (productsData: Partial<PolyBelt>[], mode: 'append' | 'replace') => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/api/poly-belts/bulk-import', {
        products: productsData,
        mode
      })
      await fetchProducts() // Refresh list
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to import products'
      throw err
    } finally {
      loading.value = false
    }
  }

  // IN/OUT operations
  const inOutOperation = async (productIds: number[], type: 'IN' | 'OUT', quantity: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/api/poly-belts/in-out', {
        product_ids: productIds,
        type,
        quantity
      })
      await fetchProducts() // Refresh list
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to perform operation'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Get transaction history
  const getTransactions = async (productId: number): Promise<Transaction[]> => {
    try {
      const response = await axios.get(`/api/poly-belts/${productId}/transactions`)
      return response.data.transactions
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch transactions'
      throw err
    }
  }

  return {
    products,
    loading,
    error,
    fetchProducts,
    createProduct,
    updateProduct,
    deleteProduct,
    bulkImport,
    inOutOperation,
    getTransactions,
  }
}