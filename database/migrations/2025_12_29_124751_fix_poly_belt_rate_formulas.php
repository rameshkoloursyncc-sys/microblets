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
        // Update poly belt rate formulas to use 'size' instead of 'ribs'
        DB::table('rate_formulas')
            ->where('category', 'poly_belts')
            ->update([
                'formula' => DB::raw("REPLACE(formula, 'ribs/', 'size/')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert poly belt rate formulas back to use 'ribs'
        DB::table('rate_formulas')
            ->where('category', 'poly_belts')
            ->update([
                'formula' => DB::raw("REPLACE(formula, 'size/', 'ribs/')")
            ]);
    }
};
