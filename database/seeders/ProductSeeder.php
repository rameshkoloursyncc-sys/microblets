<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseProducts = [
            [
                'name' => 'Timing Belt',
                'sku' => 'L',
                'dimension' => 98,
                'reorder_level' => 100,
                'items_per_sleve' => 450,
                'rate' => 1.25,
                'stock' => 2,
            ],
            [
                'name' => 'C Section',
                'sku' => 'C',
                'dimension' => 35,
                'reorder_level' => 100,
                'items_per_sleve' => null,
                'rate' => 1.25,
                'stock' => 285,
            ],
            [
                'name' => 'SPA Section',
                'sku' => 'SPA',
                'dimension' => 800,
                'reorder_level' => 100,
                'items_per_sleve' => null,
                'rate' => 1.25,
                'stock' => 9455,
            ],
        ];

        foreach ($baseProducts as $base) {
            for ($i = 0; $i < 100; $i++) {

                $dimension = rand(10, 1000);
                $reorder_level = rand(50, 200);
                $rate = round(rand(50, 2000) / 100, 2); // 0.50 to 20.00
                $stock = rand(1, 10000);

                // Set items_per_sleve only for Timing Belt
                $items_per_sleve = $base['name'] === 'Timing Belt' ? rand(1, 500) : null;

                // Calculate value only if items_per_sleve exists
                $value = $items_per_sleve ? $stock * $items_per_sleve * $rate : 0;

                Product::create([
                    'name' => $base['name'],
                    'sku' => $base['sku'],
                    'dimension' => $dimension,
                    'stock' => $stock,
                    'reorder_level' => $reorder_level,
                    'items_per_sleve' => $items_per_sleve,
                    'rate' => $rate,
                    'value' => $value,
                ]);
            }
        }
    }
}
