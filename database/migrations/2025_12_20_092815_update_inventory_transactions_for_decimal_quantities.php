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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // Change quantity, stock_before, and stock_after to decimal to support TPU belts
            $table->decimal('quantity', 10, 2)->nullable()->change();
            $table->decimal('stock_before', 10, 2)->change();
            $table->decimal('stock_after', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            // Revert back to integer
            $table->integer('quantity')->nullable()->change();
            $table->integer('stock_before')->change();
            $table->integer('stock_after')->change();
        });
    }
};