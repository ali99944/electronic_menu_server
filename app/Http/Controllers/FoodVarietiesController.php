<?php

namespace App\Http\Controllers;

use App\Models\FoodVarieties;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoodVarietiesController extends Controller
{
    public function index()
    {
        $food_varities = FoodVarieties::all();

        return response()->json([
            'data' => $food_varities
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [

        ]);

        $image = $request->file('image');
        $imageName = time() . '.' . $image->extension();
        $image->move(public_path('images/varieties'), $imageName);

        $varient = FoodVarieties::create([
            'name' => $request->name,
            'image' => 'images/varieties/' . $imageName
        ]);

        return response()->json([
            'data' => $varient
        ]);
    }

    public function destroy(Request $request, $id)
    {
        FoodVarieties::where('id', $id)->delete();

        return response()->json([
            'status' => true
        ]);
    }

    public function update(Request $request, $id)
    {
        // Find the food variety by ID
        $variety = FoodVarieties::find($id);

        if (!$variety) {
            return response()->json([
                'error' => 'Food variety not found'
            ], 404);
        }

        // Validate the incoming request data
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255', // Adjust validation rules as needed
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Only validate image if provided
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 400);
        }

        // Update the name
        $variety->name = $request->name;

        // If an image is provided, handle image upload
        if ($request->hasFile('image')) {
            // Delete the old image from the server if it exists
            if (file_exists(public_path($variety->image))) {
                unlink(public_path($variety->image));
            }

            // Upload the new image
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $image->move(public_path('images/varieties'), $imageName);
            $variety->image = 'images/varieties/' . $imageName;
        }

        // Save the updated variety
        $variety->save();

        return response()->json([
            'data' => $variety
        ]);
    }
}
