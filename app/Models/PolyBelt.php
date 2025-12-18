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

            // Auto-calculate rate_per_rib if not provided (only if not manually set)
            if (empty($polyBelt->rate_per_rib)) {
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
            return 0.0;
        }

        // Parse formula and calculate
        // Formula format: "ribs/25.4*0.59" for PK, "ribs/25.4*0.36" for PJ, etc.
        $formulaStr = $formula->formula;
        
        // Extract the multiplier from formula (e.g., 0.59 from "ribs/25.4*0.59")
        if (preg_match('/ribs\/25\.4\*([0-9.]+)/', $formulaStr, $matches)) {
            $multiplier = (float) $matches[1];
            return $this->ribs / 25.4 * $multiplier;
        }

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
}