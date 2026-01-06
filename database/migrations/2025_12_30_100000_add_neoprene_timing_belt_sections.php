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
        // This migration is safe to run in production
        // It only adds data, doesn't modify table structure
        
        // Check if we have any NEOPRENE section data that needs to be migrated
        $neopreneData = DB::table('timing_belts')->where('section', 'NEOPRENE')->get();
        
        if ($neopreneData->count() > 0) {
            // If there's existing NEOPRENE data, we'll leave it as is
            // New neoprene sections will be NEOPRENE-XL, NEOPRENE-L, etc.
            // Log message for debugging
        } else {
            // No existing NEOPRENE data found. Ready for new neoprene sections.
        }
        
        // Add some sample neoprene data if needed (optional)
        // This is commented out to avoid adding unwanted data in production
        /*
        $sampleNeopreneData = [
            [
                'section' => 'NEOPRENE-XL',
                'size' => '100',
                'type' => 'FULL SLEEVE',
                'total_mm' => 0,
                'rate' => 0,
                'value' => 0,
                'remark' => 'Sample neoprene XL',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        
        DB::table('timing_belts')->insert($sampleNeopreneData);
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove any NEOPRENE-* sections if needed
        DB::table('timing_belts')->where('section', 'like', 'NEOPRENE-%')->delete();
    }
};