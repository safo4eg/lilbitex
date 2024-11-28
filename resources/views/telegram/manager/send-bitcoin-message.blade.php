@if($typeValue === \App\Enums\Order\BitcoinSendReasonEnum::TRANSACTION_CREATE_ERROR->value)
🔴 Обмен №{{$orderNumber}} - ошибка при создании транзакции.
✅ Оплата была успешно проверена, данное действие безопасное.

▶️ Нажмите кнопку "Отправить биток" для повторной попытки.
⚠️ Если повторная отправка не происходит, обратитесь к программисту.
@elseif($typeValue === \App\Enums\Order\BitcoinSendReasonEnum::TRANSACTION_SEND_ERROR->value)
🔴 Обмен №{{$orderNumber}} - ошибка при отправки транзакции.
✅ Оплата была успешно проверена, данное действие безопасное.

▶️ Нажмите кнопку "Отправить биток" для повторной попытки.
⚠️ Если повторная отправка не происходит, обратитесь к программисту.
@elseif(\App\Enums\Order\BitcoinSendReasonEnum::CHECK_PAYMENT_AND_SEND_BITCOIN->value)
USER ID: <b>{{$userId}}</b> USERNAME: {{'@'.$username}}
Просит проверить оплату обмена:
🔸Под номером <b>{{$orderNumber}}</b>, созданного <b>{{$orderCreatedAt}} (ПО МСК)</b>
🔸С суммой оплаты <b>№{{$sumToPay}}</b>

⚠️ Убедитесь, что поступил платеж в промежутке от создания обмена в течении 10 минут.
⚠️ Если есть похожий платеж, но время не совпадает, напишите пользователю для уточнения.
@endif

Детали обмена:
🔹 Тип кошелька: {{$walletType}}
🔹 BTC-адрес: {{$walletAddress}}
🔹 Сумма к отправке: {{$sumToSend}} BTC