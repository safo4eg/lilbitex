<?php

namespace App\Telegram\Conversations;

use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

abstract class InlineMenuWithSaveMessageId extends InlineMenu
{
    protected function doOpen(string $text, InlineKeyboardMarkup $buttons, array $opt): Message|null
    {
        return $this->bot->sendMessageWithSaveId(...[
            'reply_markup' => $buttons,
            'text' => $text,
            'chat_id' => $this->chatId,
            ...$opt,
        ]);
    }
}