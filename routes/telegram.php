<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use SergiX44\Nutgram\Nutgram;
use \SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllGroupChats;
use \SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllPrivateChats;
use \App\Telegram\Middleware\EnsureUserChat;
use \App\Telegram\Middleware\EnsureManagerChat;

// обработка групповых чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('manager', function (Nutgram $bot) {
       return $bot->sendMessage('Команда в чате менеджеров');
    });
})->middleware(EnsureManagerChat::class);

// обработка приватных чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('start', function (Nutgram $bot) {
        return $bot->sendMessage('Hello, world!');
    })->description('The start command!');
})->middleware(EnsureUserChat::class);