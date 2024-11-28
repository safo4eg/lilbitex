<?php

namespace App\Telegram\Services;

use App\Enums\GroupsEnum;
use App\Enums\Order\BitcoinSendReasonEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class ManagerService
{
    private string $chat_id;
    private Nutgram $bot;

    public function __construct()
    {
        $this->bot = app(Nutgram::class);
        $this->chat_id = config('nutgram.config.groups.' . GroupsEnum::MANAGER->value);
    }

    /**
     * Показать сообщение с ручной отправкой битка
     */
    public function showSendBitcoinMessage(int $orderId, int $typeValue): void
    {
        $order = Order::with('user', 'setting')
            ->find($orderId);

        $viewData = [
            'typeValue' => $typeValue,
            'orderNumber' => $order->id,
            'orderCreatedAt' => $order->created_at,
            'sumToPay' => $order->sum_to_pay,
            'userId' => $order->user->id,
            'username' => $order->user->username,
            'walletType' => WalletTypeEnum::getWalletTypesName()[$order->setting->wallet_type],
            'walletAddress' => $order->wallet_address,
            'sumToSend' => BTCHelper::convertSatoshiToBTC($order->amount + $order->network_fee),
        ];

        $inlineKeyboardMarkup = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(
                    text: 'Отправить биток',
                    callback_data: "/btc/send/:$orderId/:$typeValue"
                )
            );

        if(BitcoinSendReasonEnum::CHECK_PAYMENT_AND_SEND_BITCOIN->value === $typeValue) {
            $inlineKeyboardMarkup->addRow(
                InlineKeyboardButton::make(
                    text: 'Отменить',
                    callback_data: "/btc/cancel/:$orderId"
                )
            );
        }


        $this->bot->sendMessage(
            text: (string) view('telegram.manager.send-bitcoin-message', $viewData),
            reply_markup: $inlineKeyboardMarkup,
            chat_id: $this->chat_id,
            parse_mode: ParseMode::HTML
        );
    }
}