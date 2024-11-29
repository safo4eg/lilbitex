<?php

namespace App\Telegram\Handlers\Manager;

use App\Enums\Order\StatusEnum;
use App\Models\Order;
use App\Telegram\Conversations\User\CancelledOrderMenu;
use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;

class CancelOrderHandler
{
    public function __invoke(Nutgram $bot, int $orderId): void
    {
        $order = Order::with('user')
            ->find($orderId);

        $order->update(['status' => StatusEnum::CANCELLED->value]);

        $bot->deleteMessage(
            message_id: $bot->messageId(),
            chat_id: $bot->chatId()
        );

        BotService::clearBotHistory($bot, $order->user->chat_id);
        CancelledOrderMenu::begin(
            bot: $bot,
            userId: $order->user->chat_id,
            chatId: $order->user->chat_id
        );
    }
}