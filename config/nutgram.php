<?php

return [
    // The Telegram BOT api token
    'token' => env('TELEGRAM_TOKEN'),

    // Проверка что запросы только от Telegram, при установке в .env режима production
    'safe_mode' => env('APP_ENV', 'local') === 'production',

    // Кастомные настройки какие-то
    'config' => [
        'groups' => [
            \App\Enums\GroupsEnum::MANAGER->value => (int) env('MANAGER_CHAT_ID')
        ]
    ],

    // Set if the service provider should automatically load
    // handlers from /routes/telegram.php
    'routes' => true,

    // Enable or disable Nutgram mixins
    'mixins' => false,

    // Путь генерации файлов через nutgram:make комманду
    'namespace' => app_path('Telegram'),

    // Логирование ошибок (канал)
    'log_channel' => env('TELEGRAM_LOG_CHANNEL', 'null'),
];
