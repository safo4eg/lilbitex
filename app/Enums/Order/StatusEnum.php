<?php

namespace App\Enums\Order;

enum StatusEnum: int
{
    case PENDING_PAYMENT = 1;
    case PENDING_EXCHANGE = 2;
    case COMPLETED = 3;
    case CANCELLED = 4;
}
