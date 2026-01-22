<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_alert_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('belt_type'); // vee, cogged, poly, tpu, timing, special
            $table->string('section'); // A, B, C, etc.
            $table->integer('product_id'); // Reference to specific product
            $table->string('product_sku')->nullable();
            $table->decimal('current_stock', 10, 2);
            $table->decimal('reorder_level', 10, 2);
            $table->decimal('stock_per_die', 10, 2)->default(1); // How much stock one die produces
            $table->integer('dies_needed')->default(1); // Calculated dies needed (rounded up)
            $table->boolean('alert_sent')->default(false); // Flag to track if alert was sent
            $table->timestamp('alert_sent_at')->nullable(); // When alert was sent
            $table->boolean('is_active')->default(true); // Active tracking
            $table->json('alert_history')->nullable(); // Track alert history
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['belt_type', 'section']);
            $table->index(['alert_sent', 'is_active']);
            $table->unique(['belt_type', 'section', 'product_id'], 'unique_product_tracking');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_alert_tracking');
    }
};
