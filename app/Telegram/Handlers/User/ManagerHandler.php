<?php

namespace App\Telegram\Handlers\User;

use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class ManagerHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendMessageWithSaveId(
            text: view('telegram.user.handlers.manager'),
            chat_id: $bot->userId(),
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow(InlineKeyboardButton::make(
                    text: 'Написать в поддержку',
                    url: 'https://t.me/Lilchikbitchik'
                ))
                ->addRow(BotService::getReturnToMenuButton())
        );
    }
}
