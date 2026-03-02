<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_date',
        
        // Finished Goods
        'finished_total_products',
        'finished_in_stock',
        'finished_low_stock',
        'finished_out_of_stock',
        'finished_total_value',
        'vee_belts_value',
        'cogged_belts_value',
        'poly_belts_value',
        'tpu_belts_value',
        'timing_belts_value',
        'special_belts_value',
        
        // Raw Materials Summary
        'raw_total_materials',
        'raw_available',
        'raw_low_stock',
        'raw_out_of_stock',
        'raw_total_value',
        
        // Raw Materials by Category
        'raw_carbon_value',
        'raw_chemical_value',
        'raw_cord_all_value',
        'raw_fabric_all_value',
        'raw_oil_value',
        'raw_others_value',
        'raw_resin_value',
        'raw_rubber_value',
        'raw_tpu_value',
        'raw_fibre_glass_cord_value',
        'raw_steel_wire_value',
        'raw_packing_value',
        'raw_open_value',
        'raw_category_values',
        
        // Other data
        'die_requirements',
        'total_alerts',
        'complete_data',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'raw_category_values' => 'array',
        'die_requirements' => 'array',
        'complete_data' => 'array',
        
        // Cast decimals
        'finished_total_value' => 'decimal:2',
        'vee_belts_value' => 'decimal:2',
        'cogged_belts_value' => 'decimal:2',
        'poly_belts_value' => 'decimal:2',
        'tpu_belts_value' => 'decimal:2',
        'timing_belts_value' => 'decimal:2',
        'special_belts_value' => 'decimal:2',
        'raw_total_value' => 'decimal:2',
        'raw_carbon_value' => 'decimal:2',
        'raw_chemical_value' => 'decimal:2',
        'raw_cord_all_value' => 'decimal:2',
        'raw_fabric_all_value' => 'decimal:2',
        'raw_oil_value' => 'decimal:2',
        'raw_others_value' => 'decimal:2',
        'raw_resin_value' => 'decimal:2',
        'raw_rubber_value' => 'decimal:2',
        'raw_tpu_value' => 'decimal:2',
        'raw_fibre_glass_cord_value' => 'decimal:2',
        'raw_steel_wire_value' => 'decimal:2',
        'raw_packing_value' => 'decimal:2',
        'raw_open_value' => 'decimal:2',
    ];
}
