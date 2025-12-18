<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50); // vee_belts, tpu_belts, etc.
            $table->string('section', 10); // A, B, C, D, E, SPA, 5V, etc.
            $table->text('formula'); // JSON: {"type": "multiply", "multiplier": 1.05} or {"type": "custom", "expression": "size/10*1.87"}
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Unique constraint: one formula per category-section
            $table->unique(['category', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_formulas');
    }
};
