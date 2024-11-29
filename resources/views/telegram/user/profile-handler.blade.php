ID: {{$user->id}}, {{'@'.$user->username}}
Количество обменов: {{$user->completed_orders_count ?? 0}}
Сумма обменов: {{$user->total_amount ?? 0}}
Персональная скидка: {{$user->personal_discount}}%