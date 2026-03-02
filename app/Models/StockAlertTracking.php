<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockAlertTracking extends Model
{
    use HasFactory;

    protected $table = 'stock_alert_tracking';

    protected $fillable = [
        'belt_type',
        'section',
        'product_id',
        'product_sku',
        'current_stock',
        'reorder_level',
        'stock_per_die',
        'dies_needed',
        'alert_sent',
        'alert_sent_at',
        'is_active',
        'alert_history'
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'stock_per_die' => 'decimal:2',
        'dies_needed' => 'integer',
        'alert_sent' => 'boolean',
        'is_active' => 'boolean',
        'alert_sent_at' => 'datetime',
        'alert_history' => 'array'
    ];

    /**
     * Calculate dies needed based on stock deficit
     */
    public function calculateDiesNeeded()
    {
        if ($this->current_stock >= $this->reorder_level) {
            return 0;
        }

        $deficit = $this->reorder_level - $this->current_stock;
        $diesNeeded = ceil($deficit / $this->stock_per_die);
        
        $this->dies_needed = $diesNeeded;
        return $diesNeeded;
    }

    /**
     * Mark alert as sent
     */
    public function markAlertSent()
    {
        $history = $this->alert_history ?? [];
        $history[] = [
            'sent_at' => now()->toDateTimeString(),
            'stock_at_time' => $this->current_stock,
            'dies_needed' => $this->dies_needed
        ];

        $this->update([
            'alert_sent' => true,
            'alert_sent_at' => now(),
            'alert_history' => $history
        ]);
    }

    /**
     * Reset alert when stock is replenished
     */
    public function resetAlert()
    {
        $this->update([
            'alert_sent' => false,
            'alert_sent_at' => null
        ]);
    }

    /**
     * Check if stock is back above minimum and reset alert
     */
    public function checkAndResetIfReplenished($newStock)
    {
        $this->current_stock = $newStock;
        
        if ($newStock >= $this->reorder_level && $this->alert_sent) {
            $this->resetAlert();
        }
        // $this->resetAlert();
        $this->calculateDiesNeeded();
        $this->save();
    }

    /**
     * Scope for items needing alerts
     */
    public function scopeNeedsAlert($query)
    {
        return $query->where('alert_sent', false)
                    ->where('is_active', true)
                    ->whereRaw('current_stock < reorder_level');
    }

//     public function scopeNeedsAlert($query)
// {
//     return $query->whereRaw('1 = 0');
// }

    /**
     * Scope for items by belt type and section
     */
    public function scopeByBeltSection($query, $beltType, $section = null)
    {
        $query->where('belt_type', $beltType);
        
        if ($section) {
            $query->where('section', $section);
        }
        
        return $query;
    }
}
