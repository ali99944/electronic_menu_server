<?php

use App\Models\Font;
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
        Schema::create('restaurant_menu_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Restaurants::class);
            $table->foreignIdFor(Font::class);

            $table->string('menu_background_color')->default('#F3F5F7');

            $table->string('header_bg_color')->default('#1B525C');
            $table->string('header_text_color')->default('#ffffff');

            $table->string('banner_title_color')->default('#ffffff');
            $table->string('banner_description_color')->default('#ABABAA');

            $table->string('primary_color')->default('#1B525C');
            $table->string('primary_text_color')->default('#ffffff');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_menu_styles');
    }
};
