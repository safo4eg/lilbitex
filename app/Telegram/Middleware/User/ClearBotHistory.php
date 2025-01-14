<?php

namespace App\Telegram\Middleware\User;

use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;

/**
 * Производит очистку предыдущих сообщений
 * - если вызвана команда
 * - если была нажата кнопка меню (запуск диалогов, менюшек и тд)
 */

class ClearBotHistory
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $next($bot);

//        BotService::clearBotHistory($bot, $bot->userId());
    }
}
