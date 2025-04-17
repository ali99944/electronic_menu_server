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
        Schema::create('changelog_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique(); // e.g., "1.0", "1.1-beta"
            $table->string('release_date'); // Store as string to easily handle "Upcoming", "Initial Release" etc.
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('changelog_versions');
    }
};
