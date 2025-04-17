<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     * Optionally filter by category_id. Ordered by display_order.
     */
    public function index(Request $request)
    {
        $query = Feature::query()->orderBy('display_order');

        if ($request->has('feature_category_id')) {
            $query->where('feature_category_id', $request->input('feature_category_id'));
        }

        // Select only necessary category fields
        $features = $query->with('category:id,title')->get();

        return response()->json(['data' => $features]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feature_category_id' => 'required|integer|exists:feature_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon_name' => 'nullable|string|max:50',
            'available_in_base' => 'sometimes|boolean', // Accept boolean directly
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

         $validatedData = $validator->validated();
         // Set default order if not provided
        $validatedData['display_order'] = $validatedData['display_order'] ?? (Feature::where('feature_category_id', $validatedData['feature_category_id'])->max('display_order') + 1);
        // Ensure boolean default if not sent
        $validatedData['available_in_base'] = $validatedData['available_in_base'] ?? true;


        $feature = Feature::create($validatedData);
        $feature->load('category:id,title'); // Load relation

        return response()->json(['data' => $feature, 'message' => 'Feature created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Feature $feature) // Route Model Binding
    {
        $feature->load('category:id,title'); // Load relation
        return response()->json(['data' => $feature]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Feature $feature) // Route Model Binding
    {
        $validator = Validator::make($request->all(), [
            'feature_category_id' => 'sometimes|required|integer|exists:feature_categories,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'icon_name' => 'nullable|string|max:50',
            'available_in_base' => 'sometimes|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $feature->update($validator->validated());
        $feature->load('category:id,title'); // Reload relation

        return response()->json(['data' => $feature, 'message' => 'Feature updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feature $feature) // Route Model Binding
    {
        $feature->delete();
        return response()->json(['message' => 'Feature deleted successfully.'], 200);
    }
}
