<?php

namespace App\Telegram\Handlers\Manager;

use SergiX44\Nutgram\Nutgram;

class SendBitcoinHandler
{
    public function __invoke(Nutgram $bot, int $orderId, int $typeValue): void
    {
        $bot->sendMessage('This is an handler!' . $orderId . $typeValue);
    }
}
