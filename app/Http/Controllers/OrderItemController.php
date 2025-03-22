<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderItemController extends Controller
{
    public function index()
    {
        $food_varities = OrderItem::all();

        return response()->json([
            'data' => $food_varities
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [

        ]);

        $varient = OrderItem::create([
            'name' => $request->name,

        ]);

        return response()->json([
            'data' => $varient
        ]);
    }
}
