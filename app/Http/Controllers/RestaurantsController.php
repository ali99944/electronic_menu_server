<?php

namespace App\Http\Controllers;

use App\Models\Restaurants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantsController extends Controller
{
    public function index()
    {
        $restaurants = Restaurants::all();

        return response()->json([
            'data' => $restaurants
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [

        ]);

        return response()->json([
            
        ]);
    }
}
