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
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('type'); // продажа/покупка
            $table->unsignedTinyInteger('status')->default(\App\Enums\Order\StatusEnum::PENDING_PAYMENT->value);
            $table->unsignedTinyInteger('asset'); // актив
            $table->decimal('amount', 38, 18); // количество для обмена
            $table->unsignedTinyInteger('wallet_type'); // тип кошелька
            $table->string('wallet_address', 128);
            $table->decimal('exchange_rate', 10, 2); // курс
            $table->timestamps();

            $table->foreign('user_id')->references('id')
                ->on('users')
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
