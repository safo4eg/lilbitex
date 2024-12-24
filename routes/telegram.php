<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Enums\GroupsEnum;
use App\Telegram\Commands\User\OrderCommand;
use App\Telegram\Commands\User\StartCommand;
use App\Telegram\Conversations;
use App\Telegram\Middleware\EnsureManagerChat;
use App\Telegram\Middleware\EnsureUserChat;
use App\Telegram\Middleware\Manager\EnsureBoss;
use App\Telegram\Middleware\User\ClearBotHistory;
use App\Telegram\Middleware\User\EnsureNoRepeatedCancellations;
use App\Telegram\Middleware\User\SaveLastUserMessageId;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use \App\Telegram\Middleware\User\EnsureActiveRequsiteExists;
use \App\Telegram\Middleware\User\EnsureNoActiveOrder;
use \App\Telegram\Handlers\Manager\SendBitcoinHandler;
use \App\Telegram\Handlers\Manager\CancelOrderHandler;
use \App\Telegram\Middleware\User\EnsureUserNotBanned;
use \App\Telegram\Handlers\User\ProfileHandler;
use \App\Telegram\Handlers\User\ManagerHandler;
use \App\Telegram\Handlers\User\SellDealHandler;
use \App\Telegram\Conversations\Manager\NotifyMenu;
use \App\Telegram\Handlers\User\InfoHandler;
use \Illuminate\Support\Facades\App;
use \Illuminate\Contracts\Debug\ExceptionHandler;

Conversation::refreshOnDeserialize();

// глобальные middleware
//$bot->middleware(SaveLastUserMessageId::class);

// обработка групповых чатов
$bot->group(function (Nutgram $bot) {
    $bot->group(function (Nutgram $bot) {
        $bot->onCommand('setting', Conversations\Manager\ExchangerSettingMenu::class)
            ->skipGlobalMiddlewares()
            ->description('Настройки обменника');
    })->middleware(EnsureBoss::class);

    $bot->onCommand('requisite', Conversations\Manager\RequisiteMenu::class)
        ->skipGlobalMiddlewares()
        ->description('Реквизиты');

    $bot->onCommand('user {id}', Conversations\Manager\UserMenu::class)
        ->whereNumber('id')
        ->skipGlobalMiddlewares()
        ->description('Найти пользователя по ID');

    $bot->onCommand('notify', NotifyMenu::class)
        ->skipGlobalMiddlewares()
        ->description('Рассылка');

    // обработка кнопки "Отправить биток"
    $bot->onCallbackQueryData('/btc/send/:{orderId}/:{typeValue}', SendBitcoinHandler::class)
        ->whereNumber('orderId')
        ->whereNumber('typeValue')
        ->skipGlobalMiddlewares();

    $bot->onCallbackQueryData("/btc/cancel/:{orderId}", CancelOrderHandler::class)
        ->whereNumber('orderId')
        ->skipGlobalMiddlewares();
})
    ->middleware(EnsureManagerChat::class)
    ->scope(new \SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeChat(config("nutgram.config.groups." . GroupsEnum::MANAGER->value)));

// обработка приватных чатов
$bot->group(function (Nutgram $bot) {
    $bot->onCommand('start', StartCommand::class)
        ->description('Главное меню');
    $bot->onCommand('order', OrderCommand::class)
        ->description('Последний заказ');

    $bot->onText('Купить BTC', Conversations\User\BtcConversation::class)
        ->middleware(EnsureNoRepeatedCancellations::class)
        ->middleware(EnsureActiveRequsiteExists::class)
        ->middleware(EnsureNoActiveOrder::class);
    $bot->onText('Профиль', ProfileHandler::class);
    $bot->onText('Наш менеджер', ManagerHandler::class);
    $bot->onText('Продать сделку', SellDealHandler::class);
    $bot->onText('Инфо', InfoHandler::class);

    $bot->onCallbackQueryData('command:start', StartCommand::class);

    // дл логирования сообщений пользователя
    $bot->onMessage(StartCommand::class);
})
    ->middleware(EnsureUserChat::class)
//    ->middleware(ClearBotHistory::class)
    ->middleware(EnsureUserNotBanned::class)
    ->scope(new \SergiX44\Nutgram\Telegram\Types\Command\BotCommandScopeAllPrivateChats());

$bot->onException(function (Nutgram $bot, \Throwable $exception) {
    $bot->sendMessageWithSaveId(
        text: 'Произошла непредвиденная ошибка',
        chat_id: $bot->chatId()
    );

    App::make(ExceptionHandler::class)->report($exception);
});