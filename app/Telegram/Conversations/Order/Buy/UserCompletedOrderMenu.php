<?php

namespace App\Telegram\Conversations\Order\Buy;

use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Database\Eloquent\Builder;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class UserCompletedOrderMenu extends InlineMenuWithSaveMessageId
{
    public function start(Nutgram $bot)
    {
        $order = Order::whereHas('user', function (Builder $query) {
            $query->where('chat_id', $this->chatId);
        })
            ->where('status', StatusEnum::COMPLETED->value)
            ->latest()
            ->first();

        $viewData = [
            'orderNumber' => $order->id,
            'walletType' => WalletTypeEnum::getWalletTypesName()[$order->setting->wallet_type],
            'walletAddress' => $order->wallet_address,
            'amountBTC' => BTCHelper::convertSatoshiToBTC($order->amount),
            'amountRUB' => BTCHelper::convertSatoshiToRub($order->amount, $order->setting->rate),
            'txid' => $order->txid,

        ];

        $this->menuText(text: view('telegram.order.buy.user-completed-menu', $viewData))
            ->addButtonRow(
                InlineKeyboardButton::make(
                    text: 'Отследить транзакцию',
                    url: "https://mempool.space/ru/testnet/tx/{$order->txid}"
                )
            )
            ->orNext('none')
            ->showMenu();
    }

    public function none(Nutgram $bot)
    {
        $bot->deleteMessage($this->chatId, $bot->messageId());
    }
}