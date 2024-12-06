ID: {{$user->id}}, {{'@'.$user->username}}
Количество обменов: {{$user->completed_orders_count ?? 0}}
Сумма обменов: {{$user->total_amount ?? 0}} РУБ
Персональная скидка: {{$user->personal_discount}}%