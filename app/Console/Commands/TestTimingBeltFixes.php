<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimingBelt;
use Illuminate\Support\Facades\DB;

class TestTimingBeltFixes extends Command
{
    protected $signature = 'test:timing-belt-fixes';
    protected $description = 'Test timing belt fixes for rate calculation and sleeve operations';

    public function handle()
    {
        $this->info('🧪 Testing Timing Belt Fixes');
        $this->newLine();

        // Test 1: Check rate formulas
        $this->info('Test 1: Checking rate formulas in database');
        $this->line('-------------------------------------------');

        $formulas = DB::table('rate_formulas')
            ->where('category', 'timing_belts')
            ->where('is_active', 1)
            ->get();

        $this->line("Found {$formulas->count()} active timing belt formulas:");

        foreach ($formulas as $formula) {
            $this->line("- Section: {$formula->section}, Formula: {$formula->formula}");
        }

        if ($formulas->count() > 0) {
            $this->info('✅ Rate formulas exist');
        } else {
            $this->error('❌ No rate formulas found - need to set up formulas');
        }

        $this->newLine();

        // Test 2: Test value calculation
        $this->info('Test 2: Testing value calculation');
        $this->line('----------------------------------');

        try {
            // Create a test timing belt
            $timingBelt = new TimingBelt([
                'section' => 'XL',
                'size' => '150',
                'type' => '18',
                'total_mm' => 1000.00,
            ]);

            // Calculate value
            $timingBelt->calculateValue();

            $this->line("Section: {$timingBelt->section}");
            $this->line("Size: {$timingBelt->size}");
            $this->line("Type: {$timingBelt->type}");
            $this->line("Total MM: {$timingBelt->total_mm}");
            $this->line("Calculated Rate: {$timingBelt->rate}");
            $this->line("Calculated Value: {$timingBelt->value}");

            if ($timingBelt->rate > 0) {
                $this->info('✅ Rate calculation working correctly');
            } else {
                $this->error('❌ Rate is still zero - check formula setup');
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        $this->newLine();

        // Test 3: Check table structure
        $this->info('Test 3: Checking timing belt table structure');
        $this->line('---------------------------------------------');

        try {
            $columns = DB::select("DESCRIBE timing_belts");

            $requiredFields = ['full_sleeve', 'in_sleeve', 'out_sleeve', 'rate_per_sleeve'];
            $foundFields = [];

            foreach ($columns as $column) {
                if (in_array($column->Field, $requiredFields)) {
                    $foundFields[] = $column->Field;
                }
            }

            $this->line("Required sleeve fields: " . implode(', ', $requiredFields));
            $this->line("Found sleeve fields: " . implode(', ', $foundFields));

            if (count($foundFields) === count($requiredFields)) {
                $this->info('✅ All sleeve fields exist in database');
            } else {
                $this->error('❌ Missing sleeve fields in database');
                $missing = array_diff($requiredFields, $foundFields);
                $this->error('Missing: ' . implode(', ', $missing));
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('🏁 Test completed!');

        return 0;
    }
}
