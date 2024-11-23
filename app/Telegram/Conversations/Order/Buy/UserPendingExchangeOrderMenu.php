<?php

namespace App\Telegram\Conversations\Order\Buy;

use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Services\BTCService;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use App\Telegram\Services\BotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use Illuminate\Database\Eloquent\Builder;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class UserPendingExchangeOrderMenu extends InlineMenuWithSaveMessageId
{
    public string $order_id;

    public function __construct()
    {
        parent::__construct();
    }

    public function start(Nutgram $bot)
    {
        $order = Order::whereHas('user', function (Builder $query) {
            $query->where('chat_id', $this->chatId);
        })
            ->where('status', StatusEnum::PENDING_EXCHANGE)
            ->latest()
            ->first();

        $this->order_id = (string) $order->id;

        $viewData = [
            'orderNumber' => $order->id,
            'walletType' => WalletTypeEnum::getWalletTypesName()[$order->setting->wallet_type],
            'walletAddress' => $order->wallet_address,
            'amountBTC' => BTCHelper::convertSatoshiToBTC($order->amount),
            'amountRUB' => BTCHelper::convertSatoshiToRub($order->amount, $order->setting->rate),
            'lastTransactionCheck' => $order->last_transaction_check,
        ];

        $this->menuText(text: view('telegram.order.buy.user-pending-exchange-menu', $viewData))
            ->addButtonRow(
                InlineKeyboardButton::make(
                    text: 'Обновить',
                    callback_data: "{$order->id}@updateOrderDetails")
            )
            ->orNext('none')
            ->showMenu();
    }

    public function updateOrderDetails(Nutgram $bot): void
    {
        BotService::clearBotHistory(bot: $bot, chatId: $this->chatId);

        $order = Order::find($this->order_id);

        if($order->status === StatusEnum::COMPLETED->value) {
            // отправка на последнее инлайн меню
        } else {
            $order->update(['last_transaction_check' => Carbon::now()]);
            $this->clearButtons();
            $this->start($bot);
        }
    }

    public function none(Nutgram $bot)
    {
        $bot->deleteMessage($this->chatId, $bot->messageId());
    }
}