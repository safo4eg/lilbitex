<?php

namespace App\Telegram\Services;

use App\Enums\GroupsEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\Manager\ManagerPendingExchangeOrderMenu;
use Illuminate\Support\Facades\Log;
use Nutgram\Laravel\Facades\Telegram;
use SergiX44\Nutgram\Nutgram;
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
            'userId' => $order->user->id,
            'username' => $order->user->username,
            'walletType' => WalletTypeEnum::getWalletTypesName()[$order->setting->wallet_type],
            'walletAddress' => $order->wallet_address,
            'sumToSend' => BTCHelper::convertSatoshiToBTC($order->amount + $order->network_fee),
        ];
        Log::channel('single')->debug($this->chat_id);
        $this->bot->sendMessage(
            text: (string) view('telegram.manager.send-bitcoin-message', $viewData),
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow(InlineKeyboardButton::make(
                    text: 'Отправить биток',
                    callback_data: "/btc/send/:$orderId/:$typeValue"
                )),
            chat_id: $this->chat_id
        );
    }
}