<?php

namespace App\Enums\Order;

enum CancellationReasonEnum: int
{
    case SYSTEM = 1;
    case USER = 2;
    case MANAGER = 3;
}
