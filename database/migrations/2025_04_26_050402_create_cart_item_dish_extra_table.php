<?php

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
        // Pivot table to link cart items with their selected dish extras
        Schema::create('cart_item_dish_extra', function (Blueprint $table) {
            // No primary ID needed for a simple pivot unless required for other reasons
            // $table->id();

            // Foreign key to the cart item
            $table->foreignId('cart_items_id')
                  ->constrained('cart_items') // Assumes your table is 'cart_items'
                  ->onDelete('cascade'); // If cart item is deleted, remove the link

            // Foreign key to the dish extra
            $table->foreignId('dish_extra_id')
                  ->constrained('dish_extras') // Assumes your table is 'dish_extras'
                  ->onDelete('cascade'); // If extra is deleted, remove the link (consider if this is desired)

            // Add quantity if an extra can be added multiple times (e.g., "Double Cheese")
            // $table->unsignedInteger('quantity')->default(1);

            // Optional: Add price at the time of adding, in case extra prices change
            // $table->decimal('price_at_addition', 8, 2)->nullable();

            // Ensure combination is unique for a cart item
            $table->primary(['cart_items_id', 'dish_extra_id']);

            // No timestamps needed usually for a simple pivot
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_item_dish_extra');
    }
};
