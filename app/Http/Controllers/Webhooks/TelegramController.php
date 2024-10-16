<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use SergiX44\Nutgram\Nutgram;

class TelegramController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Nutgram $bot)
    {
        // глобально сохраняет последнее сообщание
        Nutgram::macro('sendMessageWithSaveMessageId', function (...$args) {
            $message = $this->sendMessage(...$args);
            $this->setUserData('lastBotMessageId', $message->message_id, $message->chat()->id);
        });

        $bot->run();
    }

    private function sendMessageWithSaveMessageId(...$args)
    {

    }
}
