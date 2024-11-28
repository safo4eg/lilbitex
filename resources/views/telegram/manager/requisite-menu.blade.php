@if($enabledRequisite)
======================
💳 Активные реквизиты:
======================
🏦 Банк: {{ $enabledRequisite->bank_name }}
📞 Телефон: {{ $enabledRequisite->phone }}
👤 Инициалы: {{ $enabledRequisite->initials }}

❌ Нажмите "Сбросить реквизиты" для перенос текущих реквизитов в статус "Неактивные".
⚠️ При отсуствии активных реквизитов пользователь не сможет оформлять заявку.
@else
В данный момент активные реквизиты отсутствуют.
@endif

🔄 Нажмите "Обновить реквизиты" для добавления новых реквизитов.
⚠️ Текущие реквизиты станут неактивными.