<?php

namespace App\Telegram\Services;

use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

final class ConversationService
{
    /**
     * Сохранить идентификатор сообщения пользователя из диалога (воронки) в контейнер
     */
    public static function saveUserMessageId(Nutgram $bot): void
    {
        if($bot->message()) {
            $messageIds = $bot->getUserData('conversation.user_message_ids');

            if ($messageIds === null) {
                $messageIds = [];
            }

            $messageIds[] = $bot->messageId();
            $bot->setUserData('conversation.user_message_ids', $messageIds, $bot->chatId());
        }
    }

    /**
     * Удалить сообщения пользователя
     */
    public static function deleteUserMessages(Nutgram $bot): void
    {
        $messageIds = $bot->getUserData('conversation.user_message_ids');
        Log::channel('single')->debug($messageIds);
        if($messageIds !== null) {
            $bot->deleteMessages(
                chat_id: $bot->chatId(),
                message_ids: $messageIds
            );
            Log::channel('single')->debug('зашло сюда');
            $bot->setUserData('conversation.user_message_ids', null, $bot->chatId());
        }
    }

    /**
     * Сохранение сообщений бота
     */
    public static function saveBotLastMessageId(Nutgram $bot, ?int $messageId): void
    {
        $messageIds = $bot->getUserData('conversation.bot_message_ids');
        $lastMessageId = $bot->getUserData('conversation.bot_last_message_id');

        if ($messageIds === null) {
            $messageIds = [];
        }

        if ($lastMessageId !== null) {
            $messageIds[] = $lastMessageId;
        }

        $lastMessageId = $messageId;

        $bot->setUserData('conversation.bot_message_ids', $messageIds, $bot->chatId());
        $bot->setUserData('conversation.bot_last_message_id', $lastMessageId, $bot->chatId());
    }

    /**
     * Удалить сохраненные значения бота
     */
    public static function deleteBotMessages(Nutgram $bot): void
    {
        self::saveBotLastMessageId($bot, null);

        $messageIds = $bot->getUserData('conversation.bot_message_ids');

        if($messageIds !== null) {
            $bot->deleteMessages(
                chat_id: $bot->chatId(),
                message_ids: $messageIds
            );

            $bot->setUserData('conversation.bot_message_ids', null, $bot->chatId());
        }
    }
}