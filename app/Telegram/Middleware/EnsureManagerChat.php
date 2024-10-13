<?php

namespace App\Telegram\Middleware;

use App\Enums\GroupsEnum;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class EnsureManagerChat
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $managerCharId = config("nutgram.config.groups." . GroupsEnum::MANAGER->value);

        if($bot->chat()->id !== $managerCharId) {
            return;
        }

        $next($bot);
    }
}
