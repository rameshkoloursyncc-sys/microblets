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
        Schema::create('poly_belts', function (Blueprint $table) {
            $table->id();
            $table->string('section', 10)->index(); // PJ, PK, PL, PM, PH, DPL, DPK
            $table->string('size', 20)->index(); // Belt size/length
            $table->integer('ribs')->default(0); // Number of ribs (this is the inventory unit)
            $table->integer('in_ribs')->default(0); // Cumulative IN ribs operations
            $table->integer('out_ribs')->default(0); // Cumulative OUT ribs operations
            $table->integer('reorder_level')->default(5); // Minimum ribs level
            $table->decimal('rate_per_rib', 10, 2)->default(0); // Rate per rib
            $table->decimal('value', 12, 2)->default(0); // ribs * rate_per_rib
            $table->text('remark')->nullable();
            $table->string('sku', 50)->unique();
            $table->string('category', 50)->default('Poly Belts');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['section', 'size']);
            $table->index('ribs');
            $table->index('reorder_level');
            
            // Unique constraint
            $table->unique(['section', 'size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poly_belts');
    }
};