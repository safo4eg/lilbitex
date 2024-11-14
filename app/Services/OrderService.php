<?php

namespace App\Services;

use App\Enums\Order\StatusEnum;
use App\Enums\Order\TypeEnum;
use App\Models\Order;

final class OrderService
{
    /**
     * Получить незанятые копейки
     */
    public function getUnusedKopecks(): string
    {
        $sumToPayArray = Order::where('type', TypeEnum::BUY->value)
            ->where('status', StatusEnum::PENDING_PAYMENT->value)
            ->pluck('sum_to_pay')
            ->toArray();

        if(count($sumToPayArray) === 0) {
            return '01';
        } else {
            $usedKopecksArray = array_map(function ($sum) {
                $exploded = explode('.', $sum);
                return $exploded[1] ?? '00';
            }, $sumToPayArray);

            for($ten = 0; $ten < 10; $ten++) {
                for($unit = 1; $unit < 10; $unit++) {
                    $kopecks = "$ten$unit";
                    if(in_array($kopecks, $usedKopecksArray) === false) {
                        return $kopecks;
                    }
                }
            }
        }

        return '00';
    }
}