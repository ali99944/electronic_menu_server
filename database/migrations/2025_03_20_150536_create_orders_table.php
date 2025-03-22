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
            $table->foreignIdFor(RestaurantTables::class);
            $table->string('notes')->nullable();

            // $table->foreignIdFor(Restaurants::class);
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
