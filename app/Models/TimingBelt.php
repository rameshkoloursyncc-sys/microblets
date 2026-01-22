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
     * Calculate total value and rate based on the DYNAMIC timing belt formula:
     * value = (size × type × type_multiplier × multiplier) + (size × total_mm × multiplier)
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
        
        // Parse the formula string to extract the multiplier and type multiplier
        $formulaString = $formula->formula;
        $multiplier = 0;
        $typeMultiplier = 450; // Default value
        
        // Handle different formula formats
        if (is_numeric($formulaString)) {
            // Simple numeric multiplier (e.g., "0.0094")
            $multiplier = (float) $formulaString;
        } elseif (preg_match('/size\*type\*(\d+(?:\.\d+)?)\*(\d+(?:\.\d+)?)\+size\*total_mm\*(\d+(?:\.\d+)?)/', $formulaString, $matches)) {
            // New format: "size*type*450*0.0094+size*total_mm*0.0094"
            $typeMultiplier = (float) $matches[1];
            $multiplier = (float) $matches[2];
        } elseif (preg_match('/size\/(\d+(?:\.\d+)?)\*(\d+(?:\.\d+)?)/', $formulaString, $matches)) {
            // Old format: "size/1*0.0094"
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
        
        // DYNAMIC FORMULA: (size × type × type_multiplier × multiplier) + (size × total_mm × multiplier)
        $part1 = $size * $typeNumeric * $typeMultiplier * $multiplier;
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


    public function stockAlert()
    {
        return $this->hasOne(StockAlertTracking::class, 'product_id')
        ->where("belts_type", 'timing')
        ->where("is_active", true);
    }
}
