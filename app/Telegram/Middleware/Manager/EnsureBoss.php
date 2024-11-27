<?php

namespace App\Telegram\Middleware\Manager;

use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class EnsureBoss
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $bossChatId = config('nutgram.config.boss_chat_id');

        if($bot->userId() === (int) $bossChatId) {
            $next($bot);
        }
    }
}
