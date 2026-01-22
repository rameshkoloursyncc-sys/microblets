<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TpuBelt extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'width',
        'meter',
        'in_meter',
        'out_meter',
        'rate',
        'value',
        'remark',
        'sku',
        'category',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'meter' => 'decimal:2',
        'in_meter' => 'decimal:2',
        'out_meter' => 'decimal:2',
        'rate' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tpuBelt) {
            // Auto-generate unique SKU if not provided
            if (empty($tpuBelt->sku)) {
                $baseSku = "{$tpuBelt->section}-{$tpuBelt->width}-{$tpuBelt->meter}M";
                
                // Check if SKU already exists and make it unique
                $counter = 1;
                $uniqueSku = $baseSku;
                while (static::where('sku', $uniqueSku)->exists()) {
                    $uniqueSku = $baseSku . "-" . $counter;
                    $counter++;
                }
                
                $tpuBelt->sku = $uniqueSku;
            }
            
            // Auto-calculate value: (rate*width/150)*meter
            $tpuBelt->calculateValue();
        });

        static::updating(function ($tpuBelt) {
            // Recalculate value when relevant fields change
            if ($tpuBelt->isDirty(['rate', 'width', 'meter'])) {
                $tpuBelt->calculateValue();
            }
        });
    }

    /**
     * Calculate value using formula: (rate*width/150)*meter
     */
    public function calculateValue()
    {
        if ($this->rate && $this->width && $this->meter) {
            $this->value = ($this->rate * $this->width / 150) * $this->meter;
        } else {
            $this->value = 0;
        }
    }

    /**
     * Scope to filter by section
     */
    public function scopeBySection($query, $section)
    {
        if ($section) {
            return $query->where('section', $section);
        }
        return $query;
    }

    /**
     * Get the user who created this record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function stockALert()
    {
        return $this->hasOne(StockAlertTracking::class, 'product_id')
        ->where('belts_type', 'tpu')
        ->where("is_active", true);
    }
}