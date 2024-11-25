<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Commands\User\OrderCommand;
use App\Telegram\Commands\User\StartCommand;
use App\Telegram\Conversations;
use App\Telegram\Middleware\ClearBotHistory;
use App\Telegram\Middleware\EnsureManagerChat;
use App\Telegram\Middleware\EnsureUserChat;
use App\Telegram\Middleware\SaveLastUserMessageId;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

Conversation::refreshOnDeserialize();

// глобальные middleware
$bot->middleware(SaveLastUserMessageId::class);

// обработка групповых чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('manager', function (Nutgram $bot) {
       return $bot->sendMessage('Команда в чате менеджеров');
    })->skipGlobalMiddlewares();

    // обработка кнопки "Отрпавить биток"
    $bot->onCallbackQueryData('/btc/send/:{orderId}/:{typeValue}', \App\Telegram\Handlers\Manager\SendBitcoinHandler::class)
        ->whereNumber('orderId')
        ->whereNumber('typeValue');
})
    ->middleware(EnsureManagerChat::class);

// обработка приватных чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('start', StartCommand::class);
    $bot->onCommand('order', OrderCommand::class);

    $bot->onCommand('null', function (Nutgram $bot) {
        $bot->deleteUserData('bot.message_ids', $bot->userId());
        $bot->deleteUserData('bot.last_message_id', $bot->userId());
        $bot->deleteUserData('user.message_ids', $bot->userId());
        $bot->deleteUserData('user.last_message_id', $bot->userId());

        return $bot->deleteMessage($bot->chatId(), $bot->messageId());
    });

    $bot->onCommand('test', Conversations\ChooseColorMenu::class);

    $bot->onText(__('commands.start.menu.buy.btc'), Conversations\User\BtcConversation::class);

    // дл логирования сообщений пользователя
    $bot->onMessage(function (Nutgram $bot) {});
})
    ->middleware(EnsureUserChat::class)
    ->middleware(ClearBotHistory::class);