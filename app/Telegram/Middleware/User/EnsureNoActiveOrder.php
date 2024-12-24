<?php

namespace App\Telegram\Middleware\User;

use App\Enums\Order\StatusEnum;
use App\Models\Order;
use SergiX44\Nutgram\Nutgram;

class EnsureNoActiveOrder
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $order = Order::whereHas('user', function ($query) use($bot) {
            $query->where('chat_id', $bot->userId());
        })
            ->latest()
            ->first();

        if($order) {
            $viewData = ['status' => $order->status];

            switch ($order->status) {
                case StatusEnum::PENDING_PAYMENT->value:
                    $bot->sendMessage(
                        text: view('telegram.user.middleware.ensure-no-active-order', $viewData),
                        chat_id: $bot->userId()
                    );
                    return;
                case StatusEnum::PENDING_EXCHANGE->value:
                    $bot->sendMessage(
                        text: view('telegram.user.middleware.ensure-no-active-order', $viewData),
                        chat_id: $bot->userId()
                    );
                    return;
            }
        }

        $next($bot);
    }
}
