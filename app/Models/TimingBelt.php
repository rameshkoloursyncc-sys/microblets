<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Calculate total value based on the timing belt formula:
     * value = (size * type_numeric_value * 450 * multiplier) + (size * total_mm * multiplier)
     */
    public function calculateValue()
    {
        // Get the multiplier for this section from rate_formulas
        $formula = \DB::table('rate_formulas')
            ->where('category', 'timing_belts')
            ->where('section', $this->section)
            ->where('is_active', 1)
            ->first();
        
        if (!$formula) {
            // Fallback to old calculation if no formula found
            $this->value = $this->total_mm * $this->rate;
            return;
        }
        
        $multiplier = (float) $formula->formula;
        $size = (float) $this->size;
        $totalMm = (float) $this->total_mm;
        
        // Convert type to numeric value
        $typeNumeric = $this->getTypeNumericValue();
        
        // Apply the formula: (size * type * 450 * multiplier) + (size * total_mm * multiplier)
        $part1 = $size * $typeNumeric * 450 * $multiplier;
        $part2 = $size * $totalMm * $multiplier;
        
        $this->value = $part1 + $part2;
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
