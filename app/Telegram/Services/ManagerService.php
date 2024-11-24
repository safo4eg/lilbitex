<?php

namespace App\Telegram\Services;

use App\Enums\GroupsEnum;
use App\Telegram\Conversations\Manager\ManagerPendingExchangeOrderMenu;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class ManagerService
{
    private string $chat_id;
    private Nutgram $bot;

    public function __construct()
    {
        $this->bot = app(Nutgram::class);
        $this->chat_id = config('nutgram.config.groups.' . GroupsEnum::MANAGER->value);
    }

    public function showPendingExchangeOrderMenu(int $orderId, int $typeValue): void
    {
        ManagerPendingExchangeOrderMenu::begin(
            bot: $this->bot,
            userId: $this->chat_id,
            chatId: $this->chat_id,
            data: ['orderId' => $orderId, 'typeValue' => $typeValue]
        );
    }
}