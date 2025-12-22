<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'product_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'rate',
        'description',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
        'rate' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product (polymorphic-like behavior)
     */
    public function getProductAttribute()
    {
        switch ($this->category) {
            case 'vee_belts':
                return VeeBelt::find($this->product_id);
            // Add other categories later
            default:
                return null;
        }
    }

    /**
     * Scopes
     */
    public function scopeForProduct($query, string $category, int $productId)
    {
        return $query->where('category', $category)
            ->where('product_id', $productId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
