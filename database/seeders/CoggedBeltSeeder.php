<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoggedBelt;
use Illuminate\Support\Facades\File;

class CoggedBeltSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the sections and their corresponding JSON files
        $sections = [
            'AX' => 'AXProducts.json',
            'BX' => 'BXProducts.json', 
            'CX' => 'CXProducts.json',
            'XPA' => 'XPAProducts.json',
            'XPB' => 'XPBProducts.json',
            'XPC' => 'XPCProducts.json',
            'XPZ' => 'XPZProducts.json',
            '3VX' => '3VXProducts.json',
            '5VX' => '5VXProducts.json',
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
                $existing = CoggedBelt::where('section', $section)
                    ->where('size', (string)$product['size'])
                    ->first();

                if ($existing) {
                    continue;
                }

                // Create the cogged belt record
                $coggedBelt = CoggedBelt::create([
                    'section' => $section,
                    'size' => (string)$product['size'],
                    'balance_stock' => $product['stock'] ?? 0,
                    'reorder_level' => $product['reorder_level'] ?? 5,
                    'rate' => $product['rate'] ?? null, // Will be auto-calculated if null
                    'remark' => $product['remark'] ?? null,
                ]);

                $count++;
            }

            $this->command->info("Imported {$count} products for {$section} section");
        }

        $this->command->info('Cogged belt seeding completed!');
    }
}