<?php

namespace App\Telegram\Middleware;

use App\Telegram\Services\BotService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class SaveLastUserMessageId
{
    public function __invoke(Nutgram $bot, $next): void
    {
        BotService::saveUserMessageId($bot);
        $next($bot);
    }
}
