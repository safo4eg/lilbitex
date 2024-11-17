<?php

namespace App\Telegram\Commands\User;

use App\Telegram\Conversations\Order\Buy\PendingPaymentOrderMenu;
use App\Telegram\Conversations\Order\OrderBuyShowMenu;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

class OrderCommand extends Command
{
    protected string $command = 'order';

    protected ?string $description = 'открытый счет';

    public function handle(Nutgram $bot): void
    {
        PendingPaymentOrderMenu::begin(bot: $bot, userId: $bot->userId(), chatId: $bot->chatId());
    }
}
