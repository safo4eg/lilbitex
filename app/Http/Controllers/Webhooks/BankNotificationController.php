<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\Order\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BTCService;
use App\Telegram\Conversations\User\PendingExchangeOrderMenu;
use App\Telegram\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class BankNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        BTCService $BTCService,
    )
    {
        // получаем сумму
        $notification = $request->getContent();
        $cleanedNotification = $this->cleanNotification($notification);
        $notificationData = json_decode($cleanedNotification, true);
        $amount = $this->extractAmount($notificationData['text']);

        $order = Order::with('user:id,chat_id')
            ->where('status', StatusEnum::PENDING_PAYMENT->value)
            ->where('sum_to_pay', $amount)
            ->first();

        if($order) {
            $order->status = StatusEnum::PENDING_EXCHANGE;
            $order->last_transaction_check = now();
            $order->save();

            $bot = app(Nutgram::class);

            // отправка меню пользователю
            BotService::clearBotHistory($bot, $order->user->chat_id);
            PendingExchangeOrderMenu::begin(
                bot: $bot,
                userId: $order->user->chat_id,
                chatId: $order->user->chat_id
            );

            $BTCService->sendBitcoin($order);

        }

        return response()->noContent(200);
    }

    /**
     * Очистить приходящий текст от лишних символов
     */
    private function cleanNotification(string $notification): string
    {
        $notification = preg_replace('/[^\P{C}\x{20}-\x{10FFFF}]/u', '', $notification);
        $notification = preg_replace('/\x{A0}/u', ' ', $notification);
        $notification = preg_replace('/\s{2,}/u', '', $notification);
        $notification = str_replace('�', '', $notification);
        return $notification;
    }

    /**
     * Извлекает сумму пополенения из уведомления
     * @return ?array - где 0 - целая часть (amount и order), 1 - копейки
     */
    private function extractAmount(string $notificationText): ?string
    {
        if (preg_match('/Пополнение.*?(\d+(?:,\d{2})?)\s*₽/', $notificationText, $matches)) {
            return str_replace(',', '.', $matches[1]);
        }

        return null;
    }
}
