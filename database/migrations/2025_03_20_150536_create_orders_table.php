<?php

use App\Models\Restaurants;
use App\Models\RestaurantTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->double('cost_price');
            $table->enum('status', ['pending', 'completed', 'rejected', 'in-progress'])->default('pending');
            $table->string('restaurant_table_number')->nullable();
            $table->string('notes')->nullable();
            $table->string('client_name');
            $table->string('client_location');
            $table->string('client_location_landmark');
            $table->string('client_phone'); 
            $table->enum('order_type', ['inside', 'delivery'])->default('delivery');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
