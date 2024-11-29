<?php
/**
 * Здесь используем по максимуму данные из БД
 * Потому что при тригере на стороне сервера $bot бдет пустой!
 */
namespace App\Telegram\Conversations\User;

use App\Enums\Order\BitcoinSendReasonEnum;
use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use App\Telegram\Services\BotService;
use App\Telegram\Services\ManagerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use function Laravel\Prompts\text;

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

        $this->menuText(
            text: view('telegram.user.cancelled-menu', $viewData),
            opt: ['parse_mode' => ParseMode::HTML]
        )
            ->addButtonRow(InlineKeyboardButton::make(
                text: 'Я оплатил',
                callback_data: "{$order->id}@requestSendBitcoin"
            ))
            ->showMenu();
    }

    public function requestSendBitcoin(Nutgram $bot): void
    {
        $managerService = app(ManagerService::class);

        $orderId = $bot->callbackQuery()->data;

        Order::where('id', $orderId)
            ->update(['status' => StatusEnum::PENDING_EXCHANGE]);

        $managerService->showSendBitcoinMessage(
            $orderId,
            BitcoinSendReasonEnum::CHECK_PAYMENT_AND_SEND_BITCOIN->value
        );

        // удаляем не через end, тк на стороне сервака запускаем InlineMenu
        BotService::clearBotHistory($bot, $bot->userId());
        PendingExchangeOrderMenu::begin(
            bot: $bot,
            userId: $bot->userId(),
            chatId: $bot->userId()
        );
    }

    // будет вызываться если не нажата кнопка
    public function none(Nutgram $bot)
    {
    }
}