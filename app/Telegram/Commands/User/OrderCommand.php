<?php

namespace App\Telegram\Commands\User;

use App\Enums\Order\StatusEnum;
use App\Models\Order;
use App\Telegram\Conversations\Order\OrderBuyShowMenu;
use App\Telegram\Conversations\User\CancelledOrderMenu;
use App\Telegram\Conversations\User\CompletedOrderMenu;
use App\Telegram\Conversations\User\PendingExchangeOrderMenu;
use App\Telegram\Conversations\User\PendingPaymentOrderMenu;
use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class OrderCommand extends Command
{
    protected string $command = 'order';

    public function handle(Nutgram $bot): void
    {
        $order = Order::whereHas('user', function ($query) use($bot) {
            $query->where('chat_id', $bot->userId());
        })
            ->latest()
            ->first();

        if($order === null) {
            $bot->sendMessage(
                text: 'К сожалению у вас еще нет заявок на обмен',
                reply_markup: InlineKeyboardMarkup::make()
                    ->addRow(BotService::getReturnToMenuButton())
            );
            return;
        }

        switch ($order->status) {
            case StatusEnum::PENDING_PAYMENT->value:
                PendingPaymentOrderMenu::begin(
                    bot: $bot,
                    userId: $order->user->chat_id,
                    chatId: $order->user->chat_id
                );
                break;
            case StatusEnum::PENDING_EXCHANGE->value:
                PendingExchangeOrderMenu::begin(
                    bot: $bot,
                    userId: $order->user->chat_id,
                    chatId: $order->user->chat_id
                );
                break;
            case StatusEnum::COMPLETED->value:
                CompletedOrderMenu::begin(
                    bot: $bot,
                    userId: $order->user->chat_id,
                    chatId: $order->user->chat_id
                );
                break;
            case StatusEnum::CANCELLED->value:
                CancelledOrderMenu::begin(
                    bot: $bot,
                    userId: $order->user->chat_id,
                    chatId: $order->user->chat_id
                );
                break;
        }
    }
}
