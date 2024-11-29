@if($status === \App\Enums\Order\StatusEnum::PENDING_PAYMENT->value)
У вас имеется неоплаченный заказ на обмен,
Прежде чем создать новый, отмените или оплатите его.
@elseif($status === \App\Enums\Order\StatusEnum::PENDING_EXCHANGE->value)
У вас имеется заказ ожидающий отправки битка,
дождитесь завершения обмена прежде, чем создавать новый.
@endif

Напишите /order для открытия меню с последним заказом.