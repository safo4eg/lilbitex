<?php

namespace App\Telegram\Handlers\Manager;

use App\Enums\Order\BitcoinSendReasonEnum;
use App\Models\Order;
use App\Services\BTCService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class SendBitcoinHandler
{
    public function __invoke(Nutgram $bot, int $orderId, int $typeValue, BTCService $BTCService): void
    {
        Log::channel('single')->debug('зашло в отправку битка');
        $bot->deleteMessage(
            chat_id: $bot->chatId(),
            message_id: $bot->messageId()
        );

        $order = Order::find($orderId);

        if($typeValue === BitcoinSendReasonEnum::CHECK_PAYMENT_AND_SEND_BITCOIN->value) {
            $viewData = [
                'createdAt' => $order->created_at,
                'sumToPay' => $order->sum_to_pay
            ];

            $confirmSendBitcoinValue = BitcoinSendReasonEnum::CONFIRM_SEND_BITCOIN->value;

            $inlineKeyboardMarkup = InlineKeyboardMarkup::make()
                ->addRow(
                    InlineKeyboardButton::make(
                        text: 'Отправить биток',
                        callback_data: "/btc/send/:{$order->id}/:$confirmSendBitcoinValue"
                    )
                )
                ->addRow(
                    InlineKeyboardButton::make(
                        text: 'Отменить',
                        callback_data: "/btc/cancel/:{$order->id}"
                    )
                );


            $bot->sendMessage(
                text: view('telegram.manager.confirm-send-bitcoin', $viewData),
                reply_markup: $inlineKeyboardMarkup,
                parse_mode: ParseMode::HTML,
                chat_id: $bot->chatId()
            );
        } else {
            $BTCService->sendBitcoin($order);
        }
    }
}
