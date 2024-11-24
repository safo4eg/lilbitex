<?php
/**
 * Здесь используем по максимуму данные из БД
 * Потому что при тригере на стороне сервера $bot бдет пустой!
 */
namespace App\Telegram\Conversations\User;

use App\Enums\Order\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\Order;
use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use Illuminate\Database\Eloquent\Builder;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class UserCancelledOrderMenu extends InlineMenuWithSaveMessageId
{
    public function start(Nutgram $bot)
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
            ->showMenu();
    }

    // будет вызываться если не нажата кнопка
    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Bye!');
        $this->end();
    }
}