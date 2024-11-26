<?php

namespace App\Enums;

enum WalletTypeEnum: int
{
    case BIGMAFIA = 1;
    case EXTERNAL = 2;

    public static function  getWalletTypesName(): array
    {
        return [
            self::BIGMAFIA->value => 'Bigmafia BTC',
            self::EXTERNAL->value => 'Внешний BTC'
        ];
    }

    public static function  getBTCWalletTypesName(): array
    {
        return [
            self::BIGMAFIA->value => 'Bigmafia BTC',
            self::EXTERNAL->value => 'Внешний BTC'
        ];
    }
}
