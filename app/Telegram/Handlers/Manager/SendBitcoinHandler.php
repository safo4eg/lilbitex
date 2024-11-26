<?php

namespace App\Telegram\Handlers\Manager;

use App\Enums\Order\BitcoinResendReasonEnum;
use App\Models\Order;
use App\Services\BTCService;
use SergiX44\Nutgram\Nutgram;

class SendBitcoinHandler
{
    public function __invoke(Nutgram $bot, int $orderId, int $typeValue, BTCService $BTCService): void
    {
        $order = Order::find($orderId);

        if($typeValue === BitcoinResendReasonEnum::CHECK_PAYMENT_AND_SEND_BITCOIN->value) {
            // просьба подтвердить действие
        } else {
            $bot->deleteMessage(
                chat_id: $bot->chatId(),
                message_id: $bot->messageId()
            );
            $BTCService->sendBitcoin($order);
        }
    }
}
