<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DieConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DieConfigurationController extends Controller
{
    /**
     * Get all die configurations
     */
    public function index()
    {
        try {
            $configurations = DieConfiguration::getAllGrouped();
            
            return response()->json([
                'success' => true,
                'data' => $configurations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching die configurations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update or create die configuration
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'belt_type' => 'required|string|in:vee,cogged,poly,tpu,timing,special,rawcarbon',
                'section' => 'required|string|max:20',
                'stock_per_die' => 'required|numeric|min:0.01|max:9999.99',
                'notes' => 'nullable|string|max:500'
            ]);

            $configuration = DieConfiguration::setDieConfiguration(
                $validated['belt_type'],
                $validated['section'],
                $validated['stock_per_die'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Die configuration saved successfully',
                'data' => $configuration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving die configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update multiple configurations at once
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'configurations' => 'required|array',
                'configurations.*.belt_type' => 'required|string|in:vee,cogged,poly,tpu,timing,special',
                'configurations.*.section' => 'required|string|max:20',
                'configurations.*.stock_per_die' => 'required|numeric|min:0.01|max:9999.99',
                'configurations.*.notes' => 'nullable|string|max:500'
            ]);

            $updated = [];
            foreach ($validated['configurations'] as $config) {
                $configuration = DieConfiguration::setDieConfiguration(
                    $config['belt_type'],
                    $config['section'],
                    $config['stock_per_die'],
                    $config['notes'] ?? null
                );
                $updated[] = $configuration;
            }

            return response()->json([
                'success' => true,
                'message' => 'Die configurations updated successfully',
                'data' => $updated,
                'count' => count($updated)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating die configurations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete die configuration
     */
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'belt_type' => 'required|string',
                'section' => 'required|string'
            ]);

            $configuration = DieConfiguration::where('belt_type', $validated['belt_type'])
                ->where('section', $validated['section'])
                ->first();

            if (!$configuration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Die configuration not found'
                ], 404);
            }

            $configuration->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Die configuration deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting die configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Seed default configurations
     */
    public function seedDefaults()
    {
        try {
            DieConfiguration::seedDefaults();

            return response()->json([
                'success' => true,
                'message' => 'Default die configurations seeded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error seeding default configurations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get die configuration for specific belt type and section
     */
    public function getConfiguration(Request $request)
    {
        try {
            $validated = $request->validate([
                'belt_type' => 'required|string',
                'section' => 'required|string'
            ]);

            $stockPerDie = DieConfiguration::getStockPerDie(
                $validated['belt_type'],
                $validated['section']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'belt_type' => $validated['belt_type'],
                    'section' => $validated['section'],
                    'stock_per_die' => $stockPerDie
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching die configuration: ' . $e->getMessage()
            ], 500);
        }
    }
}