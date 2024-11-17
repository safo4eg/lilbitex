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
     * Сохранение сообщений бота
     */
    public static function saveBotLastMessageId(Nutgram $bot, ?int $messageId, int $chatId): void
    {
        $messageIds = $bot->getUserData('bot.message_ids', $chatId);
        $lastMessageId = $bot->getUserData('bot.last_message_id', $chatId);

        if ($messageIds === null) {
            $messageIds = [];
        }

        if ($lastMessageId !== null) {
            $messageIds[] = $lastMessageId;
        }

        $lastMessageId = $messageId;

        $bot->setUserData('bot.message_ids', $messageIds, $chatId);
        $bot->setUserData('bot.last_message_id', $lastMessageId, $chatId);
    }

    /**
     * @param Nutgram $bot
     * @param int $chatId
     * @return void
     */
    public static function clearBotHistory(Nutgram $bot, int $chatId): void
    {
        BotService::deleteUserMessages($bot, $chatId);
        BotService::deleteBotMessages($bot, $chatId);
    }

    /**
     * Удалить сообщения бота
     * @param Nutgram $bot - Фейк бот
     * @param int $chatId
     */
    private static function deleteBotMessages(Nutgram $bot, int $chatId): void
    {
        self::saveBotLastMessageId($bot, null, $chatId);

        $messageIds = $bot->getUserData('bot.message_ids', $chatId);

        if($messageIds) {
            $bot->deleteMessages(
                chat_id: $chatId,
                message_ids: $messageIds
            );

            $bot->setUserData('bot.message_ids', null, $chatId);
        }
    }

    /**
     * Удалить сообщения пользователя
     * @param Nutgram $bot - Фейк бот
     * @param int $chatId
     */
    private static function deleteUserMessages(Nutgram $bot, int $chatId): void
    {
        $messageIds = $bot->getUserData('user.message_ids', $chatId);
        $lastMessageId = $bot->getUserData('user.last_message_id', $chatId);

        if($messageIds) {
            if($lastMessageId) {
                $messageIds[] = $lastMessageId;
                $bot->setUserData('user.last_message_id', null, $chatId);
            }

            $bot->deleteMessages(
                chat_id: $chatId,
                message_ids: $messageIds
            );

            $bot->setUserData('user.message_ids', $messageIds, $chatId);
        }
    }
}