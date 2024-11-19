<?php

namespace App\Jobs;

use App\Enums\Order\StatusEnum;
use App\Exceptions\SilentVerifyOrderTimeoutJobException;
use App\Models\Order;
use App\Telegram\Conversations\Order\Buy\CancelledOrderMenu;
use App\Telegram\Services\BotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

class VerifyOrderTimeoutJob implements ShouldQueue
{
    use Queueable;

    public Order $order;
    public $backoff = 5;
    public $tries = 0;
    public $orderTimeLimit = 300; // количество секунд которе может жить счет в статусе ожидания оплаты

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->onQueue('orders');
    }

    public function handle(): void
    {
        if($this->order->status === StatusEnum::PENDING_PAYMENT->value) {
            $createdAtCarbon = Carbon::parse($this->order->created_at);
            $elapsedTime = $createdAtCarbon->diffInSeconds(now());

            if($elapsedTime > $this->orderTimeLimit) {
                $bot = app(Nutgram::class);

                $this->order->status = StatusEnum::CANCELLED->value;
                $this->order->save();

                BotService::clearBotHistory($bot, $this->order->user->chat_id);
                CancelledOrderMenu::begin(
                    bot: $bot,
                    userId: $this->order->user->chat_id,
                    chatId: $this->order->user->chat_id
                );
            } else {
                throw new SilentVerifyOrderTimeoutJobException('Счет еще можно оплатить...');
            }
        }
    }
}
