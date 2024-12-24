<?php

namespace App\Telegram\Conversations;

use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

abstract class InlineMenuWithSaveMessageId extends InlineMenu
{
    protected function doOpen(string $text, InlineKeyboardMarkup $buttons, array $opt): Message|null
    {
        $chatId = empty($this->chatId)
            ? $this->bot->chatId()
            : $this->chatId;

        return $this->bot->sendMessage(...[
            'reply_markup' => $buttons,
            'text' => $text,
            'chat_id' => $chatId,
            ...$opt,
        ]);
    }
}