<?php

namespace App\Observers;

use App\Enums\Order\StatusEnum;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function updating(Order $order) {
        $changes = $order->getDirty();

        if (
            isset($changes['status'])
            && $changes['status'] !== StatusEnum::CANCELLED->value
        ) {
            $order->cancellation_reason = null;
        }
    }
}
