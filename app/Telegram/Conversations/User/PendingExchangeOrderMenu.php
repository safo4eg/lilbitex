<?php

namespace App\Telegram\Conversations\User;

use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use App\Telegram\Services\BotService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class PendingExchangeOrderMenu extends InlineMenuWithSaveMessageId
{
    public string $order_id;

    public function __construct()
    {
        parent::__construct();
    }

    public function start(Nutgram $bot)
    {
        $this->clearButtons();

        $order = Order::whereHas('user', function (Builder $query) {
            $query->where('chat_id', $this->chatId);
        })
            ->where('status', StatusEnum::PENDING_EXCHANGE->value)
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

        $this->menuText(text: view('telegram.user.pending-exchange-menu', $viewData))
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
        $order = Order::find($this->order_id);

        if($order->status === StatusEnum::COMPLETED->value) {
            CompletedOrderMenu::begin(
                bot: $bot,
                userId: $bot->userId(),
                chatId: $bot->userId()
            );

//            BotService::clearBotHistory($bot, $bot->userId());
        } else {
            $order->update(['last_transaction_check' => Carbon::now()]);
            $this->start($bot);
        }
    }

    public function none(Nutgram $bot)
    {
        $bot->deleteMessage($this->chatId, $bot->messageId());
    }
}