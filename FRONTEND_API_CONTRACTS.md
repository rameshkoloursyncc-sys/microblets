# Frontend API Contracts & Composables

## Overview
Vue.js composables that handle all API interactions with type safety and error handling.

---

## Authentication Composable

### `useAuth.ts`

```typescript
interface User {
  id: number
  name: string
  role: 'admin' | 'user'
}

interface AuthComposable {
  // State
  user: Ref<User | null>
  isAuthenticated: Computed<boolean>
  isAdmin: Computed<boolean>
  isUser: Computed<boolean>
  
  // Methods
  initAuth(): Promise<void>
  login(credentials: LoginCredentials): Promise<User>
  logout(): Promise<void>
  createUser(userData: CreateUserData): Promise<User>
  getUsers(): Promise<User[]>
  deleteUser(id: number): Promise<void>
}

interface LoginCredentials {
  name: string
  password: string
}

interface CreateUserData {
  name: string
  password: string
  role: 'admin' | 'user'
}
```

**Usage:**
```typescript
const { user, isAuthenticated, login, logout } = useAuth()

// Login
await login({ name: 'admin', password: 'password' })

// Check authentication
if (isAuthenticated.value) {
  // User is logged in
}

// Logout
await logout()
```

---

## Inventory Composables

### `useVeeBelts.ts`

```typescript
interface VeeBelt {
  id: number
  section: string
  size: string
  balance_stock: number
  in_stock: number
  out_stock: number
  reorder_level: number | null
  rate: string
  value: string
  remark: string | null
  sku: string
  category: string
  created_by: number | null
  updated_by: number | null
  created_at: string
  updated_at: string
}

interface VeeBeltComposable {
  // State
  products: Ref<VeeBelt[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  // Methods
  fetchProducts(): Promise<void>
  createProduct(data: CreateVeeBeltData): Promise<VeeBelt>
  updateProduct(id: number, data: Partial<VeeBelt>): Promise<VeeBelt>
  deleteProduct(id: number): Promise<void>
  bulkImport(file: File): Promise<BulkImportResult>
  inOutOperation(productIds: number[], action: 'IN' | 'OUT', quantity: number): Promise<void>
  getTransactions(productId: number): Promise<Transaction[]>
}

interface CreateVeeBeltData {
  section: string
  size: string
  balance_stock: number
  reorder_level?: number | null
  rate?: number
  remark?: string
}

interface BulkImportResult {
  message: string
  imported: number
  updated: number
  errors: string[]
}

interface Transaction {
  id: number
  type: 'IN' | 'OUT' | 'EDIT'
  quantity?: number
  stock_before?: number
  stock_after: number
  rate: string
  description: string
  user_id: number | null
  user?: { id: number; name: string }
  created_at: string
}
```

**Usage:**
```typescript
const { products, loading, fetchProducts, createProduct, inOutOperation } = useVeeBelts('A')

// Fetch products for section A
await fetchProducts()

// Create new product
await createProduct({
  section: 'A',
  size: '25',
  balance_stock: 100,
  reorder_level: 20,
  rate: 25.50
})

// Perform IN operation
await inOutOperation([1, 2], 'IN', 50)
```

### `useTimingBelts.ts`

```typescript
interface TimingBelt {
  id: number
  type: string
  size: string
  total_mm: number
  sleeves: number
  rate: string
  rate_per_sleeve: string
  balance_stock_mm: number
  balance_stock_sleeves: number
  reorder_level: number | null
  remark: string | null
  created_by: number | null
  updated_by: number | null
  created_at: string
  updated_at: string
}

interface TimingBeltComposable {
  // State
  products: Ref<TimingBelt[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  // Methods
  fetchProducts(): Promise<void>
  createProduct(data: CreateTimingBeltData): Promise<TimingBelt>
  updateProduct(id: number, data: Partial<TimingBelt>): Promise<TimingBelt>
  deleteProduct(id: number): Promise<void>
  inOutOperation(productIds: number[], action: 'IN' | 'OUT', quantity: number, unitType: 'total_mm' | 'sleeves'): Promise<void>
  getTransactions(productId: number): Promise<Transaction[]>
}

interface CreateTimingBeltData {
  type: string
  size: string
  total_mm: number
  sleeves: number
  rate?: number
  rate_per_sleeve?: number
  reorder_level?: number | null
  remark?: string
}
```

**Usage:**
```typescript
const { products, createProduct, inOutOperation } = useTimingBelts('T5')

// Create timing belt
await createProduct({
  type: 'T5',
  size: '225',
  total_mm: 2250,
  sleeves: 15,
  rate: 45.00
})

// IN operation with mm units
await inOutOperation([1], 'IN', 300, 'total_mm')

// OUT operation with sleeve units
await inOutOperation([1], 'OUT', 2, 'sleeves')
```

### `useCoggedBelts.ts`

```typescript
interface CoggedBelt {
  id: number
  section: string
  size: string
  balance_stock: number
  rate: string
  value: string
  reorder_level: number | null
  remark: string | null
  created_at: string
  updated_at: string
}

interface CoggedBeltComposable {
  products: Ref<CoggedBelt[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  fetchProducts(): Promise<void>
  createProduct(data: CreateCoggedBeltData): Promise<CoggedBelt>
  updateProduct(id: number, data: Partial<CoggedBelt>): Promise<CoggedBelt>
  deleteProduct(id: number): Promise<void>
  inOutOperation(productIds: number[], action: 'IN' | 'OUT', quantity: number): Promise<void>
}
```

### `usePolyBelts.ts`

```typescript
interface PolyBelt {
  id: number
  section: string
  size: string
  ribs: number
  rate_per_rib: string
  total_value: string
  reorder_level: number | null
  remark: string | null
  created_at: string
  updated_at: string
}

interface PolyBeltComposable {
  products: Ref<PolyBelt[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  fetchProducts(): Promise<void>
  createProduct(data: CreatePolyBeltData): Promise<PolyBelt>
  updateProduct(id: number, data: Partial<PolyBelt>): Promise<PolyBelt>
  deleteProduct(id: number): Promise<void>
  inOutOperation(productIds: number[], action: 'IN' | 'OUT', quantity: number): Promise<void>
}

interface CreatePolyBeltData {
  section: string
  size: string
  ribs: number
  rate_per_rib?: number
  reorder_level?: number | null
  remark?: string
}
```

### `useTpuBelts.ts`

```typescript
interface TpuBelt {
  id: number
  type: string
  size: string
  balance_stock: number
  rate: string
  value: string
  reorder_level: number | null
  remark: string | null
  created_at: string
  updated_at: string
}

interface TpuBeltComposable {
  products: Ref<TpuBelt[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  fetchProducts(): Promise<void>
  createProduct(data: CreateTpuBeltData): Promise<TpuBelt>
  updateProduct(id: number, data: Partial<TpuBelt>): Promise<TpuBelt>
  deleteProduct(id: number): Promise<void>
  inOutOperation(productIds: number[], action: 'IN' | 'OUT', quantity: number): Promise<void>
}
```

### `useSpecialBelts.ts`

```typescript
interface SpecialBelt {
  id: number
  type: string
  size: string
  balance_stock: number
  rate: string
  value: string
  reorder_level: number | null
  remark: string | null
  created_at: string
  updated_at: string
}

interface SpecialBeltComposable {
  products: Ref<SpecialBelt[]>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  fetchProducts(): Promise<void>
  createProduct(data: CreateSpecialBeltData): Promise<SpecialBelt>
  updateProduct(id: number, data: Partial<SpecialBelt>): Promise<SpecialBelt>
  deleteProduct(id: number): Promise<void>
  inOutOperation(productIds: number[], action: 'IN' | 'OUT', quantity: number): Promise<void>
}
```

---

## Dashboard Composable

### `useDashboard.ts`

```typescript
interface DashboardStats {
  total_products: number
  total_stock: number
  total_value: number
  low_stock_items: number
  out_of_stock_items: number
  sections: Record<string, {
    total_products: number
    total_stock: number
    total_value: number
  }>
}

interface DashboardComposable {
  // State
  stats: Ref<DashboardStats | null>
  loading: Ref<boolean>
  error: Ref<string | null>
  
  // Methods
  fetchStats(): Promise<void>
  fetchDebugInfo(): Promise<any>
}
```

**Usage:**
```typescript
const { stats, loading, fetchStats } = useDashboard()

// Fetch dashboard statistics
await fetchStats()

// Access stats
console.log(`Total Products: ${stats.value?.total_products}`)
console.log(`Total Value: ₹${stats.value?.total_value}`)
```

---

## HTTP Client Configuration

### `axios.ts`

```typescript
// Base configuration
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'
axios.defaults.withCredentials = true

// Request interceptor for authentication
axios.interceptors.request.use((config) => {
  // Add CSRF token
  const token = document.head.querySelector('meta[name="csrf-token"]')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = (token as HTMLMetaElement).content
  }
  
  // Add fallback authentication
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    config.headers['X-Auth-User'] = storedUser
  }
  
  return config
})

// Response interceptor for error handling
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle session expiration
      const isSilentAuth = error.config?.headers?.['X-Silent-Auth'] === 'true'
      
      if (!isSilentAuth) {
        console.log('401 Unauthorized detected')
      }
      
      // Handle critical vs non-critical endpoints
      const criticalEndpoints = ['/api/login', '/api/register', '/api/logout']
      const isCriticalEndpoint = criticalEndpoints.some(endpoint => 
        error.config?.url?.includes(endpoint)
      )
      
      if (isCriticalEndpoint) {
        localStorage.removeItem('user')
      }
      
      if (!isSilentAuth && error.response?.data?.error === 'session_expired') {
        localStorage.setItem('session_expired', 'true')
      }
    }
    
    return Promise.reject(error)
  }
)
```

---

## Error Handling

### Global Error Types

```typescript
interface ApiError {
  message: string
  errors?: Record<string, string[]>
  error?: string
  redirect?: string
}

interface ValidationError extends ApiError {
  errors: Record<string, string[]>
}

interface AuthError extends ApiError {
  error: 'session_expired' | 'unauthorized'
  redirect: string
}
```

### Error Handling Patterns

```typescript
// In composables
const handleApiError = (error: any): string => {
  if (error.response?.status === 422) {
    // Validation error
    const errors = error.response.data.errors
    return Object.values(errors).flat().join(', ')
  }
  
  if (error.response?.status === 401) {
    // Authentication error
    return 'Session expired. Please login again.'
  }
  
  if (error.response?.status === 403) {
    // Authorization error
    return 'You do not have permission to perform this action.'
  }
  
  // Generic error
  return error.response?.data?.message || 'An unexpected error occurred'
}

// Usage in composables
try {
  await api.post('/api/vee-belts', data)
} catch (error) {
  errorMessage.value = handleApiError(error)
  throw error
}
```

---

## Type Definitions

### Common Types

```typescript
// Base product interface
interface BaseProduct {
  id: number
  created_at: string
  updated_at: string
  created_by: number | null
  updated_by: number | null
}

// Stock operation types
type StockOperation = 'IN' | 'OUT'
type UnitType = 'pieces' | 'mm' | 'sleeves' | 'ribs'

// User roles
type UserRole = 'admin' | 'user'

// API response wrapper
interface ApiResponse<T> {
  data: T
  message?: string
}

// Pagination
interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
```

---

## Usage Examples

### Complete CRUD Operations

```typescript
// Component setup
const { 
  products, 
  loading, 
  error, 
  fetchProducts, 
  createProduct, 
  updateProduct, 
  deleteProduct,
  inOutOperation 
} = useVeeBelts('A')

// Fetch data on mount
onMounted(async () => {
  await fetchProducts()
})

// Create new product
const handleCreate = async (formData: CreateVeeBeltData) => {
  try {
    await createProduct(formData)
    // Product automatically added to products array
  } catch (error) {
    console.error('Failed to create product:', error)
  }
}

// Update product
const handleUpdate = async (id: number, updates: Partial<VeeBelt>) => {
  try {
    await updateProduct(id, updates)
    // Product automatically updated in products array
  } catch (error) {
    console.error('Failed to update product:', error)
  }
}

// Stock operations
const handleStockIn = async (productIds: number[], quantity: number) => {
  try {
    await inOutOperation(productIds, 'IN', quantity)
    // Products automatically updated with new stock levels
  } catch (error) {
    console.error('Failed to perform stock operation:', error)
  }
}
```

This documentation provides complete type safety and clear contracts for all frontend-backend interactions in the inventory management system.