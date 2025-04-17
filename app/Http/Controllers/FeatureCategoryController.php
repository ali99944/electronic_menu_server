<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeatureCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeatureCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * Ordered by display_order.
     */
    public function index()
    {
        // Eager load features, order categories and features
        $categories = FeatureCategory::with('features')
                                    ->orderBy('display_order')
                                    ->get();
        return response()->json(['data' => $categories]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:feature_categories,title',
            'icon_name' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        // Set default order if not provided
        $validatedData['display_order'] = $validatedData['display_order'] ?? (FeatureCategory::max('display_order') + 1);

        $category = FeatureCategory::create($validatedData);
        $category->load('features'); // Load relation

        return response()->json(['data' => $category, 'message' => 'Feature category created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FeatureCategory $featureCategory) // Route Model Binding
    {
        $featureCategory->load('features'); // Eager load features
        return response()->json(['data' => $featureCategory]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeatureCategory $featureCategory) // Route Model Binding
    {
        $validator = Validator::make($request->all(), [
             'title' => ['sometimes', 'required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('feature_categories')->ignore($featureCategory->id)],
            'icon_name' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $featureCategory->update($validator->validated());
        $featureCategory->load('features'); // Reload relation

        return response()->json(['data' => $featureCategory, 'message' => 'Feature category updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeatureCategory $featureCategory) // Route Model Binding
    {
        // Features associated will be deleted due to cascade constraint
        $featureCategory->delete();
        return response()->json(['message' => 'Feature category deleted successfully.'], 200);
    }
}
