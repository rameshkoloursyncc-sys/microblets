<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Updating timing belt formulas on production...\n\n";

try {
    // Complete timing belt formula configuration
    $timingBeltFormulas = [
        // Commercial sections with their specific multipliers
        ['category' => 'timing_belts', 'section' => 'XL', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'L', 'formula' => '0.0045', 'is_active' => 1], // L has different multiplier
        ['category' => 'timing_belts', 'section' => 'H', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'XH', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'T5', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'T10', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => '3M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => '5M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => '8M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => '14M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'DL', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'DH', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'D5M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'D8M', 'formula' => '0.0094', 'is_active' => 1],
        
        // Neoprene sections with their specific multipliers
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-XL', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-L', 'formula' => '0.0045', 'is_active' => 1], // NEOPRENE-L has different multiplier
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-H', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-XH', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-T5', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-T10', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-3M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-5M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-8M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-14M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-DL', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-DH', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-D5M', 'formula' => '0.0094', 'is_active' => 1],
        ['category' => 'timing_belts', 'section' => 'NEOPRENE-D8M', 'formula' => '0.0094', 'is_active' => 1],
    ];

    echo "📊 Current timing belt formulas in database:\n";
    $existingFormulas = DB::table('rate_formulas')
        ->where('category', 'timing_belts')
        ->orderBy('section')
        ->get();
    
    foreach ($existingFormulas as $formula) {
        echo "  {$formula->section}: {$formula->formula}\n";
    }
    echo "\n";

    DB::beginTransaction();

    $updated = 0;
    $inserted = 0;
    $skipped = 0;

    foreach ($timingBeltFormulas as $formula) {
        $formula['created_at'] = now();
        $formula['updated_at'] = now();
        $formula['created_by'] = null;
        
        // Check if formula exists
        $existing = DB::table('rate_formulas')
            ->where('category', $formula['category'])
            ->where('section', $formula['section'])
            ->first();
            
        if ($existing) {
            // Update if formula is different
            if ($existing->formula !== $formula['formula']) {
                DB::table('rate_formulas')
                    ->where('id', $existing->id)
                    ->update([
                        'formula' => $formula['formula'],
                        'is_active' => $formula['is_active'],
                        'updated_at' => $formula['updated_at']
                    ]);
                echo "✅ Updated {$formula['section']}: {$existing->formula} → {$formula['formula']}\n";
                $updated++;
            } else {
                echo "⏭️  Skipped {$formula['section']}: already correct ({$formula['formula']})\n";
                $skipped++;
            }
        } else {
            // Insert new formula
            DB::table('rate_formulas')->insert($formula);
            echo "➕ Inserted {$formula['section']}: {$formula['formula']}\n";
            $inserted++;
        }
    }

    DB::commit();

    echo "\n📈 Summary:\n";
    echo "  ✅ Updated: {$updated} formulas\n";
    echo "  ➕ Inserted: {$inserted} formulas\n";
    echo "  ⏭️  Skipped: {$skipped} formulas (already correct)\n";
    echo "  📊 Total processed: " . ($updated + $inserted + $skipped) . " formulas\n\n";

    // Verify the update
    echo "🔍 Verifying updated formulas:\n";
    $verifyFormulas = DB::table('rate_formulas')
        ->where('category', 'timing_belts')
        ->orderBy('section')
        ->get();
    
    foreach ($verifyFormulas as $formula) {
        echo "  {$formula->section}: {$formula->formula} (Active: " . ($formula->is_active ? 'Yes' : 'No') . ")\n";
    }

    echo "\n🎉 Timing belt formulas updated successfully!\n";
    echo "\n📝 Formula explanation:\n";
    echo "  Formula: value = (size × type × 450 × multiplier) + (size × total_mm × multiplier)\n";
    echo "  - 450 is a fixed constant\n";
    echo "  - multiplier varies by section (L sections: 0.0045, others: 0.0094)\n";
    echo "  - type numeric value: 1 for FULL SLEEVE, 2 for HALF SLEEVE\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Failed to update timing belt formulas: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}