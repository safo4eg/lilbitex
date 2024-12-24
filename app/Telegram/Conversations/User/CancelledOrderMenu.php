<?php
/**
 * Здесь используем по максимуму данные из БД
 * Потому что при тригере на стороне сервера $bot бдет пустой!
 */
namespace App\Telegram\Conversations\User;

use App\Enums\Order\BitcoinSendReasonEnum;
use App\Enums\Order\CancellationReasonEnum;
use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use App\Telegram\Services\BotService;
use App\Telegram\Services\ManagerService;
use Illuminate\Database\Eloquent\Builder;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class CancelledOrderMenu extends InlineMenuWithSaveMessageId
{
    public function start(Nutgram $bot): void
    {
        // пока что только для битка делаю,
        //возможно в дальнейшем здесь придется абстрактно для других монет сделать

        // получаем последний отменённый счёт
        $order = Order::whereHas('user', function (Builder $query) use($bot) {
            $query->where('chat_id', $this->chatId);
        })
            ->where('status', StatusEnum::CANCELLED->value)
            ->latest()
            ->first();

        if ($order->user->deleted_at) {
            $this->end();
            return;
        }

        $viewData = [
            'orderNumber' => $order->id,
            'walletType' => WalletTypeEnum::getWalletTypesName()[$order->setting->wallet_type],
            'walletAddress' => $order->wallet_address,
            'amountBTC' => BTCHelper::convertSatoshiToBTC($order->amount),
            'amountRUB' => BTCHelper::convertSatoshiToRub($order->amount, $order->setting->rate),
            'sum' => $order->sum_to_pay
        ];

        $menuBuilder = $this->menuText(
            text: view('telegram.user.cancelled-menu', $viewData),
            opt: ['parse_mode' => ParseMode::HTML]
        );

        if(
            $order->cancellation_reason === CancellationReasonEnum::SYSTEM->value
            OR $order->cancellation_reason === CancellationReasonEnum::USER->value
        ) {
            $menuBuilder->addButtonRow(InlineKeyboardButton::make(
                text: 'Я оплатил',
                callback_data: "{$order->id}@requestSendBitcoin"
            ));
        }

        $menuBuilder
            ->addButtonRow(BotService::getReturnToMenuButton())
            ->showMenu();
    }

    public function requestSendBitcoin(Nutgram $bot): void
    {
        $managerService = app(ManagerService::class);

        $order = Order::find($bot->callbackQuery()->data);

        $order->update(['status' => StatusEnum::PENDING_EXCHANGE->value]);

        $managerService->showSendBitcoinMessage(
            $order->id,
            BitcoinSendReasonEnum::CHECK_PAYMENT_AND_SEND_BITCOIN->value
        );

        // удаляем не через end, тк на стороне сервака запускаем InlineMenu
        PendingExchangeOrderMenu::begin(
            bot: $bot,
            userId: $bot->userId(),
            chatId: $bot->userId()
        );

//        BotService::clearBotHistory($bot, $bot->userId());
    }

    // будет вызываться если не нажата кнопка
    public function none(Nutgram $bot)
    {
    }
}