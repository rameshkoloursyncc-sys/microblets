<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MockJsonController extends Controller
{
    private function getMockPath(string $section): string
    {
        return resource_path("js/mock/{$section}Products.json");
    }

    // Get products from mock JSON
    public function getProducts(string $section): JsonResponse
    {
        $path = $this->getMockPath($section);
        
        if (!file_exists($path)) {
            return response()->json([]);
        }
        
        $content = file_get_contents($path);
        $products = json_decode($content, true);
        
        return response()->json($products ?: []);
    }

    // Save products to mock JSON
    public function saveProducts(Request $request, string $section): JsonResponse
    {
        $products = $request->input('products', []);
        $path = $this->getMockPath($section);
        
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($path, json_encode($products, JSON_PRETTY_PRINT));
        
        return response()->json(['success' => true]);
    }

    // Import JSON data (paste JSON feature)
    public function importJson(Request $request, string $section): JsonResponse
    {
        $data = $request->input('data', []);
        $mode = $request->input('mode', 'replace'); // 'replace' or 'append'
        
        $path = $this->getMockPath($section);
        
        // Get existing products if append mode
        $existingProducts = [];
        if ($mode === 'append' && file_exists($path)) {
            $content = file_get_contents($path);
            $existingProducts = json_decode($content, true) ?: [];
        }
        
        // Process imported data
        $baseId = count($existingProducts) > 0 
            ? max(array_column($existingProducts, 'id')) + 1 
            : 1;
        
        $newProducts = [];
        foreach ($data as $index => $item) {
            $newProducts[] = [
                'id' => $baseId + $index,
                'category' => $item['category'] ?? "{$section} Section",
                'name' => $item['name'] ?? $item['section'] ?? $section,
                'sku' => $item['sku'] ?? $item['size'] ?? '',
                'size' => $item['size'] ?? '',
                'stock' => $item['stock'] ?? $item['balanceStock'] ?? 0,
                'reorder_level' => $item['reorder_level'] ?? 5,
                'rate' => $item['rate'] ?? 0,
                'value' => $item['value'] ?? (($item['stock'] ?? $item['balanceStock'] ?? 0) * ($item['rate'] ?? 0)),
                'in_qty' => 0,
                'out_qty' => 0
            ];
        }
        
        // Merge or replace
        $finalProducts = $mode === 'append' 
            ? array_merge($existingProducts, $newProducts)
            : $newProducts;
        
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($path, json_encode($finalProducts, JSON_PRETTY_PRINT));
        
        return response()->json([
            'success' => true,
            'added' => count($newProducts),
            'total' => count($finalProducts)
        ]);
    }
}