<?php

namespace App\Helpers;

final class BTCHelper
{
    /**
     * Перевод сатоши в биткоин
     */
    public static function convertSatoshiToBTC(string $satoshi): string
    {
        return bcdiv($satoshi, '100000000', 8);
    }

    /**
     * Перевод сатоши в рубли
     */
    public static function convertSatoshiToRub(string $satoshi, string $rate): string
    {
        $satoshiRate = bcdiv($rate, '100000000', 8); // цена 1 сатоши
        return bcmul($satoshi, $satoshiRate, 0);
    }

    /**
     * Конвертация рублей в сатоши
     */
    public static function convertRubToBTC(string $rub, string $rate): string
    {
        return bcdiv($rub, $rate, 8);
    }
}