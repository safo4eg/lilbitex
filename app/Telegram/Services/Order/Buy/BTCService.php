<?php

namespace App\Telegram\Services\Order\Buy;

use Illuminate\Support\Facades\Log;

final class BTCService
{
    /**
     * Валидация суммы для покупки, вернёт false если не прошло валидацию, true в противном случае
     */
    public static function validateAmount(string $amount): bool
    {
        // маска для введённого числа чтобы пропускало целые и btc
        if(preg_match('#^((\d){0,20}|(\d{0,20}\.\d{0,8}))$#u', $amount) === 0) {
            return false;
        }

        return true;
    }

    public static function validateWalletAddress(string $walletAddress): bool
    {
        if (preg_match('/^(1|3|bc1)[a-zA-Z0-9]{25,39}$/', $walletAddress)) {
            return true;
        }

        return false;
    }
}