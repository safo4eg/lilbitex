<?php

namespace App\Telegram\Conversations\User;

use App\Enums\Order\CancellationReasonEnum;
use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use App\Telegram\Services\BotService;
use Illuminate\Database\Eloquent\Builder;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class PendingPaymentOrderMenu extends InlineMenuWithSaveMessageId
{
    public string $order_id;

    public function start(Nutgram $bot)
    {
        $order = Order::whereHas('user', function (Builder $query) use($bot) {
            $query->where('chat_id', $bot->userId());
        })
            ->where('status', StatusEnum::PENDING_PAYMENT->value)
            ->latest()
            ->first();

        $this->order_id = $order->id;

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

        $this->menuText(text: view('telegram.user.pending-payment-menu', $viewData))
            ->addButtonRow(
                InlineKeyboardButton::make(
                    text: 'Отменить',
                    callback_data: '@handleCancelOrder'
                )
            )
            ->addButtonRow(BotService::getReturnToMenuButton())
            ->showMenu();
    }

    public function handleCancelOrder(Nutgram $bot): void
    {
        Order::where('id', $this->order_id)
            ->update([
                'status' => StatusEnum::CANCELLED->value,
                'cancellation_reason' => CancellationReasonEnum::USER->value
            ]);

        CancelledOrderMenu::begin(
            bot: $bot,
            userId: $bot->userId(),
            chatId: $bot->userId()
        );

        BotService::clearBotHistory($bot, $bot->userId());
    }

    // будет вызываться если не нажата кнопка
    public function none(Nutgram $bot)
    {
    }
}
