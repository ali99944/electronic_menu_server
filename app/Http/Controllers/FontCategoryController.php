<?php

namespace App\Http\Controllers;

use App\Models\FontCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FontCategoryController extends Controller
{
    public function index()
    {
        $fonts = FontCategory::all();

        return response()->json([
            'data' => $fonts
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors()
            ]);
        }


        $font = FontCategory::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'data' => $font
        ]);
    }
}
