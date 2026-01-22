import { ref, computed } from 'vue'
import axios from '@/lib/axios'

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

export interface TpuBelt {
  id: number
  section: string
  width: string
  meter: number
  in_meter?: number
  out_meter?: number
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
  product_type: string
  product_id: number
  type: 'IN' | 'OUT' | 'EDIT'
  quantity: number
  stock_before: number
  stock_after: number
  description: string
  created_by?: number
  created_at: string
  user?: {
    id: number
    name: string
  }
}

export interface CreateTpuBeltData {
  section: string
  width: string
  meter: number
  rate: number
  remark?: string
}

export interface InOutOperationData {
  ids: number[]
  action: 'IN' | 'OUT'
  quantity: number
  unit_type: 'width' | 'meter'
  remark?: string
}

export function useTpuBelts(section?: string) {
  const products = ref<TpuBelt[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const baseURL = '/api/tpu-belts'

  const fetchProducts = async () => {
    loading.value = true
    error.value = null
    
    try {
      const url = section ? `${baseURL}/section/${section}` : baseURL
      console.log('Fetching TPU belts from:', url)
      const response = await axios.get(url)
      console.log('TPU belts fetched:', response.data.length, 'products')
      products.value = response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch TPU belts'
      console.error('Error fetching TPU belts:', err)
    } finally {
      loading.value = false
    }
  }

  const createProduct = async (data: CreateTpuBeltData) => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.post(baseURL, data)
      products.value.push(response.data)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create TPU belt'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateProduct = async (id: number, data: Partial<TpuBelt>) => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.put(`${baseURL}/${id}`, data)
      const index = products.value.findIndex(p => p.id === id)
      if (index !== -1) {
        products.value[index] = response.data
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update TPU belt'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteProduct = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      await axios.delete(`${baseURL}/${id}`)
      products.value = products.value.filter(p => p.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete TPU belt'
      throw err
    } finally {
      loading.value = false
    }
  }

  const bulkImport = async (data: TpuBelt[], mode: 'append' | 'replace' = 'append') => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.post(`${baseURL}/bulk-import`, {
        data,
        mode
      })
      
      // Refresh the products list
      await fetchProducts()
      
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to import TPU belts'
      throw err
    } finally {
      loading.value = false
    }
  }

  const inOutOperation = async (data: InOutOperationData) => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.post(`${baseURL}/in-out`, data)
      // Don't auto-refresh here, let the component handle it
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to perform operation'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getTransactions = async (productId: number): Promise<Transaction[]> => {
    try {
      const response = await axios.get(`${baseURL}/${productId}/transactions`)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch transactions'
      throw err
    }
  }

  // Computed properties for summary statistics
  const totalProducts = computed(() => products.value.length)
  
  const totalMeter = computed(() => {
    return products.value.reduce((sum, product) => sum + Number(product.meter), 0)
  })

  const totalValue = computed(() => {
    return products.value.reduce((sum, product) => sum + Number(product.value), 0)
  })

  const zeroMeterCount = computed(() => {
    return products.value.filter(product => product.meter === 0).length
  })

  return {
    // State
    products,
    loading,
    error,

    // Actions
    fetchProducts,
    createProduct,
    updateProduct,
    deleteProduct,
    bulkImport,
    inOutOperation,
    getTransactions,

    // Computed
    totalProducts,
    totalMeter,
    totalValue,
    zeroMeterCount,
  }
}