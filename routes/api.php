<?php

use App\Events\OrderCreatedEvent;
use App\Http\Controllers\CartItemsController;
use App\Http\Controllers\FoodDishesController;
use App\Http\Controllers\FoodVarietiesController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\RestaurantsController;
use App\Http\Controllers\RestaurantTablesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('access-portals')->group(function() {

});


Route::prefix('restaurants')->group(function() {
    Route::get('/', [RestaurantsController::class, 'index']);
});


Route::prefix('food-varieties')->group(function() {
    Route::get('/', [FoodVarietiesController::class, 'index']);
    Route::post('/', [FoodVarietiesController::class, 'store']);
    Route::put('/{id}', [FoodVarietiesController::class, 'update']);
    Route::delete('/{id}', [FoodVarietiesController::class, 'destroy']);
});

Route::prefix('food-dishes')->group(function() {
    Route::get('/', [FoodDishesController::class, 'index']);
    Route::post('/', [FoodDishesController::class, 'store']);
    Route::delete('/{id}' , [FoodDishesController::class, 'destroy']);
    Route::put('/{id}' , [FoodDishesController::class, 'update']);
});

Route::prefix('restaurant-tables')->group(function() {
    Route::get('/', [RestaurantTablesController::class, 'index']);
    Route::post('/', [RestaurantTablesController::class, 'store']);
    Route::delete('/{id}', [RestaurantTablesController::class, 'destroy']);
    Route::put('/{id}/status', [RestaurantTablesController::class, 'updateStatus']);
});

Route::prefix('cart-items')->group(function() {
    Route::get('/', [CartItemsController::class, 'index']);
    Route::post('/', [CartItemsController::class, 'store']);
    Route::put('/{id}', [CartItemsController::class, 'update']);
});

Route::prefix('orders')->group(function() {
    Route::get('/', [OrdersController::class, 'index']);
    Route::post('/', [OrdersController::class, 'store']);
    Route::put('/{id}/status', [OrdersController::class, 'updateStatus']);
});
