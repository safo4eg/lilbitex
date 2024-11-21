<?php

namespace App\Telegram\Conversations\Order\Buy;

use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class UserPendingPaymentOrderMenu extends InlineMenuWithSaveMessageId
{
    public function start(Nutgram $bot)
    {
        $order = Order::whereHas('user', function (Builder $query) use($bot) {
            $query->where('chat_id', $bot->userId());
        })
            ->where('status', StatusEnum::PENDING_PAYMENT->value)
            ->latest()
            ->first();

        $viewData = [
            'orderNumber' => $order->id,
            'walletType' => WalletTypeEnum::getWalletTypesName()[$order->setting->wallet_type],
            'walletAddress' => $order->wallet_address,
            'amountBTC' => BTCHelper::convertSatoshiToBTC($order->amount),
            'amountRUB' => BTCHelper::convertSatoshiToRub($order->amount, $order->setting->rate),
            'bankName' => $order->requisite->bank_name,
            'phone' => $order->requisite->phone,
            'initials' => $order->requisite->initials,
            'sum' => $order->sum_to_pay
        ];

        $this->menuText(text: view('telegram.order.buy.user-pending-payment-menu', $viewData))
            ->showMenu();
    }


    // будет вызываться если не нажата кнопка
    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Bye!');
        $this->end();
    }
}
