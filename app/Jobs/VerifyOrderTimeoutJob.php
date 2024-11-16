<?php

namespace App\Jobs;

use App\Enums\Order\StatusEnum;
use App\Exceptions\SilentVerifyOrderTimeoutJobException;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Carbon;

class VerifyOrderTimeoutJob implements ShouldQueue
{
    use Queueable;

    #[WithoutRelations]
    public Order $order;
    public $deleteWhenMissingModels = true;
    public $backoff = 0;
    public $tries = 0;
    public $orderTimeLimit = 600; // количество секунд которе может жить задача в статусе ожидания оплаты

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->onQueue('order');
    }

    public function handle(): void
    {
        if($this->order->status === StatusEnum::PENDING_PAYMENT->value) {
            $createdAtCarbon = Carbon::parse($this->order->created_at);
            $elapsedTime = now()->diffInSeconds($createdAtCarbon);

            if($elapsedTime > $this->orderTimeLimit) {
                $this->order->status = StatusEnum::CANCELLED->value;
            } else {
                throw new SilentVerifyOrderTimeoutJobException('Счет еще можно оплатить...');
            }
        }
    }
}
