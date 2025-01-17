<?php

namespace App\Telegram\Middleware\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class EnsureUserNotBanned
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $user = User::withTrashed()
            ->where('chat_id', $bot->userId())
            ->first();

        if ($user && $user->deleted_at) {
            $bot->sendMessage(
                text: view('telegram.user.middleware.blocked-user', ['chatId' => $user->chat_id]),
                reply_markup: InlineKeyboardMarkup::make()
                    ->addRow(InlineKeyboardButton::make(
                        text: 'Написать менеджеру',
                        url: 'https://t.me/mamo227'
                    )),
                chat_id: $bot->userId()
            );
            return;
        }

        $next($bot);
    }
}
