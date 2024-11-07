<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use SergiX44\Nutgram\Nutgram;
use \App\Telegram\Middleware\EnsureUserChat;
use \App\Telegram\Middleware\EnsureManagerChat;
use App\Telegram\Commands\User\StartCommand;
use \App\Telegram\Conversations;
use \App\Telegram\Middleware\SaveLastUserMessageId;
use \App\Telegram\Middleware\ClearBotHistory;

// глобальные middleware
$bot->middleware(SaveLastUserMessageId::class);

// обработка групповых чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('manager', function (Nutgram $bot) {
       return $bot->sendMessage('Команда в чате менеджеров');
    });
})->middleware(EnsureManagerChat::class);

// обработка приватных чатов
$bot->group(function (Nutgram $bot) {
    $bot->registerCommand(StartCommand::class);
    $bot->onCommand('null', function (Nutgram $bot) {
        $bot->deleteUserData('bot.message_ids', $bot->userId());
        $bot->deleteUserData('bot.last_message_id', $bot->userId());
        $bot->deleteUserData('user.message_ids', $bot->userId());
        $bot->deleteUserData('user.last_message_id', $bot->userId());

        return $bot->deleteMessage($bot->chatId(), $bot->messageId());
    });
    $bot->onText(__('commands.start.menu.buy.btc'), Conversations\Order\Buy\BTCConversation::class);
})
    ->middleware(EnsureUserChat::class)
    ->middleware(ClearBotHistory::class);

$bot->onMessage(function (Nutgram $bot) {}); // нужно для логирования сообщений пользователя, иначе не сохраняются id