<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Commands\User\OrderCommand;
use App\Telegram\Commands\User\StartCommand;
use App\Telegram\Conversations;
use App\Telegram\Middleware\EnsureManagerChat;
use App\Telegram\Middleware\EnsureUserChat;
use App\Telegram\Middleware\Manager\EnsureBoss;
use App\Telegram\Middleware\User\ClearBotHistory;
use App\Telegram\Middleware\User\SaveLastUserMessageId;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use \App\Telegram\Middleware\User\EnsureActiveRequsiteExists;
use \App\Telegram\Middleware\User\EnsureNoActiveOrder;
use \App\Telegram\Handlers\Manager\SendBitcoinHandler;
use \App\Telegram\Handlers\Manager\CancelOrderHandler;
use \App\Telegram\Middleware\User\EnsureUserNotBanned;

Conversation::refreshOnDeserialize();

// глобальные middleware
$bot->middleware(SaveLastUserMessageId::class);

// обработка групповых чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('manager', function (Nutgram $bot) {
       return $bot->sendMessage('Команда в чате менеджеров');
    })->skipGlobalMiddlewares();

    $bot->group(function (Nutgram $bot) {
        $bot->onCommand('setting', Conversations\Manager\ExchangerSettingMenu::class)
            ->skipGlobalMiddlewares();
        $bot->onCommand('requisite', Conversations\Manager\RequisiteMenu::class)
            ->skipGlobalMiddlewares();
    })->middleware(EnsureBoss::class);

    // обработка кнопки "Отправить биток"
    $bot->onCallbackQueryData('/btc/send/:{orderId}/:{typeValue}', SendBitcoinHandler::class)
        ->whereNumber('orderId')
        ->whereNumber('typeValue')
        ->skipGlobalMiddlewares();

    $bot->onCallbackQueryData("/btc/cancel/:{orderId}", CancelOrderHandler::class)
        ->whereNumber('orderId')
        ->skipGlobalMiddlewares();

    $bot->onCommand('test', Conversations\ChooseColorMenu::class);
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

    $bot->onText('Купить BTC', Conversations\User\BtcConversation::class)
        ->middleware(EnsureActiveRequsiteExists::class)
        ->middleware(EnsureNoActiveOrder::class);

    // дл логирования сообщений пользователя
    $bot->onMessage(function (Nutgram $bot) {});
})
    ->middleware(EnsureUserChat::class)
    ->middleware(ClearBotHistory::class)
    ->middleware(EnsureUserNotBanned::class);