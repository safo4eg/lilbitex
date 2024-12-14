<?php

namespace App\Telegram\Handlers\User;

use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class InfoHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendMessageWithSaveId(
            text: view('telegram.user.handlers.info'),
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow(InlineKeyboardButton::make(
                    text: 'Написать менеджеру',
                    url: 'https://t.me/Lilchikbitchik'
                ))
                ->addRow(BotService::getReturnToMenuButton()),
            chat_id: $bot->userId()
        );
    }
}
