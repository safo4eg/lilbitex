<?php
namespace App\Telegram\Middleware;

use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

/**
 * Глобальный middleware
 * - удаляет последнее сообщение, которое написал пользователь
 * - удаляет последнее сообщение, кторое отправил бот
 */
class CleanBotHistory
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if($bot->message()) {
            $bot->deleteMessage(
                chat_id: $bot->chatId(),
                message_id: $bot->messageId()
            );
        }

        $lastBotMessageId = $bot->getUserData('lastBotMessageId', $bot->chatId());

        if($lastBotMessageId) {
            $bot->setUserData('lastBotMessageId', null, $bot->chatId());

            $bot->deleteMessage(
                chat_id: $bot->chatId(),
                message_id: $lastBotMessageId
            );
        }

        $next($bot);
    }
}
