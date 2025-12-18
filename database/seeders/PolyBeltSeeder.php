<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PolyBelt;
use Illuminate\Support\Facades\File;

class PolyBeltSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the sections and their corresponding JSON files
        $sections = [
            'PK' => 'PKProducts.json',
            'PL' => 'PLProducts.json',
        ];

        foreach ($sections as $section => $filename) {
            $filePath = resource_path("js/mock/{$filename}");
            
            if (!File::exists($filePath)) {
                $this->command->warn("File not found: {$filename}");
                continue;
            }

            $jsonData = File::get($filePath);
            $products = json_decode($jsonData, true);

            if (!$products) {
                $this->command->error("Invalid JSON in file: {$filename}");
                continue;
            }

            $this->command->info("Processing {$section} section from {$filename}...");
            $count = 0;

            foreach ($products as $product) {
                // Skip if already exists
                $existing = PolyBelt::where('section', $section)
                    ->where('size', (string)$product['size'])
                    ->first();

                if ($existing) {
                    continue;
                }

                // Create the poly belt record
                $polyBelt = PolyBelt::create([
                    'section' => $section,
                    'size' => (string)$product['size'],
                    'ribs' => $product['ribs'],
                    'reorder_level' => $product['reorder_level'] ?? 5,
                    'rate_per_rib' => $product['rate_per_rib'] ?? null, // Will be auto-calculated if null
                    'remark' => $product['remark'] ?? null,
                ]);

                $count++;
            }

            $this->command->info("Imported {$count} products for {$section} section");
        }

        $this->command->info('Poly belt seeding completed!');
    }
}