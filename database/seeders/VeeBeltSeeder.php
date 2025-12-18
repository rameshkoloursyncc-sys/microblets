<?php

namespace Database\Seeders;

use App\Models\VeeBelt;
use Illuminate\Database\Seeder;

class VeeBeltSeeder extends Seeder
{
    /**
     * Seed vee belts from JSON files
     */
    public function run(): void
    {
        $this->command->info('Seeding Vee Belts...');

        // Mapping of JSON files to section names
        $veebeltsMapping = [
            'AProducts.json' => 'A',
            'BProducts.json' => 'B',
            'CProducts.json' => 'C',
            'DProducts.json' => 'D',
            'EProducts.json' => 'E',
            'SPAProducts.json' => 'SPA',
            'SPA_products.json' => 'SPA', // Alternative filename
            'SPBProducts.json' => 'SPB',
            'SPCProducts.json' => 'SPC',
            'SPZProducts.json' => 'SPZ',
            '3VProducts.json' => '3V',
            '5VProducts.json' => '5V',
            '8VProducts.json' => '8V',
        ];

        $mockDir = resource_path('js/mock/');
        $totalImported = 0;
        $totalUpdated = 0;

        foreach ($veebeltsMapping as $filename => $section) {
            $filepath = $mockDir . $filename;
            
            if (!file_exists($filepath)) {
                continue;
            }
            
            $jsonData = file_get_contents($filepath);
            $products = json_decode($jsonData, true);
            
            if (!is_array($products)) {
                $this->command->warn("Invalid JSON in $filename");
                continue;
            }
            
            $this->command->info("Processing $section Section - " . count($products) . " products");
            
            foreach ($products as $product) {
                $data = [
                    'section' => $section,
                    'size' => (string) ($product['size'] ?? ''),
                    'balance_stock' => $product['balance_stock'] ?? $product['stock'] ?? 0,
                    'in_stock' => $product['in_stock'] ?? 0,
                    'out_stock' => $product['out_stock'] ?? 0,
                    'reorder_level' => $product['reorder_level'] ?? 5,
                    'rate' => $product['rate'] ?? 0,
                    'value' => ($product['balance_stock'] ?? $product['stock'] ?? 0) * ($product['rate'] ?? 0),
                    'remark' => $product['remark'] ?? '',
                ];

                $existing = VeeBelt::where('section', $section)
                    ->where('size', $data['size'])
                    ->first();

                if ($existing) {
                    $existing->update($data);
                    $totalUpdated++;
                } else {
                    VeeBelt::create($data);
                    $totalImported++;
                }
            }
        }

        $this->command->info("✅ Seeding complete!");
        $this->command->info("   Imported: $totalImported");
        $this->command->info("   Updated: $totalUpdated");
        $this->command->info("   Total: " . ($totalImported + $totalUpdated));
    }
}
