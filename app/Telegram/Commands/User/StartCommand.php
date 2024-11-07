<?php

namespace App\Telegram\Commands\User;

use App\Models\User;
use App\Telegram\Services\BotService;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

class StartCommand extends Command
{
    protected string $command = 'start';

    protected ?string $description = 'перезапустить бота';

    public function handle(Nutgram $bot): void
    {
        User::firstOrCreate([
            'chat_id' => $bot->user()->id,
            'first_name' => $bot->user()->first_name,
            'username' => $bot->user()->username
        ]);

        $bot->sendMessageWithSaveId(
            text: "Hello world",
            reply_markup: ReplyKeyboardMarkup::make(
                resize_keyboard: true,
                one_time_keyboard: false,
            )
                ->addRow(KeyboardButton::make(__('commands.start.menu.buy.btc')))
                ->addRow(
                    KeyboardButton::make(__('commands.start.menu.profile')),
                    KeyboardButton::make(__('commands.start.menu.info'))
                )
        );

    }
}
