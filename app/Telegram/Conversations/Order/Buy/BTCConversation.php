<?php

namespace App\Telegram\Conversations\Order\Buy;

use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class BTCConversation extends Conversation
{
    public function start(Nutgram $bot)
    {
        $bot->sendMessage('Первый шаг покупки битка');
        $this->next('secondStep');
    }

    public function secondStep(Nutgram $bot)
    {
        $bot->sendMessage('Bye!');
        $this->end();
    }
}
