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
        Schema::create('feature_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., "إدارة القائمة (المنيو)"
            $table->string('icon_name')->nullable(); // Store icon name (e.g., "ListChecks", "ShoppingBasket")
            $table->integer('display_order')->default(0); // For controlling order on the page
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_categories');
    }
};
