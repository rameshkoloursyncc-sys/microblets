<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RateFormula;
use Illuminate\Http\Request;

class RateFormulaController extends Controller
{
    /**
     * Get all rate formulas
     */
    public function index(Request $request)
    {
        $query = RateFormula::query();

        if ($request->has('category')) {
            $query->forCategory($request->category);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        return $query->with('creator:id,name')->get();
    }

    /**
     * Store a new rate formula
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'formula' => 'required|array',
            'formula.type' => 'required|in:multiply,divide_multiply,custom',
            'is_active' => 'boolean',
        ]);

        // Check if formula already exists
        $existing = RateFormula::where('category', $validated['category'])
            ->where('section', $validated['section'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Formula already exists for this section',
                'formula' => $existing
            ], 409);
        }

        $formula = RateFormula::create($validated);

        return response()->json([
            'message' => 'Formula created successfully',
            'formula' => $formula
        ], 201);
    }

    /**
     * Update a rate formula
     */
    public function update(Request $request, int $id)
    {
        $formula = RateFormula::findOrFail($id);

        $validated = $request->validate([
            'formula' => 'sometimes|array',
            'formula.type' => 'sometimes|in:multiply,divide_multiply,custom',
            'is_active' => 'sometimes|boolean',
        ]);

        $formula->update($validated);

        return response()->json([
            'message' => 'Formula updated successfully',
            'formula' => $formula->fresh()
        ]);
    }

    /**
     * Update formula by category and section (for settings page)
     */
    public function updateBySection(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'formula' => 'required|string',
        ]);

        $formula = RateFormula::where('category', $validated['category'])
            ->where('section', $validated['section'])
            ->first();

        if (!$formula) {
            // Create new formula if it doesn't exist
            $formula = RateFormula::create([
                'category' => $validated['category'],
                'section' => $validated['section'],
                'formula' => $validated['formula'],
                'is_active' => true,
            ]);
        } else {
            // Update existing formula
            $formula->update([
                'formula' => $validated['formula'],
                'is_active' => true,
            ]);
        }

        return response()->json([
            'message' => 'Formula updated successfully',
            'formula' => $formula->fresh()
        ]);
    }

    /**
     * Delete a rate formula
     */
    public function destroy(int $id)
    {
        $formula = RateFormula::findOrFail($id);
        $formula->delete();

        return response()->json([
            'message' => 'Formula deleted successfully'
        ]);
    }

    public function getAllFormulas()
    {
        try{
            $formulas = RateFormula::all();
            $organised = [];

            foreach ($formulas as $formula) {
                $category = $formula->category;
                $section = $formula->section;

                if(!isset($organised[$category])){
                    $organised[$category]= [];
                }
            $parsed = $this->parseFormula($formula->formula, $category);
            $formulaData=[
                'id' => $formula->id,
                'formula' => $formula->formula,
                'multiplier'=>$parsed['multiplier'],
                'divisor'=>$parsed['divisor'],
                'created_at'=>$formula->created_at,
                'updated_at'=>$formula->updated_at,
            ];
            if($parsed['type_multiplier'] !== null){
                $formulaData['type_multiplier'] = $parsed['type_multiplier'];
            }
            $organised[$category] [$section] = $formulaData;
            }
          return response()->json($organised);
        } catch(\Exception $e){
            return response()->json([
                'error' => 'error is this',
                "message" => $e->getMessage()
            ], 500);
        }
    }



   private function parseFormula($formulaString, $category)
{
    $result = [
        'multiplier' => 0,
        'divisor' => 1,
        'type_multiplier' => null
    ];
    
    if (empty($formulaString)) {
        return $result;
    }
    
    // Handle different formula types
    if ($category === 'timing_belts') {
        // Timing belt formula: size*type*450*0.0094+size*total_mm*0.0094
        if (preg_match('/size\*type\*(\d+(?:\.\d+)?)\*(\d+(?:\.\d+)?)\+size\*total_mm\*(\d+(?:\.\d+)?)/', $formulaString, $matches)) {
            $result['type_multiplier'] = floatval($matches[1]);
            $result['multiplier'] = floatval($matches[2]);
        }
    } elseif ($category === 'poly_belts') {
        // Poly belt formula: size/25.4*0.36
        if (preg_match('/ribs\/(\d+(?:\.\d+)?)\*(\d+(?:\.\d+)?)/', $formulaString, $matches)) {
            $result['divisor'] = floatval($matches[1]);
            $result['multiplier'] = floatval($matches[2]);
        }
    } else {
        // Standard formula: size/1*1.05
        if (preg_match('/size\/(\d+(?:\.\d+)?)\*(\d+(?:\.\d+)?)/', $formulaString, $matches)) {
            $result['divisor'] = floatval($matches[1]);
            $result['multiplier'] = floatval($matches[2]);
        }
    }
    
    return $result;
}

}
