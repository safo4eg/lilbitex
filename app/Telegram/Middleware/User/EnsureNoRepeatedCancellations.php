<?php

namespace App\Telegram\Middleware\User;

use App\Enums\Order\StatusEnum;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class EnsureNoRepeatedCancellations
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $orders = Order::whereHas('user', function ($query) use($bot) {
                $query->where('chat_id', $bot->userId());
            })
            ->latest()
            ->limit(3)
            ->get();

        $cancelledOrders = $orders->countBy(function ($order) {
            return $order->status === StatusEnum::CANCELLED->value;
        });

        if(isset($cancelledOrders['1']) && $cancelledOrders['1'] === 3) {
            $user = User::where('chat_id', $bot->userId())
                ->first();

            $user->update(['deleted_at' => Carbon::now()]);

            $bot->sendMessage(
                text: view('telegram.user.middleware.blocked-user', ['id' => $user->id]),
                reply_markup: InlineKeyboardMarkup::make()
                    ->addRow(InlineKeyboardButton::make(
                        text: 'Написать менеджеру',
                        url: 'https://t.me/Lilchikbitchik'
                    )),
                chat_id: $bot->userId()
            );

            return;
        }

        $next($bot);
    }
}
