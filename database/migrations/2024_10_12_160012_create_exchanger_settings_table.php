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
        Schema::create('exchanger_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->autoIncrement();
            $table->unsignedInteger('rate')->nullable();
            $table->unsignedTinyInteger('exchanger_fee')->default(0);
            $table->unsignedMediumInteger('network_fee')->nullable();
            $table->unsignedInteger('balance')->nullable();
            $table->unsignedInteger('min_amount')->default(500);
            $table->unsignedInteger('max_amount')->default(50000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchanger_settings');
    }
};
