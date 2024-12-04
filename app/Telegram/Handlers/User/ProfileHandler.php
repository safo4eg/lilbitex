<?php

namespace App\Telegram\Handlers\User;

use App\Models\User;
use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class ProfileHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $user = User::where('chat_id', $bot->userId())
            ->first();

        $bot->sendMessageWithSaveId(
            text: view('telegram.user.profile-handler', ['user' => $user]),
            reply_markup: InlineKeyboardMarkup::make()
                ->addRow(
                    InlineKeyboardButton::make(
                        text: 'Поддержка',
                        url: 'https://t.me/LiL_BIT_SUP'
                    )
                )
                ->addRow(BotService::getReturnToMenuButton()),
            chat_id: $bot->userId()
        );
    }
}
