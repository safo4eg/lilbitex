<?php

namespace App\Telegram\Services;

use SergiX44\Nutgram\Nutgram;

class BotService
{
    /**
     * Сохранить идентификатор сообщения пользователя
     */
    public static function saveUserMessageId(Nutgram $bot): void
    {
        if($bot->message()) {
            $messageIds = $bot->getUserData('user.message_ids');
            $lastMessageId = $bot->getUserData('user.last_message_id');

            if (!$messageIds) {
                $messageIds = [];
            }

            if ($lastMessageId) {
                $messageIds[] = $lastMessageId;
            }

            $lastMessageId = $bot->messageId();

            $bot->setUserData('user.message_ids', $messageIds, $bot->chatId());
            $bot->setUserData('user.last_message_id', $lastMessageId, $bot->chatId());
        }
    }

    /**
     * Удалить сообщения пользователя
     */
    public static function deleteUserMessages(Nutgram $bot): void
    {
        $messageIds = $bot->getUserData('user.message_ids');
        $lastMessageId = $bot->getUserData('user.last_message_id');

        if($messageIds) {
            $bot->deleteMessages(
                chat_id: $bot->chatId(),
                message_ids: $messageIds
            );

            if($lastMessageId) {
                $messageIds = [$lastMessageId];
                $bot->setUserData('user.last_message_id', null, $bot->chatId());
            }

            $bot->setUserData('user.message_ids', $messageIds, $bot->chatId());
        }
    }

    /**
     * Сохранение сообщений бота
     */
    public static function saveBotLastMessageId(Nutgram $bot, ?int $messageId): void
    {
        $messageIds = $bot->getUserData('bot.message_ids');
        $lastMessageId = $bot->getUserData('bot.last_message_id');

        if ($messageIds === null) {
            $messageIds = [];
        }

        if ($lastMessageId !== null) {
            $messageIds[] = $lastMessageId;
        }

        $lastMessageId = $messageId;

        $bot->setUserData('bot.message_ids', $messageIds, $bot->chatId());
        $bot->setUserData('bot.last_message_id', $lastMessageId, $bot->chatId());
    }

    /**
     * Удалить сохраненные значения бота
     */
    public static function deleteBotMessages(Nutgram $bot): void
    {
        self::saveBotLastMessageId($bot, null);

        $messageIds = $bot->getUserData('bot.message_ids');

        if($messageIds) {
            $bot->deleteMessages(
                chat_id: $bot->chatId(),
                message_ids: $messageIds
            );

            $bot->setUserData('bot.message_ids', null, $bot->chatId());
        }
    }
}