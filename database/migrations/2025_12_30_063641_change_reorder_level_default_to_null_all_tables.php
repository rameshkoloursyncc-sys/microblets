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
        // List of all belt tables that have reorder_level column
        $tables = [
            'vee_belts',
            'cogged_belts', 
            'poly_belts',
            'tpu_belts',
            'timing_belts',
            'special_belts'
        ];

        foreach ($tables as $table) {
            // Check if table exists and has reorder_level column
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'reorder_level')) {
                // Change default value to NULL and allow NULL values
                Schema::table($table, function (Blueprint $table) {
                    $table->integer('reorder_level')->nullable()->default(null)->change();
                });
                
                // Update existing records: set reorder_level to NULL where it's currently 5 (the old default)
                // This allows users to selectively enable tracking by setting values > 1
                DB::table($table)->where('reorder_level', 5)->update(['reorder_level' => null]);
                
                echo "Updated {$table} - changed reorder_level default to NULL\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'vee_belts',
            'cogged_belts',
            'poly_belts', 
            'tpu_belts',
            'timing_belts',
            'special_belts'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'reorder_level')) {
                // Revert back to default 5
                Schema::table($table, function (Blueprint $table) {
                    $table->integer('reorder_level')->nullable()->default(5)->change();
                });
                
                // Update NULL values back to 5
                DB::table($table)->whereNull('reorder_level')->update(['reorder_level' => 5]);
                
                echo "Reverted {$table} - changed reorder_level default back to 5\n";
            }
        }
    }
};