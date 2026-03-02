<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();
            
            // Finished Goods Summary
            $table->integer('finished_total_products')->default(0);
            $table->integer('finished_in_stock')->default(0);
            $table->integer('finished_low_stock')->default(0);
            $table->integer('finished_out_of_stock')->default(0);
            $table->decimal('finished_total_value', 15, 2)->default(0);
            
            // Finished Goods by Category
            $table->decimal('vee_belts_value', 15, 2)->default(0);
            $table->decimal('cogged_belts_value', 15, 2)->default(0);
            $table->decimal('poly_belts_value', 15, 2)->default(0);
            $table->decimal('tpu_belts_value', 15, 2)->default(0);
            $table->decimal('timing_belts_value', 15, 2)->default(0);
            $table->decimal('special_belts_value', 15, 2)->default(0);
            
            // Raw Materials Summary
            $table->integer('raw_total_materials')->default(0);
            $table->integer('raw_available')->default(0);
            $table->integer('raw_low_stock')->default(0);
            $table->integer('raw_out_of_stock')->default(0);
            $table->decimal('raw_total_value', 15, 2)->default(0);
            
            // Raw Materials by Category - Individual columns for all categories
            $table->decimal('raw_carbon_value', 15, 2)->default(0);
            $table->decimal('raw_chemical_value', 15, 2)->default(0);
            $table->decimal('raw_cord_all_value', 15, 2)->default(0); // Combined cord value
            $table->decimal('raw_fabric_all_value', 15, 2)->default(0); // Combined fabric value
            $table->decimal('raw_oil_value', 15, 2)->default(0);
            $table->decimal('raw_others_value', 15, 2)->default(0);
            $table->decimal('raw_resin_value', 15, 2)->default(0);
            $table->decimal('raw_rubber_value', 15, 2)->default(0);
            $table->decimal('raw_tpu_value', 15, 2)->default(0);
            $table->decimal('raw_fibre_glass_cord_value', 15, 2)->default(0);
            $table->decimal('raw_steel_wire_value', 15, 2)->default(0);
            $table->decimal('raw_packing_value', 15, 2)->default(0);
            $table->decimal('raw_open_value', 15, 2)->default(0);
            
            // Raw Materials by Category (also store as JSON for flexibility and subcategories)
            $table->json('raw_category_values')->nullable();
            
            // Die Requirements (store as JSON)
            $table->json('die_requirements')->nullable();
            
            // Low Stock Alerts Count
            $table->integer('total_alerts')->default(0);
            
            // Complete snapshot data (for detailed queries)
            $table->json('complete_data')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_snapshots');
    }
};
