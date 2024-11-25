@if($typeValue === \App\Enums\Order\BitcoinResendReasonEnum::TRANSACTION_CREATE_ERROR->value)
    🔴 Обмен №{{$orderNumber}} - ошибка при создании транзакции.
    ✅ Оплата была успешно проверена, данное действие безопасное.

    ▶️ Нажмите кнопку "Отправить биток" для повторной попытки.
    ⚠️ Если повторная отправка не происходит, обратитесь к программисту.
@elseif($typeValue === \App\Enums\Order\BitcoinResendReasonEnum::TRANSACTION_SEND_ERROR->value)
    🔴 Обмен №{{$orderNumber}} - ошибка при отправки транзакции.
    ✅ Оплата была успешно проверена, данное действие безопасное.

    ▶️ Нажмите кнопку "Отправить биток" для повторной попытки.
    ⚠️ Если повторная отправка не происходит, обратитесь к программисту.
@elseif(\App\Enums\Order\BitcoinResendReasonEnum::CHECK_PAYMENT_AND_SEND_BITCOIN->value)
    нужно проверить транзу
@endif

Детали обмена:
🔹 USER ID:{{$userId}} USERNAME: {{'@'.$username}}
🔹 Тип кошелька: {{$walletType}}
🔹 BTC-адрес: {{$walletAddress}}
🔹 Сумма к отправке: {{$sumToSend}} BTC