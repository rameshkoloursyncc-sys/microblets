<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PolyBelt extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'size',
        'ribs',
        'in_ribs',
        'out_ribs',
        'reorder_level',
        'rate_per_rib',
        'value',
        'remark',
        'sku',
        'category',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'size' => 'decimal:2',
        'ribs' => 'integer',
        'in_ribs' => 'integer',
        'out_ribs' => 'integer',
        'reorder_level' => 'integer',
        'rate_per_rib' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    /**
     * Boot method to auto-calculate fields
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($polyBelt) {
            // Auto-generate SKU if not provided
            if (empty($polyBelt->sku)) {
                $polyBelt->sku = $polyBelt->section . '-' . $polyBelt->size . '-' . $polyBelt->ribs . 'R';
            }

            // Check if size or section changed - if so, recalculate rate
            $sizeChanged = $polyBelt->isDirty('size');
            $sectionChanged = $polyBelt->isDirty('section');
            
            // Auto-calculate rate_per_rib if not provided OR if size/section changed
            if (empty($polyBelt->rate_per_rib) || $sizeChanged || $sectionChanged) {
                $polyBelt->rate_per_rib = $polyBelt->calculateRatePerRib();
            }

            // Auto-calculate value (ribs * rate_per_rib)
            $polyBelt->value = $polyBelt->ribs * $polyBelt->rate_per_rib;
        });
    }

    /**
     * Calculate rate per rib based on section formula
     */
    public function calculateRatePerRib(): float
    {
        $formula = RateFormula::where('category', 'poly_belts')
            ->where('section', $this->section)
            ->first();

        if (!$formula) {
            \Log::info("No formula found for poly belt section: {$this->section}");
            return 0.0;
        }

        // Parse formula and calculate
        // Formula format: "size/25.4*0.59" for PK, "size/25.4*0.36" for PJ, etc.
        // Correct formula: rate_per_rib = (size ÷ divisor) × multiplier
        $formulaStr = $formula->formula;
        
        // Extract the divisor and multiplier from formula (e.g., 25.4 and 0.59 from "size/25.4*0.59")
        if (preg_match('/size\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
            $divisor = (float) $matches[1];
            $multiplier = (float) $matches[2];
            $result = $this->size / $divisor * $multiplier;
            
            \Log::info("Rate calculation for {$this->section}-{$this->size}: ({$this->size} ÷ {$divisor}) × {$multiplier} = {$result}");
            return $result;
        }
        
        // Fallback: try old format with ribs (for backward compatibility)
        if (preg_match('/ribs\/([0-9.]+)\*([0-9.]+)/', $formulaStr, $matches)) {
            $divisor = (float) $matches[1];
            $multiplier = (float) $matches[2];
            $result = $this->size / $divisor * $multiplier; // Still use size, not ribs
            
            \Log::info("Rate calculation (fallback) for {$this->section}-{$this->size}: ({$this->size} ÷ {$divisor}) × {$multiplier} = {$result}");
            return $result;
        }

        \Log::warning("Invalid formula format for {$this->section}: {$formulaStr}");
        return 0.0;
    }

    /**
     * Scope: Filter by section
     */
    public function scopeBySection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope: Low stock items (low ribs)
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('ribs', '<=', 'reorder_level')
            ->where('ribs', '>', 0);
    }

    /**
     * Scope: Out of stock items (no ribs)
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('ribs', 0);
    }

    /**
     * Get transactions for this product
     */
    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'product_id')
            ->where('category', 'poly_belts')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the rate formula for this section
     */
    public function rateFormula()
    {
        return $this->hasOne(RateFormula::class, 'section', 'section')
            ->where('category', 'poly_belts');
    }


    public function stockAlert()
{
    return $this->hasOne(StockAlertTracking::class, 'product_id')
        ->where('belt_type', 'ploy')
        ->where('is_active', true);
}
}