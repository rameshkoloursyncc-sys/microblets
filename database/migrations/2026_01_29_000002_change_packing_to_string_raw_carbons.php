<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_carbons', function (Blueprint $table) {
            $table->id();

            // Core identifiers
            $table->string('section', 50);                  // item code / description
            $table->string('category')->default('rawcarbon');

            // Inventory fields
            $table->string('packing', 20);                  // supports kg, ltr, bottle, drum, etc.
            $table->decimal('balance_stock', 10, 2)->default(0);
            $table->decimal('in_stock', 10, 2)->default(0);
            $table->decimal('out_stock', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);

            // Financials
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('value', 14, 2)->default(0);

            // Meta
            $table->text('remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Uniqueness constraint
            $table->unique(['section', 'packing']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_carbons');
    }
};
