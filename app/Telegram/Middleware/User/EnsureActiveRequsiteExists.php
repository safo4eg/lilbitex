<?php

namespace App\Telegram\Middleware\User;

use App\Enums\Requisite\StatusEnum;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Nutgram;

class EnsureActiveRequsiteExists
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $isEnabledRequisiteExists = DB::table('requisites')
            ->where('status', StatusEnum::ENABLED->value)
            ->exists();

        if(!$isEnabledRequisiteExists) {
            $bot->sendMessageWithSaveId(
                text: 'Покупка временно недоступна по техническим причинам',
                chat_id: $bot->userId()
            );
            return;
        }

        $next($bot);
    }
}
