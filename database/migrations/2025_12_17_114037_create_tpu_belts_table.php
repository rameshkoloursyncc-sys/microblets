<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tpu_belts', function (Blueprint $table) {
            $table->id();
            $table->string('section'); // e.g., TS8M
            $table->string('width'); // Width in mm (e.g., 150)
            $table->decimal('meter', 10, 2)->default(0); // Meter quantity (inventory unit)
            $table->decimal('in_meter', 10, 2)->default(0); // IN meter tracking
            $table->decimal('out_meter', 10, 2)->default(0); // OUT meter tracking
            $table->decimal('rate', 10, 2); // Rate per unit (no formula)
            $table->decimal('value', 12, 2)->default(0); // Calculated: (rate*width/150)*meter
            $table->text('remark')->nullable();
            $table->string('sku')->unique(); // Auto-generated: TS8M-150-7M
            $table->string('category')->default('TPU Belts');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['section', 'width']);
            $table->index('section');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpu_belts');
    }
};