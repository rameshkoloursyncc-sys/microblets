<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns exist before trying to drop them
        $columns = Schema::getColumnListing('poly_belts');
        
        Schema::table('poly_belts', function (Blueprint $table) use ($columns) {
            // Only drop columns if they exist
            $columnsToDrop = [];
            if (in_array('balance_stock', $columns)) $columnsToDrop[] = 'balance_stock';
            if (in_array('in_stock', $columns)) $columnsToDrop[] = 'in_stock';
            if (in_array('out_stock', $columns)) $columnsToDrop[] = 'out_stock';
            if (in_array('rate', $columns)) $columnsToDrop[] = 'rate';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Only add columns if they don't exist
            if (!in_array('in_ribs', $columns)) {
                $table->integer('in_ribs')->default(0)->after('ribs');
            }
            if (!in_array('out_ribs', $columns)) {
                $table->integer('out_ribs')->default(0)->after('in_ribs');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = Schema::getColumnListing('poly_belts');
        
        Schema::table('poly_belts', function (Blueprint $table) use ($columns) {
            // Add back old columns if they don't exist
            if (!in_array('balance_stock', $columns)) {
                $table->integer('balance_stock')->default(0)->after('ribs');
            }
            if (!in_array('in_stock', $columns)) {
                $table->integer('in_stock')->default(0)->after('balance_stock');
            }
            if (!in_array('out_stock', $columns)) {
                $table->integer('out_stock')->default(0)->after('in_stock');
            }
            if (!in_array('rate', $columns)) {
                $table->decimal('rate', 10, 2)->default(0)->after('rate_per_rib');
            }
            
            // Drop new columns if they exist
            $columnsToDrop = [];
            if (in_array('in_ribs', $columns)) $columnsToDrop[] = 'in_ribs';
            if (in_array('out_ribs', $columns)) $columnsToDrop[] = 'out_ribs';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};