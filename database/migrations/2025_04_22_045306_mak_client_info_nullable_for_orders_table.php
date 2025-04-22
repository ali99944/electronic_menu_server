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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_name')->nullable()->change();
            $table->string('client_location')->nullable()->change();
            $table->string('client_location_landmark')->nullable()->change();
            $table->string('client_phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_name')->change();
            $table->string('client_location')->change();
            $table->string('client_location_landmark')->change();
            $table->string('client_phone')->change();
        });
    }
};
