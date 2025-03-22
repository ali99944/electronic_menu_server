<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantTablesController extends Controller
{
    public function index()
    {
        $restaurant_tables = RestaurantTables::all();

        return response()->json([
            'data' => $restaurant_tables
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [

        ]);

        $table = RestaurantTables::create([
            'table_number' => $request->table_number
        ]);

        return response()->json([
            'data' => $table
        ]);
    }

    public function destroy(Request $request, $id)
    {
        RestaurantTables::where('id', $id)->delete();

        return response()->json([
            'status' => true
        ]);
    }

    // Update Restaurant Table Status
    public function updateStatus(Request $request, $id)
    {
        // Validate the status input
        $validation = Validator::make($request->all(), [
            'status' => 'required|in:reserved,busy,free'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors()
            ], 400);
        }

        // Find the table by its ID
        $table = RestaurantTables::find($id);

        if (!$table) {
            return response()->json([
                'error' => 'Restaurant table not found'
            ], 404);
        }

        // Update the table status
        $table->status = $request->status;
        $table->save();

        return response()->json([
            'data' => $table
        ]);
    }
}
