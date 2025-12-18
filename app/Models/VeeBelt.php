<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VeeBelt extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'size',
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
        'balance_stock' => 'integer',
        'in_stock' => 'integer',
        'out_stock' => 'integer',
        'reorder_level' => 'integer',
        'rate' => 'decimal:2',
        'value' => 'decimal:2',
    ];

    protected $appends = ['sku', 'category'];

    /**
     * Boot method to auto-set created_by and updated_by
     */
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

    /**
     * Generate SKU attribute
     */
    public function getSkuAttribute(): string
    {
        return strtoupper($this->section) . '-' . $this->size;
    }

    /**
     * Generate category attribute
     */
    public function getCategoryAttribute(): string
    {
        return $this->section . ' Section';
    }

    /**
     * Calculate rate based on formula
     */
    public function calculateRate(): float
    {
        $formula = RateFormula::where('category', 'vee_belts')
            ->where('section', $this->section)
            ->where('is_active', true)
            ->first();

        if (!$formula) {
            return 0;
        }

        $formulaData = json_decode($formula->formula, true);
        $size = (float) $this->size;

        // Handle different formula types
        switch ($formulaData['type'] ?? 'multiply') {
            case 'multiply':
                // Example: A Section = 1.05 * size
                return $size * ($formulaData['multiplier'] ?? 1);

            case 'divide_multiply':
                // Example: 5V Section = (size / 10) * 1.87
                $divisor = $formulaData['divisor'] ?? 1;
                $multiplier = $formulaData['multiplier'] ?? 1;
                return ($size / $divisor) * $multiplier;

            case 'custom':
                // Custom expression evaluation (use with caution)
                $expression = str_replace('size', $size, $formulaData['expression']);
                return eval("return $expression;");

            default:
                return 0;
        }
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

    public function transactions()
    {
        return InventoryTransaction::where('category', 'vee_belts')
            ->where('product_id', $this->id)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scopes
     */
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
}
