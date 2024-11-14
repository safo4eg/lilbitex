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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('type'); // продажа/покупка;
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('requisite_id');
            $table->unsignedTinyInteger('exchanger_setting_id');
            $table->unsignedTinyInteger('status')->default(\App\Enums\Order\StatusEnum::PENDING_PAYMENT->value);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('sum_to_send');
            $table->decimal('sum_to_pay', 12, 2);
            $table->string('wallet_address', 128);
            $table->timestamps();

            $table->foreign('user_id')->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('requisite_id')->references('id')
                ->on('requisites')
                ->onDelete('cascade');

            $table->foreign('exchanger_setting_id')->references('id')
                ->on('exchanger_settings')
                ->onDelete('cascade');
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
