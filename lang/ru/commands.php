<?php

return [
    'start' => [
        'menu' => [
            'buy' => [
                'btc' => 'Купить BTC'
            ],
            'profile' => 'Профиль',
            'info' => 'Инфо'
        ]
    ],

    'buy' => [
        'btc' => [
            'wallet_types' => \App\Enums\Order\WalletTypeEnum::getWalletTypesName()
        ]
    ]
];