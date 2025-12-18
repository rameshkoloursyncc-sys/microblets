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
        Schema::table('poly_belts', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['balance_stock', 'in_stock', 'out_stock', 'rate']);
            
            // Add new columns
            $table->integer('in_ribs')->default(0)->after('ribs'); // Cumulative IN ribs operations
            $table->integer('out_ribs')->default(0)->after('in_ribs'); // Cumulative OUT ribs operations
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('poly_belts', function (Blueprint $table) {
            // Add back old columns
            $table->integer('balance_stock')->default(0)->after('ribs');
            $table->integer('in_stock')->default(0)->after('balance_stock');
            $table->integer('out_stock')->default(0)->after('in_stock');
            $table->decimal('rate', 10, 2)->default(0)->after('rate_per_rib');
            
            // Drop new columns
            $table->dropColumn(['in_ribs', 'out_ribs']);
        });
    }
};