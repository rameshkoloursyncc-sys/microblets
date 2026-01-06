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
        // Add missing timing belt rate formulas
        $timingBeltFormulas = [
            // Commercial sections
            ['category' => 'timing_belts', 'section' => '3M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => '5M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => '8M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => '14M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'DL', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'DH', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'D5M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'D8M', 'formula' => '0.0094', 'is_active' => 1],
            
            // Neoprene sections
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-3M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-5M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-8M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-14M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-DL', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-DH', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-D5M', 'formula' => '0.0094', 'is_active' => 1],
            ['category' => 'timing_belts', 'section' => 'NEOPRENE-D8M', 'formula' => '0.0094', 'is_active' => 1],
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
        // Remove the added timing belt formulas
        $sectionsToRemove = [
            '3M', '5M', '8M', '14M', 'DL', 'DH', 'D5M', 'D8M',
            'NEOPRENE-3M', 'NEOPRENE-5M', 'NEOPRENE-8M', 'NEOPRENE-14M',
            'NEOPRENE-DL', 'NEOPRENE-DH', 'NEOPRENE-D5M', 'NEOPRENE-D8M'
        ];
        
        DB::table('rate_formulas')
            ->where('category', 'timing_belts')
            ->whereIn('section', $sectionsToRemove)
            ->delete();
    }
};