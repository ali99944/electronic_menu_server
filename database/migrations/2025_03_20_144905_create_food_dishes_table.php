<?php

use App\Models\FoodVarieties;
use App\Models\Restaurants;
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
        Schema::create('food_dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FoodVarieties::class);
            $table->foreignIdFor(Restaurants::class);
            $table->string('name');
            $table->string('description');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_dishes');
    }
};
