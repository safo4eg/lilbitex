<?php

namespace App\Telegram\Conversations\Manager;

use App\Enums\Order\ManagerPendingExchangeTypeEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;

class ManagerPendingExchangeOrderMenu extends InlineMenu
{
    public int $type_value;

    public int $order_id;

    public function start(Nutgram $bot, int $orderId, int $typeValue): void
    {
        $this->order_id = $orderId;
        $this->type_value = $typeValue;

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

        $this->menuText(view('telegram.manager.pending-exchange-menu', $viewData))
            ->orNext('none')
            ->showMenu();
    }


    public function none(Nutgram $bot)
    {
        $bot->deleteMessage($this->chatId, $bot->messageId());
    }
}
