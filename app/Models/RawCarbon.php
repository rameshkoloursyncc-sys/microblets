<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RawCarbon extends Model
{
    use HasFactory;
     protected $table = 'raw_carbons';

    protected $fillable = [
        'section',
        'category',
        'packing',
        'balance_stock',
        'in_stock',
        'out_stock',
        'reorder_level',
        'rate',
        'value',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'balance_stock' => 'decimal:3',
        'in_stock' => 'decimal:3',
        'out_stock' => 'decimal:3',
        'reorder_level' => 'integer',
        'rate' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    protected $appends = ['sku'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        // Auto-calculate value before saving
        static::saving(function ($model) {
            $model->value = $model->balance_stock * $model->rate;
        });
    }

    public function getSkuAttribute(): string
    {
        return strtoupper($this->section) . '-' . $this->packing;
    }

    // public function calculateRate(): float
    // {
    //     $formula = RateFormula::where('category', 'rawcarbon')
    //         ->where('section', $this->section)
    //         ->where('is_active', true)
    //         ->first();

    //     if (!$formula) {
    //         return 0;
    //     }

    //     $formulaData = json_decode($formula->formula, true);
    //     $packing = (float) $this->packing;

    //     switch ($formulaData['type'] ?? 'multiply') {
    //         case 'multiply':
    //             return $packing * ($formulaData['multiplier'] ?? 1);
    //         case 'divide_multiply':
    //             $divisor = $formulaData['divisor'] ?? 1;
    //             $multiplier = $formulaData['multiplier'] ?? 1;
    //             return ($packing / $divisor) * $multiplier;
    //         default:
    //             return 0;
    //     }
    // }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transactions()
    {
        return InventoryTransaction::where('category', 'rawcarbon')
            ->where('product_id', $this->id)
            ->orderBy('created_at', 'desc');
    }

    public function scopeBySection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('balance_stock', '<=', 'reorder_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('balance_stock', 0);
    }

    public function stockAlert()
    {
        return $this->hasOne(StockAlertTracking::class, 'product_id')
            ->where('belt_type', 'rawcarbon')  // Changed from 'cogged_belts' to 'cogged'
            ->where('is_active', true);
    }
}
