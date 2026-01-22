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
        Schema::create('die_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('belt_type'); // vee, cogged, poly, tpu, timing, special
            $table->string('section'); // A, B, SPA, etc.
            $table->decimal('stock_per_die', 8, 2)->default(30.00); // How much stock one die produces
            $table->text('notes')->nullable(); // Optional notes
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['belt_type', 'section']);
            
            // Index for faster lookups
            $table->index(['belt_type', 'section', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('die_configurations');
    }
};