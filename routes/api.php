<?php

use App\Events\OrderCreatedEvent;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DishExtraController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ChangelogPointController;
use App\Http\Controllers\ChangelogVersionController;
use App\Http\Controllers\FeatureCategoryController;
use App\Http\Controllers\FeatureController;
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
    Route::get('/{id}/dishes', [FoodVarietiesController::class, 'getFoodDishesByCategory']);
    Route::get('/{id}', [FoodVarietiesController::class, 'getOne']);
    Route::post('/', [FoodVarietiesController::class, 'store'])->middleware('auth:sanctum,restaurant_portal');
    Route::put('/{id}', [FoodVarietiesController::class, 'update'])->middleware('auth:sanctum,restaurant_portal');
    Route::delete('/{id}', [FoodVarietiesController::class, 'destroy'])->middleware('auth:sanctum,restaurant_portal');
});

Route::prefix('food-dishes')->group(function() {
    Route::get('/restaurants/{id}', [FoodDishesController::class, 'index']);
    Route::get('/{id}', [FoodDishesController::class, 'get_one']);
    Route::post('/', [FoodDishesController::class, 'store']);
    Route::delete('/{id}' , [FoodDishesController::class, 'destroy']);
    Route::put('/{id}' , [FoodDishesController::class, 'update']);
});

Route::prefix('restaurant-tables')->group(function() {
    // Route::get('/', [RestaurantTablesController::class, 'index']);
    Route::get('/restaurants/{id}', [RestaurantTablesController::class, 'get_restaurant_tables'])->middleware('auth:sanctum,restaurant_portal');
    Route::post('/', [RestaurantTablesController::class, 'store'])->middleware('auth:sanctum,restaurant_portal');
    Route::delete('/{id}', [RestaurantTablesController::class, 'destroy'])->middleware('auth:sanctum,restaurant_portal');
    Route::put('/{id}/status', [RestaurantTablesController::class, 'updateStatus']);
});

Route::prefix('cart-items')->group(function() {
    Route::get('/', [CartItemsController::class, 'index']);
    Route::post('/', [CartItemsController::class, 'store']);
    Route::put('/{id}', [CartItemsController::class, 'update']);
    Route::delete('/clear', [CartItemsController::class, 'clearAllCartItems']);
    Route::delete('/{id}', [CartItemsController::class, 'destroyCartItem']);
});

Route::prefix('orders')->group(function() {
    Route::get('/', [OrdersController::class, 'index'])->middleware('auth:sanctum,restaurant_portal');
    Route::get('/{id}', [OrdersController::class, 'get_one']);
    Route::post('/', [OrdersController::class, 'store']);
    Route::put('/{id}/status', [OrdersController::class, 'updateStatus'])->middleware('auth:sanctum,restaurant_portal');

    Route::delete('/{id}', [OrdersController::class, 'destroy'])->middleware('auth:sanctum,restaurant_portal');
    Route::get('/client/{phone}', [OrdersController::class, 'get_client_orders']);
});

Route::prefix('restaurants')->group(function() {
    Route::get('/', [RestaurantController::class, 'index']);
    Route::get('/{id}', [RestaurantController::class, 'get_restaurant']);
    Route::post('/', [RestaurantController::class, 'store']);
    Route::put('/change-password', [RestaurantController::class, 'change_password'])->middleware('auth:sanctum,restaurant_portal');

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



Route::apiResource('changelog-versions', ChangelogVersionController::class);
Route::apiResource('changelog-points', ChangelogPointController::class);

Route::apiResource('feature-categories', FeatureCategoryController::class);
Route::apiResource('features', FeatureController::class);

Route::get('/settings', [SettingsController::class, 'index'])->name('api.settings.index');

Route::patch('/order-items/{id}/status', [OrderItemController::class, 'updateStatus']);


// --- NEW: Routes for managing extras ON a specific dish ---
Route::post('/food-dishes/{dish}/extras', [DishExtraController::class, 'store']);
Route::delete('/food-dishes/{dish}/extras/{extra}', [DishExtraController::class, 'destroy']);
 // Optional: Route::put('/food-dishes/{dish}/extras/{extra}', [DishExtraController::class, 'update']);


 Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth:sanctum'); // Protect route
