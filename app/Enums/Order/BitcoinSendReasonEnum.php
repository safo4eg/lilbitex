<?php

namespace App\Enums\Order;

enum BitcoinSendReasonEnum: int
{
    CASE TRANSACTION_CREATE_ERROR = 1;
    CASE TRANSACTION_SEND_ERROR = 2;
    case CHECK_PAYMENT_AND_SEND_BITCOIN = 3; // проверить и оплатить
    case CONFIRM_SEND_BITCOIN = 4; // подтвердить биток
}
