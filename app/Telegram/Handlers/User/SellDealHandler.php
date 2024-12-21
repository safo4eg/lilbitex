<?php

namespace App\Telegram\Handlers\User;

use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class SellDealHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendMessageWithSaveId(
            text: view('telegram.user.handlers.sell-deal'),
            chat_id: $bot->userId(),
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow(InlineKeyboardButton::make(
                    text: 'Написать',
                    url: 'https://t.me/LILbit_sdelki'
                ))
                ->addRow(BotService::getReturnToMenuButton())
        );
    }
}
