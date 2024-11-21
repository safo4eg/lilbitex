<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\Order\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AmountRequest;
use App\Models\Order;
use App\Services\API\BlockStreamAPIService;
use App\Services\BTCService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankNotificationController extends Controller
{
    public function __invoke(Request $request, BTCService $service, BlockStreamAPIService $blockStreamAPIService)
    {
        // здесь принимаем уведомление
        // парсим уведомление на получение суммы

        // получаем сумму
        $amount = $request->input('amount');

        $order = Order::where('status', StatusEnum::PENDING_PAYMENT->value)
            ->where('sum_to_pay', $amount)
            ->first();

        if($order) {
            $txHex = $service->createSignedTransaction($order);
            $blockStreamAPIService->broadcastTransaction($txHex);
        }

        return response()->noContent(200);
    }
}
