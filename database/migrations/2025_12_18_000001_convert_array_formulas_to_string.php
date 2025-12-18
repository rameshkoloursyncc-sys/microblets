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
        // Convert any existing array formulas to string format
        $formulas = DB::table('rate_formulas')->get();
        
        foreach ($formulas as $formula) {
            $formulaData = json_decode($formula->formula, true);
            
            // If it's an array (old format), convert to string
            if (is_array($formulaData)) {
                $newFormula = '';
                
                if ($formula->category === 'poly_belts') {
                    // Poly belts: ribs/25.4*multiplier
                    $divisor = 25.4;
                    $multiplier = $formulaData['multiplier'] ?? 1;
                    $newFormula = "ribs/{$divisor}*{$multiplier}";
                } else {
                    // Other belt types: size/divisor*multiplier
                    if ($formulaData['type'] === 'divide_multiply') {
                        $divisor = $formulaData['divisor'] ?? 10;
                        $multiplier = $formulaData['multiplier'] ?? 1;
                        $newFormula = "size/{$divisor}*{$multiplier}";
                    } else {
                        // Simple multiply
                        $multiplier = $formulaData['multiplier'] ?? 1;
                        $newFormula = "size/1*{$multiplier}";
                    }
                }
                
                // Update the formula
                DB::table('rate_formulas')
                    ->where('id', $formula->id)
                    ->update(['formula' => $newFormula]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we're converting data format
    }
};