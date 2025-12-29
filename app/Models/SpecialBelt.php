<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialBelt extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'size',
        'type',
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

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($specialBelt) {
            $specialBelt->calculateValue();
        });

        static::updating(function ($specialBelt) {
            // Recalculate value when relevant fields change
            if ($specialBelt->isDirty(['balance_stock', 'rate'])) {
                $specialBelt->calculateValue();
            }
        });
    }

    /**
     * Calculate total value
     */
    public function calculateValue()
    {
        $this->value = $this->balance_stock * $this->rate;
    }

    /**
     * Scope to filter by section
     */
    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope to filter by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('balance_stock', '<=', 'reorder_level');
    }

    /**
     * Scope for out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('balance_stock', '<=', 0);
    }

    /**
     * Get current stock
     */
    public function getCurrentStock()
    {
        return $this->balance_stock;
    }

    /**
     * Get stock unit
     */
    public function getStockUnit()
    {
        return 'pieces';
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