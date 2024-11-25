<?php

namespace App\Enums\Order;

enum BitcoinResendReasonEnum: int
{
    CASE TRANSACTION_CREATE_ERROR = 1;
    CASE TRANSACTION_SEND_ERROR = 2;
    case CHECK_PAYMENT_AND_SEND_BITCOIN = 3;
}
