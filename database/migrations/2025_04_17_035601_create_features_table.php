<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FeatureCategory; // Import model

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FeatureCategory::class)
                  ->constrained()
                  ->cascadeOnDelete(); // Delete features if category is deleted
            $table->string('name'); // e.g., "إدارة التصنيفات"
            $table->text('description');
            $table->string('icon_name')->nullable(); // e.g., "Tag", "CheckCircle"
            $table->boolean('available_in_base')->default(true); // Simple flag for availability (can be expanded later for plans)
             $table->integer('display_order')->default(0); // Control order within category
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
