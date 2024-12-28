ID: {{$user->chat_id}}, {{'@'.$user->username}}
Статус аккаунта: <b>{{$user->deleted_at ? 'Заблокирован': 'Активный'}}</b>
Количество обменов: {{$user->completed_orders_count}}
Сумма обменов: {{$user->total_amount}} RUB
Персональная скидка: {{$user->personal_discount}}%