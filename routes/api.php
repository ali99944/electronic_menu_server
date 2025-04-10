<?php

use App\Events\OrderCreatedEvent;
use App\Http\Controllers\CartItemsController;
use App\Http\Controllers\FontCategoryController;
use App\Http\Controllers\FontController;
use App\Http\Controllers\FoodDishesController;
use App\Http\Controllers\FoodVarietiesController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantMenuStyleController;
use App\Http\Controllers\RestaurantPortalsController;
use App\Http\Controllers\RestaurantSettingController;
use App\Http\Controllers\RestaurantTablesController;
use App\Models\Orders;
use Illuminate\Support\Facades\Route;


Route::prefix('food-varieties')->group(function() {
    Route::get('/restaurants/{id}', [FoodVarietiesController::class, 'index']);
    Route::post('/', [FoodVarietiesController::class, 'store'])->middleware('auth:sanctum,restaurant_portal');
    Route::put('/{id}', [FoodVarietiesController::class, 'update'])->middleware('auth:sanctum,restaurant_portal');
    Route::delete('/{id}', [FoodVarietiesController::class, 'destroy'])->middleware('auth:sanctum,restaurant_portal');
});

Route::prefix('food-dishes')->group(function() {
    Route::get('/restaurants/{id}', [FoodDishesController::class, 'index']);
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
    Route::get('/', [OrdersController::class, 'index'])->middleware('auth:sanctum,restaurant_portal');
    Route::get('/client/{phone}', [OrdersController::class, 'get_client_orders']);
    Route::get('/{id}', [OrdersController::class, 'get_one']);
    Route::post('/', [OrdersController::class, 'store']);
    Route::put('/{id}/status', [OrdersController::class, 'updateStatus']);

    Route::delete('/{id}', [OrdersController::class, 'destroy']);
});

Route::prefix('restaurants')->group(function() {
    Route::get('/', [RestaurantController::class, 'index']);
    Route::get('/{id}', [RestaurantController::class, 'get_restaurant']);
    Route::post('/', [RestaurantController::class, 'store']);

    Route::get('/{id}/settings', [RestaurantSettingController::class, 'settings']);
    Route::put('/{id}/settings', [RestaurantSettingController::class, 'update_settings']);

    Route::get('/{id}/styles', [RestaurantMenuStyleController::class, 'styles']);
    Route::get('/{id}/styles/css', [RestaurantMenuStyleController::class, 'css_styles']);
    Route::put('/{id}/styles', [RestaurantMenuStyleController::class, 'update_styles']);
});


Route::prefix('font-categories')->group(function() {
    Route::get('/', [FontCategoryController::class, 'index']);
    Route::post('/', [FontCategoryController::class, 'store']);

    Route::post('/font-categories/{id}/fonts', [FontController::class, 'get_category_fonts']);
});

Route::prefix('fonts')->group(function() {
    Route::get('/', [FontController::class, 'index']);
    Route::post('/', [FontController::class, 'store']);
});

Route::prefix('restaurant-portals')->group(function() {
    Route::post('/', [RestaurantPortalsController::class, 'store']);
    Route::post('/login', [RestaurantPortalsController::class, 'login_portal']);
});

Route::get('/pusher/test', function () {
    event(new OrderCreatedEvent(
        Orders::create([
            'notes' => 'لا يوجد',
            'status' => 'pending',
            'cost_price' => 0,
            'restaurant_table_number' => 1,
            'client_name' => 'لا يوجد',
            'client_location' => 'لا يوجد',
            'client_location_landmark' => 'لا يوجد',
            'client_phone' => 'لا يوجد',
            'order_type' => 'inside'
        ])
    ));

    return 'done';
});
