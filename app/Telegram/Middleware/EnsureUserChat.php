<?php

namespace App\Telegram\Middleware;

use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;

class EnsureUserChat
{
    public function __invoke(Nutgram $bot, $next): void
    {
        if($bot->chat()->type !== ChatType::PRIVATE) {
            return;
        }

        $next($bot);
    }
}
