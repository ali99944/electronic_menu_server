<?php

namespace App\Http\Controllers;

use App\Models\Font;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FontController extends Controller
{
    public function index()
    {
        $fonts = Font::all();

        return response()->json([
            'data' => $fonts
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'font' => 'required',
            'font_category_id' => 'required|exists:font_categories,id',

        ]);

        if($validation->fails()){
            return response()->json([
                'errors' => $validation->errors()
            ], 500);
        }

        $font = $request->file('font');
        $fontName = $font->getClientOriginalName();
        $font->move(public_path('fonts'), $fontName);

        $font = Font::create([
            'name' => $request->name,
            'link' => "fonts/{$fontName}",
            'description' => $request->description,
            'font_category_id' => $request->font_category_id
        ]);

        return response()->json([
            'data' => $font
        ]);
    }

    public function get_category_fonts(Request $request, $id)
    {
        $fonts = Font::where('id', $id)->get();

        return response()->json([
            'data' => $fonts
        ]);
    }
}
