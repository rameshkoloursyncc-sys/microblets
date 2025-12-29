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
        Schema::create('timing_belts', function (Blueprint $table) {
            $table->id();
            $table->string('section', 10); // XL, L, H, XH, T5, T10, 5M, 8M, 14M, DL, DH, D5M, D8M
            $table->string('size', 20); // Belt size
            $table->string('category', 20); // Commercial, Neoprene
            
            // Commercial fields
            $table->string('type', 20)->nullable(); // 1 = FULL SLEEVE, 2 = HALF SLEEVE (for Commercial)
            $table->decimal('mm', 10, 2)->nullable(); // Individual piece length in mm (for Commercial)
            $table->decimal('total_mm', 12, 2)->nullable(); // Total inventory in mm (for Commercial)
            $table->decimal('in_mm', 12, 2)->default(0); // IN mm tracking (for Commercial)
            $table->decimal('out_mm', 12, 2)->default(0); // OUT mm tracking (for Commercial)
            
            // Neoprene fields
            $table->integer('full_sleeve')->nullable(); // Number of full sleeves (for Neoprene)
            $table->integer('in_sleeve')->default(0); // IN sleeve tracking (for Neoprene)
            $table->integer('out_sleeve')->default(0); // OUT sleeve tracking (for Neoprene)
            $table->decimal('rate_per_sleeve', 10, 2)->nullable(); // Rate per sleeve (for Neoprene)
            
            // Common fields
            $table->integer('reorder_level')->default(5);
            $table->decimal('rate', 10, 2)->default(0); // Rate per mm (for Commercial) or rate per sleeve (for Neoprene)
            $table->decimal('total_value', 12, 2)->default(0); // Calculated value
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes
            $table->index('section');
            $table->index('category');
            $table->index(['total_mm', 'full_sleeve']);
            $table->unique(['section', 'size', 'type', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timing_belts');
    }
};
