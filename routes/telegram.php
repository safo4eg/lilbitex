<?php
/** @var SergiX44\Nutgram\Nutgram $bot */

use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\EC;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use \App\Telegram\Middleware\EnsureUserChat;
use \App\Telegram\Middleware\EnsureManagerChat;
use App\Telegram\Commands\User\StartCommand;
use App\Telegram\Commands\User\OrderCommand;
use \App\Telegram\Conversations;
use \App\Telegram\Middleware\SaveLastUserMessageId;
use \App\Telegram\Middleware\ClearBotHistory;

Conversation::refreshOnDeserialize();

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
    $bot->onCommand('start', StartCommand::class);
    $bot->onCommand('order', OrderCommand::class);

    $bot->onCommand('null', function (Nutgram $bot) {
        $bot->deleteUserData('bot.message_ids', $bot->userId());
        $bot->deleteUserData('bot.last_message_id', $bot->userId());
        $bot->deleteUserData('user.message_ids', $bot->userId());
        $bot->deleteUserData('user.last_message_id', $bot->userId());

        return $bot->deleteMessage($bot->chatId(), $bot->messageId());
    });

    $bot->onCommand('test', function (Nutgram $bot, \App\Services\API\BlockStreamAPIService $blockStreamAPIService) {
        $res = $blockStreamAPIService->getAddressUTXO('n3RGTBgLrv9pa1girPGaytTX8Suz5q2JS1');
        Log::channel('single')->debug($res);
    });

//    $bot->onMessage(function (Nutgram $bot, \App\Services\BTCService $BTCService) {
//        $text = $bot->message()->text;
//
//        $BTCService->validateAmountFormat($text);
//
//        $bot->sendMessage('обработка');
//    });

    $bot->onText(__('commands.start.menu.buy.btc'), Conversations\Order\Buy\BTCConversation::class);
})
    ->middleware(EnsureUserChat::class)
    ->middleware(ClearBotHistory::class);

$bot->onMessage(function (Nutgram $bot) {}); // нужно для логирования сообщений пользователя, иначе не сохраняются id