<?php

namespace App\Telegram\Commands\User;

use App\Models\User;
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
        $user = User::firstWhere('chat_id', $bot->userId());
        
        if(!$user) {
            User::create([
                'chat_id' => $bot->userId(),
                'first_name' => $bot->user()->first_name,
                'username' => $bot->user()->username
            ]);
        }

        $bot->sendMessageWithSaveId(
            text: "Добро пожаловать в lilbitex",
            reply_markup: ReplyKeyboardMarkup::make(
                resize_keyboard: true,
                one_time_keyboard: false,
            )
                ->addRow(KeyboardButton::make(__('commands.start.menu.buy.btc')))
                ->addRow(KeyboardButton::make('Наш менеджер'))
                ->addRow(
                    KeyboardButton::make(__('commands.start.menu.profile')),
                    KeyboardButton::make(__('commands.start.menu.info'))
                ),
            chat_id: $bot->chatId()
        );

    }
}
