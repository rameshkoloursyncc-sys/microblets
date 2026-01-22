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


export interface TimingBelt {
  id: number
  section: string
  size: string
  type?: string
  mm?: number
  total_mm?: number
  in_mm?: number
  out_mm?: number
  full_sleeve?: number
  in_sleeve?: number
  out_sleeve?: number
  rate_per_sleeve?: number
  rate?: number
  value?: number
  reorder_level: number
  remark?: string
  created_by?: number
  updated_by?: number
  created_at?: string
  updated_at?: string
  stock_alert?: StockAlert | 0
}

export interface Transaction {
  id: number
  category: string
  product_id: number
  type: 'IN' | 'OUT' | 'EDIT'
  quantity: number
  stock_before: number
  stock_after: number
  rate: number
  description: string
  user_id?: number
  user?: { name: string }
  created_at: string
}

export interface InOutRequest {
  ids: number[]
  action: 'IN' | 'OUT'
  unit_type?: 'total_mm' | 'type'
  quantity: number
  remark?: string
}

export function useTimingBelts(section?: string) {
  const products = ref<TimingBelt[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchProducts = async () => {
    loading.value = true
    error.value = null
    
    try {
      const url = section ? `/api/timing-belts/section/${section}` : '/api/timing-belts'
      console.log('Fetching timing belts from:', url)
      
      const response = await axios.get(url)
      console.log('Timing belts fetched:', response.data?.length || 0, 'products')
      
      // Ensure we have a valid array
      products.value = Array.isArray(response.data) ? response.data : []
    } catch (err: any) {
      console.error('Error fetching timing belts:', err)
      error.value = err.response?.data?.message || 'Failed to load timing belts'
      products.value = [] // Reset to empty array on error
    } finally {
      loading.value = false
    }
  }

  const createProduct = async (data: Partial<TimingBelt>) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/timing-belts', data)
      await fetchProducts() // Refresh the list
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create timing belt'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateProduct = async (id: number, data: Partial<TimingBelt>) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.put(`/api/timing-belts/${id}`, data)
      await fetchProducts() // Refresh the list
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update timing belt'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteProduct = async (id: number) => {
    loading.value = true
    error.value = null
    
    try {
      await axios.delete(`/api/timing-belts/${id}`)
      await fetchProducts() // Refresh the list
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete timing belt'
      throw err
    } finally {
      loading.value = false
    }
  }

  const bulkImport = async (data: any[], mode: 'append' | 'replace') => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/timing-belts/bulk-import', {
        data,
        mode
      })
      await fetchProducts() // Refresh the list
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to import timing belts'
      throw err
    } finally {
      loading.value = false
    }
  }

  const inOutOperation = async (request: InOutRequest) => {
    loading.value = true
    error.value = null
    
    try {
      console.log('Sending IN/OUT request:', request)
      const response = await axios.post('/api/timing-belts/in-out', request)
      console.log('IN/OUT response:', response.data)
      
      console.log('Refreshing products after IN/OUT operation...')
      await fetchProducts()
      console.log('Products refreshed, new count:', products.value.length)
      
      return response.data
    } catch (err: any) {
      console.error('IN/OUT operation error:', err)
      error.value = err.response?.data?.message || 'Failed to perform IN/OUT operation'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getTransactions = async (productId: number): Promise<Transaction[]> => {
    try {
      const response = await axios.get(`/api/timing-belts/${productId}/transactions`)
      return response.data
    } catch (err: any) {
      console.error('Error fetching transactions:', err)
      throw err
    }
  }

  // Computed properties for statistics
  const totalProducts = computed(() => (products.value || []).length)
  
  const totalStock = computed(() => {
    return (products.value || []).reduce((sum, p) => sum + (Number(p?.total_mm) || 0), 0)
  })
  
  const totalValue = computed(() => {
    return (products.value || []).reduce((sum, p) => sum + (Number(p?.value) || 0), 0)
  })
  
  const lowStockCount = computed(() => {
    return (products.value || []).filter(p => {
      if (!p) return false
      const currentStock = Number(p.total_mm) || 0
      return currentStock <= (Number(p.reorder_level) || 0)
    }).length
  })
  
  const outOfStockCount = computed(() => {
    return (products.value || []).filter(p => {
      if (!p) return false
      const currentStock = Number(p.total_mm) || 0
      return currentStock <= 0
    }).length
  })

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
    totalProducts,
    totalStock,
    totalValue,
    lowStockCount,
    outOfStockCount
  }
}