<?php

use App\Models\FoodDishes;
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
        Schema::create('dish_variations', function (Blueprint $table) {
            $table->id();
            // Link to the main dish
            $table->foreignIdFor(FoodDishes::class)->constrained()->cascadeOnDelete();

            // Describes the variation (e.g., "Small", "Medium", "Large", "وسط", "كبير", "جامبو", "Normal", "Meal", "عادي", "وجبة")
            // For single-price items, you could use a default value like "Standard" or just the dish name again, or leave it nullable if appropriate.
            $table->string('name')->default('Standard');

            // Use decimal for price to avoid floating-point issues
            $table->decimal('price', 8, 2); // Adjust precision (8) and scale (2) as needed

            // Optional: Add a specific description for this variation if needed
            // $table->string('variation_description')->nullable();

            // Optional: Add SKU or other identifiers if needed
            // $table->string('sku')->nullable()->unique();

            $table->timestamps();

            // Index for faster lookups
            $table->index('food_dishes_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dish_variations');
    }
};
