<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vee_belts', function (Blueprint $table) {
            $table->id();
            $table->string('section', 10)->index(); // A, B, C, D, E, SPA, SPB, SPC, SPZ, 3V, 5V, 8V
            $table->string('size', 20); // 18, 85, 1000, etc.
            $table->integer('balance_stock')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('value', 10, 2)->default(0); // Calculated: balance_stock * rate
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Unique constraint: one record per section-size combination
            $table->unique(['section', 'size']);
            
            // Indexes for faster queries
            $table->index('balance_stock');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vee_belts');
    }
};
