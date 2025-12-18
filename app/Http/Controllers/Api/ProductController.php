<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'section' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'dimension' => 'nullable|string|max:255',
            'stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'items_per_sleve' => 'nullable|integer|min:1',
            'rate' => 'nullable|numeric|min:0',
            'value' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create($data);

        return response()->json($product, 201);
    }
    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'section' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'dimension' => 'nullable|string|max:255',
            'stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'items_per_sleve' => 'nullable|integer|min:1',
            'rate' => 'nullable|numeric|min:0',
            'value' => 'nullable|numeric|min:0',
        ]);

        $product->update($data);

        return response()->json($product);
    }
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'data' => 'required|array',
            'data.*.name' => 'required|string|max:255',
            'data.*.section' => 'nullable|string|max:255',
            'data.*.size' => 'nullable|string|max:255',
            'data.*.stock' => 'nullable|integer|min:0',
            'data.*.rate' => 'nullable|numeric|min:0',
            'data.*.value' => 'nullable|numeric|min:0',
        ]);

        $products = [];
        $errors = [];

        foreach ($request->data as $index => $productData) {
            try {
                // Generate SKU if not provided
                if (empty($productData['sku'])) {
                    $section = $productData['section'] ?? 'UNKNOWN';
                    $size = $productData['size'] ?? 'UNKNOWN';
                    $productData['sku'] = strtoupper($section) . '-' . $size . '-' . time() . '-' . $index;
                }

                // Check for duplicate SKU
                if (Product::where('sku', $productData['sku'])->exists()) {
                    $productData['sku'] = $productData['sku'] . '-' . uniqid();
                }

                $product = Product::create([
                    'name' => $productData['name'],
                    'sku' => $productData['sku'],
                    'section' => $productData['section'] ?? null,
                    'size' => $productData['size'] ?? null,
                    'stock' => $productData['stock'] ?? 0,
                    'dimension' => $productData['dimension'] ?? null,
                    'reorder_level' => $productData['reorder_level'] ?? null,
                    'items_per_sleve' => $productData['items_per_sleve'] ?? null,
                    'rate' => $productData['rate'] ?? 0,
                    'value' => $productData['value'] ?? 0,
                ]);

                $products[] = $product;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'data' => $productData,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'created' => count($products),
            'errors' => $errors,
            'products' => $products
        ], 201);
    }
}
