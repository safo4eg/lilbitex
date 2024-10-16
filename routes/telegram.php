<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use SergiX44\Nutgram\Nutgram;
use \SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllGroupChats;
use \SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllPrivateChats;
use \App\Telegram\Middleware\EnsureUserChat;
use \App\Telegram\Middleware\EnsureManagerChat;
use App\Telegram\Commands\User\StartCommand;
use \App\Telegram\Conversations;
use \App\Telegram\Middleware\CleanBotHistory;

// глобальные middleware
$bot->middleware(CleanBotHistory::class);

// обработка групповых чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('manager', function (Nutgram $bot) {
       return $bot->sendMessage('Команда в чате менеджеров');
    });
})->middleware(EnsureManagerChat::class);

// обработка приватных чатов
$bot->group(function (Nutgram $bot) {
    $bot->registerCommand(StartCommand::class);
    $bot->onText(__('commands.start.menu.buy.btc'), Conversations\Order\Buy\BTCConversation::class);
})->middleware(EnsureUserChat::class);