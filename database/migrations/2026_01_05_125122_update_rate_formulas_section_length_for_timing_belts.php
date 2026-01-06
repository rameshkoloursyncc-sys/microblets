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
        // Increase section column length to accommodate NEOPRENE-XL, NEOPRENE-XH, etc.
        Schema::table('rate_formulas', function (Blueprint $table) {
            $table->string('section', 20)->change();
        });
        
        // Add timing belt rate formulas
        $timingBeltFormulas = [
            ['category' => 'timing_belts', 'section' => 'XL', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'L', 'formula' => '0.0045', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'H', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'XH', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'T5', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'T10', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-XL', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-L', 'formula' => '0.0045', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-H', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-XH', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-T5', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-T10', 'formula' => '0.0094', 'is_active' => 1],
        ];

        foreach ($timingBeltFormulas as $formula) {
            $formula['created_at'] = now();
            $formula['updated_at'] = now();
            $formula['created_by'] = null;
            
            // Insert only if not exists
            $exists = DB::table('rate_formulas')
                ->where('category', $formula['category'])
                ->where('section', $formula['section'])
                ->exists();
                
            if (!$exists) {
                DB::table('rate_formulas')->insert($formula);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove timing belt formulas
        DB::table('rate_formulas')->where('category', 'timing_belts')->delete();
        
        // Revert section column length
        Schema::table('rate_formulas', function (Blueprint $table) {
            $table->string('section', 10)->change();
        });
    }
};