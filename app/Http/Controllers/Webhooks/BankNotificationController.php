<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\Order\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AmountRequest;
use App\Models\Order;
use App\Services\API\BlockStreamAPIService;
use App\Services\BTCService;
use App\Telegram\Conversations\Order\Buy\UserPendingExchangeOrderMenu;
use App\Telegram\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class BankNotificationController extends Controller
{
    public function __invoke(Request $request, BTCService $service, BlockStreamAPIService $blockStreamAPIService)
    {
        Log::channel('single')->debug($request->all());

        // получаем сумму
        $amount = $request->getContent();
        Log::channel('single')->debug($amount);

        $order = Order::with('user:id,chat_id')
            ->where('status', StatusEnum::PENDING_PAYMENT->value)
            ->where('sum_to_pay', $amount)
            ->first();

        if($order) {
            Log::channel('single')->debug('test');
            $order->update([
                'status' => StatusEnum::PENDING_EXCHANGE->value,
                'last_transaction_check' => now()
            ]);

            $bot = app(Nutgram::class);

            // отправка меню пользователю
            BotService::clearBotHistory($bot, $order->user->chat_id);
            UserPendingExchangeOrderMenu::begin(
                bot: $bot,
                userId: $order->user->chat_id,
                chatId: $order->user->chat_id
            );

            $txHex = $service->createSignedTransaction($order);

            if($txHex === -1) {
                // не удалось создать и подписать транзакцию:
                // очистка сообщения пользователя
                // показ InlineMenu
            }

            $txid = $blockStreamAPIService->broadcastTransaction($txHex);

            if($txid === -1) {
//                 не удалось отправить транзакцию в сеть
            }

            $order->update([
                'txid' => $txid,
                'status' => StatusEnum::COMPLETED
            ]);
        }
//
        return response()->noContent(200);
    }
}
