<?php

/**
 * Import Vee Belts JSON data to database
 * Run: php import_vee_belts.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\VeeBelt;
use Illuminate\Support\Facades\DB;

// Mapping of JSON files to section names
$veebeltsMapping = [
    'AProducts.json' => 'A',
    'BProducts.json' => 'B',
    'CProducts.json' => 'C',
    'DProducts.json' => 'D',
    'EProducts.json' => 'E',
    'SPAProducts.json' => 'SPA',
    'SPBProducts.json' => 'SPB',
    'SPCProducts.json' => 'SPC',
    'SPZProducts.json' => 'SPZ',
    '3VProducts.json' => '3V',
    '5VProducts.json' => '5V',
    '8VProducts.json' => '8V',
];

$mockDir = __DIR__ . '/resources/js/mock/';
$totalImported = 0;
$totalSkipped = 0;

echo "Starting Vee Belts import...\n\n";

foreach ($veebeltsMapping as $filename => $section) {
    $filepath = $mockDir . $filename;
    
    if (!file_exists($filepath)) {
        echo "⚠️  File not found: $filename\n";
        continue;
    }
    
    $jsonData = file_get_contents($filepath);
    $products = json_decode($jsonData, true);
    
    if (!is_array($products)) {
        echo "❌ Invalid JSON in $filename\n";
        continue;
    }
    
    echo "Processing $filename ($section Section) - " . count($products) . " products\n";
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($products as $product) {
        // Check if product already exists
        $existing = VeeBelt::where('section', $section)
            ->where('size', $product['size'])
            ->first();
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Create new product
        try {
            VeeBelt::create([
                'section' => $section,
                'size' => $product['size'],
                'balance_stock' => $product['stock'] ?? 0,
                'reorder_level' => $product['reorder_level'] ?? 5,
                'rate' => $product['rate'] ?? 0,
                'value' => ($product['stock'] ?? 0) * ($product['rate'] ?? 0),
                'remark' => $product['remark'] ?? '',
            ]);
            $imported++;
        } catch (\Exception $e) {
            echo "  ❌ Error importing {$section}-{$product['size']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "  ✅ Imported: $imported, Skipped: $skipped\n\n";
    $totalImported += $imported;
    $totalSkipped += $skipped;
}

echo "\n========================================\n";
echo "Import Complete!\n";
echo "Total Imported: $totalImported\n";
echo "Total Skipped: $totalSkipped\n";
echo "========================================\n";
