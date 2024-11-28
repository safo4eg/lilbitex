<?php

namespace App\Telegram\Middleware\User;

use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;

class SaveLastUserMessageId
{
    public function __invoke(Nutgram $bot, $next): void
    {
        BotService::saveUserMessageId($bot);
        $next($bot);
    }
}
