<?php

namespace App\Telegram\Conversations\Manager;

use App\Enums\AssetEnum;
use App\Enums\WalletTypeEnum;
use App\Helpers\BTCHelper;
use App\Models\ExchangerSetting;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class SettingsMenu extends InlineMenu
{
    public string $setting_id;

    public function start(Nutgram $bot)
    {
        $inlineButtons = [];
        $walletTypeNames = WalletTypeEnum::getBTCWalletTypesName();
        foreach (WalletTypeEnum::cases() as $walletType) {
            $inlineButtons[] = InlineKeyboardButton::make(
                text: $walletTypeNames[$walletType->value],
                callback_data: $walletType->value . '@handleChooseSettings'
            );
        }

        $this->menuText('Выберите настройки для изменений')
            ->addButtonRow(...$inlineButtons)
            ->orNext('none')
            ->showMenu();
    }

    public function handleChooseSettings(Nutgram $bot): void
    {
        $data = $bot->callbackQuery()->data;

        if(WalletTypeEnum::EXTERNAL->value === (int) $data) {
            $this->showExternalSettings();
            return;
        }

        if(WalletTypeEnum::BIGMAFIA->value === (int) $data) {
            $this->showBigmafiaSettings();
            return;
        }
    }

    public function showBigmafiaSettings(): void
    {

    }

    public function showExternalSettings(): void
    {
        $setting = ExchangerSetting::where('asset', AssetEnum::BTC->value)
            ->where('wallet_type', WalletTypeEnum::EXTERNAL->value)
            ->first();

        $viewData = [
            'walletTypeName' => WalletTypeEnum::getWalletTypesName()[$setting->wallet_type],
            'rate' => $setting->rate,
            'minAmount' => $setting->min_amount,
            'maxAmount' => $setting->max_amount,
            'exchangerFee' => $setting->exchanger_fee,
        ];

        $this->menuText(view('telegram.manager.show-external-settings', $viewData))
            ->clearButtons()
            ->addButtonRow(InlineKeyboardButton::make(
                text: 'Изменить комиссию обменника',
                callback_data: '@showChangeExchangerFee'
            ))
            ->addButtonRow(InlineKeyboardButton::make(
                text: 'Изменить минималку',
                callback_data: '@showChangeMinAmount'
            ))
            ->addButtonRow(InlineKeyboardButton::make(
                text: 'Изменить максималку',
                callback_data: '@showChangeMaxAmount'
            ))
            ->orNext('none')
            ->showMenu();
    }

    public function showChangeExchangerFee(Nutgram $bot): void
    {
        $this->menuText('Введите комиссию обменника в процентах от 0 до 100')
            ->orNext('handleChangeExchangerFee')
            ->showMenu();
    }

    public function handleChangeExchangerFee(Nutgram $bot): void
    {
        $fee = $bot->message()->text;

        if(preg_match('/^(100|[1-9]?[0-9])$/', $fee)) {
            $bot->sendMessage('testt');
            return;
        }

        $this->closeMenu();
        $this->menuText('⚠️ Введенная комиссия должна быть в диапазоне от 0 до 100!')
            ->showMenu();
    }

    public function showChangeMinAmount(Nutgram $bot): void
    {
        $bot->sendMessage('изменить минималку');
    }

    public function showChangeMaxAmount(Nutgram $bot): void
    {
        $bot->sendMessage('изменить максималку');
    }

    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Bye!');
        $this->end();
    }
}
