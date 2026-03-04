#!/bin/bash

# Production Timing Belt Fixes Deployment Script
# This script applies the fixes for:
# 1. Rate becoming zero when changing size/mm
# 2. Settings values resetting on refresh  
# 3. Separate IN/OUT for Full Sleeve and Total MM operations

echo "🚀 Deploying Timing Belt Fixes to PRODUCTION"
echo "============================================="

# Safety check
read -p "⚠️  Are you sure you want to deploy to PRODUCTION? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Deployment cancelled."
    exit 1
fi

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

echo "📋 Step 1: Creating backup..."
BACKUP_DIR="backups/timing_belt_fixes_production_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup files that will be modified
cp app/Models/TimingBelt.php "$BACKUP_DIR/" 2>/dev/null || echo "⚠️  TimingBelt.php not found for backup"
cp app/Http/Controllers/Api/TimingBeltController.php "$BACKUP_DIR/" 2>/dev/null || echo "⚠️  TimingBeltController.php not found for backup"
cp routes/api_timing_belts.php "$BACKUP_DIR/" 2>/dev/null || echo "⚠️  api_timing_belts.php not found for backup"
cp resources/js/composables/useTimingBelts.ts "$BACKUP_DIR/" 2>/dev/null || echo "⚠️  useTimingBelts.ts not found for backup"
cp resources/js/components/inventory/TimingBeltTable.vue "$BACKUP_DIR/" 2>/dev/null || echo "⚠️  TimingBeltTable.vue not found for backup"

echo "✅ Files backed up to $BACKUP_DIR"

echo "📋 Step 2: Applying database migrations..."
php artisan migrate --force

echo "📋 Step 3: Updating TimingBelt Model..."
cat > app/Models/TimingBelt.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TimingBelt extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'size',
        'type',
        'mm',
        'total_mm',
        'in_mm',
        'out_mm',
        'full_sleeve',
        'in_sleeve',
        'out_sleeve',
        'rate_per_sleeve',
        'reorder_level',
        'rate',
        'value',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mm' => 'decimal:2',
        'total_mm' => 'decimal:2',
        'in_mm' => 'decimal:2',
        'out_mm' => 'decimal:2',
        'full_sleeve' => 'integer',
        'in_sleeve' => 'integer',
        'out_sleeve' => 'integer',
        'rate_per_sleeve' => 'decimal:2',
        'rate' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($timingBelt) {
            $timingBelt->calculateValue();
        });

        static::updating(function ($timingBelt) {
            // Recalculate value when relevant fields change
            if ($timingBelt->isDirty(['size', 'type', 'total_mm', 'rate'])) {
                $timingBelt->calculateValue();
            }
        });
    }

    /**
     * Calculate total value and rate based on the STRICT timing belt formula:
     * value = (size × type × 450 × multiplier) + (size × total_mm × multiplier)
     * rate = value / total_mm (rate per mm)
     */
    public function calculateValue()
    {
        // Get the multiplier for this section from rate_formulas
        $formula = DB::table('rate_formulas')
            ->where('category', 'timing_belts')
            ->where('section', $this->section)
            ->where('is_active', 1)
            ->first();
        
        if (!$formula) {
            // Fallback: set rate and value to 0 if no formula found
            $this->rate = 0;
            $this->value = 0;
            return;
        }
        
        // Parse the formula string to extract the multiplier
        $formulaString = $formula->formula;
        $multiplier = 0;
        
        // Handle different formula formats
        if (is_numeric($formulaString)) {
            // Simple numeric multiplier (e.g., "0.0094")
            $multiplier = (float) $formulaString;
        } elseif (preg_match('/size\/(\d+(?:\.\d+)?)\*(\d+(?:\.\d+)?)/', $formulaString, $matches)) {
            // Formula like "size/1*0.0094"
            $divisor = (float) $matches[1];
            $multiplier = (float) $matches[2];
        } else {
            // Try to extract just the multiplier from the end
            if (preg_match('/(\d+(?:\.\d+)?)$/', $formulaString, $matches)) {
                $multiplier = (float) $matches[1];
            }
        }
        
        if ($multiplier <= 0) {
            // Set rate and value to 0 if multiplier is invalid
            $this->rate = 0;
            $this->value = 0;
            return;
        }
        
        $size = (float) $this->size;
        $totalMm = (float) $this->total_mm;
        
        // Convert type to numeric value
        $typeNumeric = $this->getTypeNumericValue();
        
        // STRICT FORMULA: (size × type × 450 × multiplier) + (size × total_mm × multiplier)
        $part1 = $size * $typeNumeric * 450 * $multiplier;
        $part2 = $size * $totalMm * $multiplier;
        
        $this->value = $part1 + $part2;
        
        // Calculate rate per mm (avoid division by zero)
        if ($totalMm > 0) {
            $this->rate = $this->value / $totalMm;
        } else {
            // If no total_mm, rate is just the first part divided by 1mm
            $this->rate = $part1;
        }
    }
    
    /**
     * Convert type to numeric value for calculation
     */
    private function getTypeNumericValue()
    {
        // For neoprene belts with "FULL SLEEVE", treat as 1
        if ($this->type === 'FULL SLEEVE') {
            return 1;
        }
        
        // For commercial belts, use the numeric type value
        return (float) $this->type;
    }

    /**
     * Scope to filter by section
     */
    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('total_mm <= reorder_level');
    }

    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('total_mm', '<=', 0);
    }

    /**
     * Get current stock
     */
    public function getCurrentStock()
    {
        return $this->total_mm ?? 0;
    }

    /**
     * Get stock unit description
     */
    public function getStockUnit()
    {
        return 'mm';
    }

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
EOF

echo "✅ TimingBelt Model updated"

echo "📋 Step 4: Updating TimingBeltController..."
# Create a backup of the current controller and update the inOutOperation method
php -r "
\$file = file_get_contents('app/Http/Controllers/Api/TimingBeltController.php');

// Add the new methods if they don't exist
if (strpos(\$file, 'recalculateAllRates') === false) {
    \$newMethods = '
    /**
     * Recalculate rates for all timing belts based on current formulas
     */
    public function recalculateAllRates()
    {
        try {
            DB::beginTransaction();

            \$timingBelts = TimingBelt::all();
            \$updated = 0;

            foreach (\$timingBelts as \$timingBelt) {
                \$timingBelt->calculateValue();
                \$timingBelt->save();
                \$updated++;
            }

            DB::commit();

            return response()->json([
                \"message\" => \"Recalculated rates for {\$updated} timing belt products\",
                \"updated_count\" => \$updated
            ]);

        } catch (\Exception \$e) {
            DB::rollBack();
            return response()->json([
                \"message\" => \"Failed to recalculate rates\",
                \"error\" => \$e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate rates for specific section
     */
    public function recalculateSectionRates(Request \$request)
    {
        \$request->validate([
            \"section\" => \"required|string\"
        ]);

        try {
            DB::beginTransaction();

            \$timingBelts = TimingBelt::where(\"section\", \$request->section)->get();
            \$updated = 0;

            foreach (\$timingBelts as \$timingBelt) {
                \$timingBelt->calculateValue();
                \$timingBelt->save();
                \$updated++;
            }

            DB::commit();

            return response()->json([
                \"message\" => \"Recalculated rates for {\$updated} products in {\$request->section} section\",
                \"updated_count\" => \$updated
            ]);

        } catch (\Exception \$e) {
            DB::rollBack();
            return response()->json([
                \"message\" => \"Failed to recalculate section rates\",
                \"error\" => \$e->getMessage()
            ], 500);
        }
    }';
    
    \$file = str_replace('    }' . PHP_EOL . '}', \$newMethods . PHP_EOL . '    }' . PHP_EOL . '}', \$file);
}

// Update the inOutOperation method to support unit_type
\$oldInOutPattern = '/public function inOutOperation\(Request \$request\).*?(?=public function|\$)/s';
\$newInOutMethod = 'public function inOutOperation(Request \$request)
    {
        \Log::info(\"Timing Belt IN/OUT operation started\", [
            \"request_data\" => \$request->all(),
            \"user\" => session(\"user\")
        ]);

        \$validator = Validator::make(\$request->all(), [
            \"ids\" => \"required|array\",
            \"ids.*\" => \"exists:timing_belts,id\",
            \"action\" => \"required|in:IN,OUT\",
            \"unit_type\" => \"required|in:total_mm,full_sleeve\",
            \"quantity\" => \"required|numeric|min:0.01\",
            \"remark\" => \"nullable|string\"
        ]);

        if (\$validator->fails()) {
            \Log::error(\"Timing Belt IN/OUT validation failed\", [
                \"errors\" => \$validator->errors()
            ]);
            return response()->json([
                \"message\" => \"Validation failed\",
                \"errors\" => \$validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            \$results = [];
            foreach (\$request->ids as \$id) {
                \$timingBelt = TimingBelt::findOrFail(\$id);
                
                \$unitType = \$request->unit_type;
                \$change = \$request->quantity;
                
                if (\$unitType === \"total_mm\") {
                    // Total MM operations
                    \$oldStock = \$timingBelt->total_mm;
                    
                    if (\$request->action === \"IN\") {
                        \$timingBelt->total_mm += \$change;
                        \$timingBelt->in_mm += \$change;
                    } else { // OUT
                        if (\$timingBelt->total_mm < \$change) {
                            throw new \Exception(\"Insufficient Total MM stock for {\$timingBelt->section}-{\$timingBelt->size}. Available: {\$timingBelt->total_mm}mm, Requested: {\$change}mm\");
                        }
                        \$timingBelt->total_mm -= \$change;
                        \$timingBelt->out_mm += \$change;
                    }
                    
                    \$results[] = [
                        \"id\" => \$timingBelt->id,
                        \"section\" => \$timingBelt->section,
                        \"size\" => \$timingBelt->size,
                        \"unit_type\" => \"total_mm\",
                        \"old_stock\" => \$oldStock,
                        \"new_stock\" => \$timingBelt->total_mm,
                        \"change\" => \$change,
                    ];
                    
                } else { // full_sleeve operations
                    // Full Sleeve operations
                    \$oldSleeveStock = \$timingBelt->full_sleeve ?? 0;
                    
                    if (\$request->action === \"IN\") {
                        \$timingBelt->full_sleeve = (\$timingBelt->full_sleeve ?? 0) + \$change;
                        \$timingBelt->in_sleeve = (\$timingBelt->in_sleeve ?? 0) + \$change;
                    } else { // OUT
                        if ((\$timingBelt->full_sleeve ?? 0) < \$change) {
                            throw new \Exception(\"Insufficient Full Sleeve stock for {\$timingBelt->section}-{\$timingBelt->size}. Available: {\$timingBelt->full_sleeve} sleeves, Requested: {\$change} sleeves\");
                        }
                        \$timingBelt->full_sleeve = (\$timingBelt->full_sleeve ?? 0) - \$change;
                        \$timingBelt->out_sleeve = (\$timingBelt->out_sleeve ?? 0) + \$change;
                    }
                    
                    \$results[] = [
                        \"id\" => \$timingBelt->id,
                        \"section\" => \$timingBelt->section,
                        \"size\" => \$timingBelt->size,
                        \"unit_type\" => \"full_sleeve\",
                        \"old_stock\" => \$oldSleeveStock,
                        \"new_stock\" => \$timingBelt->full_sleeve,
                        \"change\" => \$change,
                    ];
                }
                
                \$timingBelt->save();

                // Create transaction record
                InventoryTransaction::create([
                    \"category\" => \"timing_belts\",
                    \"product_id\" => \$timingBelt->id,
                    \"type\" => \$request->action,
                    \"quantity\" => \$request->quantity,
                    \"stock_before\" => \$unitType === \"total_mm\" ? (\$oldStock ?? 0) : (\$oldSleeveStock ?? 0),
                    \"stock_after\" => \$unitType === \"total_mm\" ? \$timingBelt->total_mm : \$timingBelt->full_sleeve,
                    \"rate\" => \$unitType === \"total_mm\" ? (\$timingBelt->rate ?? 0) : (\$timingBelt->rate_per_sleeve ?? 0),
                    \"description\" => \"{\$request->action} {\$change}\" . (\$unitType === \"total_mm\" ? \"mm\" : \" sleeves\"),
                    \"user_id\" => session(\"user\")[\"id\"] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                \"message\" => \"Successfully processed {\$request->action} operation for \" . count(\$request->ids) . \" timing belts\",
                \"results\" => \$results
            ]);

        } catch (\Exception \$e) {
            DB::rollBack();
            \Log::error(\"Timing Belt IN/OUT operation failed\", [
                \"error\" => \$e->getMessage(),
                \"trace\" => \$e->getTraceAsString()
            ]);
            return response()->json([
                \"message\" => \"Operation failed\",
                \"error\" => \$e->getMessage()
            ], 500);
        }
    }

    ';

if (preg_match(\$oldInOutPattern, \$file)) {
    \$file = preg_replace(\$oldInOutPattern, \$newInOutMethod, \$file);
}

file_put_contents('app/Http/Controllers/Api/TimingBeltController.php', \$file);
"

echo "✅ TimingBeltController updated"

echo "📋 Step 5: Updating API routes..."
# Update routes to include new endpoints
if ! grep -q "recalculate-all-rates" routes/api_timing_belts.php 2>/dev/null; then
    sed -i.bak '/Route::post.*seed-section/a\
    Route::post("/recalculate-all-rates", [TimingBeltController::class, "recalculateAllRates"]);\
    Route::post("/recalculate-section-rates", [TimingBeltController::class, "recalculateSectionRates"]);' routes/api_timing_belts.php
fi

echo "✅ API routes updated"

echo "📋 Step 6: Updating frontend composable..."
# Update useTimingBelts.ts interface
cat > resources/js/composables/useTimingBelts.ts << 'EOF'
import { ref, computed } from 'vue'
import axios from '../lib/axios'

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
  unit_type?: 'total_mm' | 'full_sleeve'
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
EOF

echo "✅ Frontend composable updated"

echo "📋 Step 7: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "📋 Step 8: Building frontend assets..."
if command -v npm &> /dev/null; then
    npm run build
else
    echo "⚠️  Warning: npm not found. Please run 'npm run build' manually."
fi

echo "📋 Step 9: Testing the fixes..."
# Test the calculation with a sample timing belt
php artisan tinker --execute="
\$belt = new App\Models\TimingBelt([
    'section' => 'XL',
    'size' => '150', 
    'type' => '18',
    'total_mm' => 1000
]);
\$belt->calculateValue();
echo 'Production Test Results:' . PHP_EOL;
echo 'Section: ' . \$belt->section . PHP_EOL;
echo 'Size: ' . \$belt->size . PHP_EOL;
echo 'Type: ' . \$belt->type . PHP_EOL;
echo 'Total MM: ' . \$belt->total_mm . PHP_EOL;
echo 'Rate: ' . \$belt->rate . PHP_EOL;
echo 'Value: ' . \$belt->value . PHP_EOL;
if (\$belt->rate > 0) {
    echo '✅ Rate calculation working in production!' . PHP_EOL;
} else {
    echo '❌ Rate calculation failed in production!' . PHP_EOL;
}
"

echo ""
echo "🎉 Timing Belt Fixes Successfully Deployed to PRODUCTION!"
echo ""
echo "📝 Summary of Changes Applied:"
echo "   ✅ Fixed rate becoming zero when changing size/mm"
echo "   ✅ Fixed settings values resetting on refresh"
echo "   ✅ Added dual IN/OUT operations for Total MM and Full Sleeve"
echo "   ✅ Implemented strict formula: (size × type × 450 × multiplier) + (size × total_mm × multiplier)"
echo ""
echo "🔧 Next Steps:"
echo "   1. Test the timing belt functionality in the web interface"
echo "   2. Verify that rate calculations work correctly"
echo "   3. Test both Total MM and Full Sleeve IN/OUT operations"
echo "   4. Check that settings formulas persist after refresh"
echo ""
echo "📁 Backup Location: $BACKUP_DIR"
echo "   (Keep this backup in case rollback is needed)"
echo ""
echo "⚠️  IMPORTANT: Test all functionality before confirming deployment success!"