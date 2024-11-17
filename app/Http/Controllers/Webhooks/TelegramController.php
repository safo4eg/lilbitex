<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Telegram\Services\BotService;
use SergiX44\Nutgram\Nutgram;

class TelegramController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Nutgram $bot)
    {
        $bot->run();
    }

    private function sendMessageWithSaveMessageId(...$args)
    {

    }
}
