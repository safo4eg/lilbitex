<?php

namespace App\Telegram\Services;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class BotService
{
    public static function getReturnToMenuButton(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(
            text: 'Вернуться в меню',
            callback_data: 'command:start'
        );
    }

    /**
     * Сохранить идентификатор сообщения пользователя
     */
    public static function saveUserMessageId(Nutgram $bot): void
    {
        if($bot->message()) {
            $messageIds = $bot->getUserData(
                key: 'user.message_ids',
                userId: $bot->userId(),
                default: []
            );

            $messageIds[] = $bot->messageId();

            $bot->setUserData(
                key: 'user.message_ids',
                value: $messageIds,
                userId: $bot->userId()
            );
        }
    }

    /**
     * Сохранение сообщений бота
     */
    public static function saveBotLastMessageId(Nutgram $bot, int $messageId, int $chatId): void
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
        $botMessageIds = $bot->getUserData('bot.message_ids', $chatId, []);
        $userMessageIds = $bot->getUserData('user.message_ids', $chatId, []);

        $deletableMessageIds = array_merge($botMessageIds, $userMessageIds);

        if(!empty($deletableMessageIds)) {
            $bot->deleteMessages(
                chat_id: $chatId,
                message_ids: $deletableMessageIds
            );
        }

        $bot->setUserData('user.message_ids', null, $chatId);
        $bot->setUserData('bot.message_ids', null, $chatId);
//        BotService::deleteUserMessages($bot, $chatId);
//        BotService::deleteBotMessages($bot, $chatId);
    }

    /**
     * Удалить сообщения бота
     * @param Nutgram $bot - Фейк бот
     * @param int $chatId
     */
    private static function deleteBotMessages(Nutgram $bot, int $chatId): void
    {
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

        if($messageIds) {

            $bot->deleteMessages(
                chat_id: $chatId,
                message_ids: $messageIds
            );

            $bot->setUserData('user.message_ids', null, $chatId);
        }
    }
}