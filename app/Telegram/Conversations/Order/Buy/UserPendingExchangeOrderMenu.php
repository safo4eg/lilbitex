<?php

namespace App\Telegram\Conversations\Order\Buy;

use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Services\BTCService;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use App\Telegram\Services\BotService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Database\Eloquent\Builder;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use function Laravel\Prompts\text;

class UserPendingExchangeOrderMenu extends InlineMenuWithSaveMessageId
{
    public function start(Nutgram $bot)
    {
        $order = Order::whereHas('user', function (Builder $query) {
            $query->where('chat_id', $this->chatId);
        })
            ->where('status', StatusEnum::PENDING_EXCHANGE)
            ->latest()
            ->first();

        $viewData = [
            'orderNumber' => $order->id,
            'walletType' => WalletTypeEnum::getWalletTypesName()[$order->setting->wallet_type],
            'walletAddress' => $order->wallet_address,
            'amountBTC' => BTCHelper::convertSatoshiToBTC($order->amount),
            'amountRUB' => BTCHelper::convertSatoshiToRub($order->amount, $order->setting->rate),
            'txid' => $order->txid
        ];

        $menuBuilder = $this->menuText(text: view('telegram.order.buy.user-pending-exchange-menu', $viewData));

        if($order->txid) {
            $menuBuilder->addButtonRow(InlineKeyboardButton::make(
                text: 'Отследить транзакцию',
                url: "https://mempool.space/ru/testnet/tx/{$order->txid}"
            ));
        } else {
            $menuBuilder->addButtonRow(InlineKeyboardButton::make(
                text: 'Обновить',
                callback_data: "{$order->id}@updateOrderDetails"
            ));
        }

        $menuBuilder
            ->orNext('none')
            ->showMenu();
    }

    public function updateOrderDetails(Nutgram $bot): void
    {
        BotService::clearBotHistory(bot: $bot, chatId: $this->chatId);

        $this->clearButtons();
        $this->start($bot);
    }

    public function none(Nutgram $bot)
    {
        $bot->deleteMessage($this->chatId, $bot->messageId());
    }
}