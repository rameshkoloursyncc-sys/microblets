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

export interface RawCarbon {
  id: number
  section: string
  packing: string
  balance_stock: number // This will handle decimal values
  in_stock?: number // This will handle decimal values
  out_stock?: number // This will handle decimal values
  reorder_level: number
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

export function useRawCarbon(section?: string) {
  const products = ref<RawCarbon[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  console.log('rawcarbon initialized with section:', section)

  // Fetch products
  const fetchProducts = async () => {
    loading.value = true
    error.value = null
    try {
      const url = section 
        ? `/api/rawcarbon/category/${section}` // Use category endpoint for raw materials
        : '/api/rawcarbon?paginate=false'
      
      console.log('Fetching from URL:', url, 'for section:', section)
      const response = await axios.get(url)
      console.log('Response received:', response.status, 'Data length:', response.data?.length)
      
      // Transform data to ensure numeric types
      products.value = response.data.map((item: any) => ({
        ...item,
        balance_stock: Number(item.balance_stock),
        in_stock: Number(item.in_stock || 0),
        out_stock: Number(item.out_stock || 0),
        reorder_level: Number(item.reorder_level),
        rate: Number(item.rate),
        value: Number(item.value),
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
  const createProduct = async (data: Partial<RawCarbon>) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/api/rawcarbon', data)
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
  const updateProduct = async (id: number, data: Partial<RawCarbon>) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/api/rawcarbon/${id}`, data)
      await fetchProducts() // Refresh list
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update product'
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
      await axios.delete(`/api/rawcarbon/${id}`)
      await fetchProducts() // Refresh list
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete product'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Bulk import
  const bulkImport = async (productsData: Partial<RawCarbon>[], mode: 'append' | 'replace', category?: string) => {
    loading.value = true
    error.value = null
    try {
      const payload: any = {
        products: productsData,
        mode
      }
      
      // Add category if provided (for raw materials)
      if (category) {
        payload.category = category
      }
      
      const response = await axios.post('/api/rawcarbon/bulk-import', payload)
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
      const response = await axios.post('/api/rawcarbon/in-out', {
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
      const response = await axios.get(`/api/rawcarbon/${productId}/transactions`)
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
