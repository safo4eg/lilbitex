<?php

namespace App\Enums\Order;

enum WalletTypesEnum: int
{
    case BIGMAFIA = 1;
    case EXTERNAL = 2;

    public static function getWalletTypesName(): array
    {
        return [
            self::BIGMAFIA->value => 'Bigmafia BTC',
            self::EXTERNAL->value => 'Внешний BTC'
        ];
    }
}
