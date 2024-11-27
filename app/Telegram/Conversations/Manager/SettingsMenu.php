<?php

namespace App\Telegram\Conversations\Manager;

use App\Enums\AssetEnum;
use App\Enums\Manager\SettingEnum;
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
                callback_data: $walletType->value . '@showSettingsMenu'
            );
        }

        $this->menuText('Выберите настройки для изменений')
            ->addButtonRow(...$inlineButtons)
            ->orNext('none')
            ->showMenu();
    }

    public function showSettingsMenu(Nutgram $bot): void
    {
        $setting = null;
        if(isset($this->setting_id)) {
            $setting = ExchangerSetting::find($this->setting_id);
        } else {
            $walletTypeValue = $bot->callbackQuery()->data;
            $setting = ExchangerSetting::where('asset', AssetEnum::BTC->value)
                ->where('wallet_type', $walletTypeValue)
                ->first();
            $this->setting_id = $setting->id;
        }

        $viewData = [
            'walletTypeName' => WalletTypeEnum::getWalletTypesName()[$setting->wallet_type],
            'rate' => $setting->rate,
            'minAmount' => $setting->min_amount,
            'maxAmount' => $setting->max_amount,
            'exchangerFee' => $setting->exchanger_fee,
        ];

        $menuBuilder = $this->menuText(view('telegram.manager.show-settings', $viewData))
            ->clearButtons();

        $buttons = $this->getSettingsButtons();
        foreach ($buttons as $button) {
            $menuBuilder->addButtonRow($button);
        }

        $menuBuilder
            ->orNext('none')
            ->showMenu();
    }

    public function showChangeExchangerFee(Nutgram $bot): void
    {
        $menuBuilder = $this->menuText('Введите комиссию обменника в процентах от 0 до 100')
            ->clearButtons();

        $buttons = $this->getSettingsButtons(SettingEnum::EXCHANGER_FEE->value);
        foreach ($buttons as $button) {
            $menuBuilder->addButtonRow($button);
        }

        $menuBuilder->orNext('handleChangeExchangerFee')
            ->showMenu();
    }

    public function handleChangeExchangerFee(Nutgram $bot): void
    {
        $fee = $bot->message()->text;
        $errMessage = '';

        if(preg_match('/^(100|[1-9]?[0-9])$/', $fee) === 0) {
            $errMessage = '⚠️ Введенная комиссия должна быть в диапазоне от 0 до 100!';
        }

        // доп проверки если нужны

        if(empty($errMessage) === false) {
            $this->closeMenu();
            $this->menuText($errMessage)
                ->showMenu();
            return;
        }

        // меняем комиссию
        try {
            ExchangerSetting::where('id', $this->setting_id)
                ->update(['exchanger_fee' => $fee]);

            $this->closeMenu();
            $this->showSettingsMenu($bot);
        } catch (\Exception $e) {
            $this->bot->sendMessage('Ошибка при обновлении БД, повторите попытку.');
            return;
        }
    }

    public function showChangeMinAmount(Nutgram $bot): void
    {
        $menuBuilder = $this->menuText('Введите желаемую минималку от 0 до 100000')
            ->clearButtons();

        $buttons = $this->getSettingsButtons(SettingEnum::MIN_AMOUNT->value);
        foreach ($buttons as $button) {
            $menuBuilder->addButtonRow($button);
        }

        $menuBuilder->orNext('handleChangeMinAmount')
            ->showMenu();
    }

    public function handleChangeMinAmount(Nutgram $bot): void
    {
        $amount = $bot->message()->text;
        $errMessage = '';

        if(preg_match('/^([1-9]?\d{1,5}|100000)$/', $amount) === 0) {
            $errMessage = '⚠️ Введенная минималка должна быть в диапазоне от 0 до 100000!';
        }

        $setting = ExchangerSetting::find($this->setting_id);
        if((int) $amount >= $setting->max_amount && empty($errMessage)) {
            $errMessage = "⚠️ Введенная минималка не может быть равной либо больше максималки ({$setting->max_amount})";
        }

        if(empty($errMessage) === false) {
            $this->closeMenu();
            $this->menuText($errMessage)
                ->showMenu();
            return;
        }

        // меняем минималку
        try {
            $setting->update(['min_amount' => $amount]);

            $this->closeMenu();
            $this->showSettingsMenu($bot);
        } catch (\Exception $e) {
            $this->bot->sendMessage('Ошибка при обновлении БД, повторите попытку.');
            return;
        }
    }

    public function showChangeMaxAmount(Nutgram $bot): void
    {
        $menuBuilder = $this->menuText('Введите желаемую максималку от 0 до 100000')
            ->clearButtons();

        $buttons = $this->getSettingsButtons(SettingEnum::MAX_AMOUNT->value);
        foreach ($buttons as $button) {
            $menuBuilder->addButtonRow($button);
        }

        $menuBuilder->orNext('handleChangeMaxAmount')
            ->showMenu();
    }

    public function handleChangeMaxAmount(Nutgram $bot): void
    {
        $amount = $bot->message()->text;
        $errMessage = '';

        if(preg_match('/^([1-9]?\d{1,5}|100000)$/', $amount) === 0) {
            $errMessage = '⚠️ Введенная максималка должна быть в диапазоне от 0 до 100000!';
        }

        $setting = ExchangerSetting::find($this->setting_id);
        if((int) $amount <= $setting->min_amount && empty($errMessage)) {
            $errMessage = "⚠️ Введенная максималка не может быть меньше либа равной минималке ({$setting->min_amount})";
        }

        if(empty($errMessage) === false) {
            $this->closeMenu();
            $this->menuText($errMessage)
                ->showMenu();
            return;
        }

        // меняем минималку
        try {
            $setting->update(['max_amount' => $amount]);

            $this->closeMenu();
            $this->showSettingsMenu($bot);
        } catch (\Exception $e) {
            $this->bot->sendMessage('Ошибка при обновлении БД, повторите попытку.');
            return;
        }
    }

    public function none(Nutgram $bot)
    {
    }

    public function endInlineMenu():void
    {
        $this->end();
    }

    /**
     * Получить массив кнопок для показа пользователю
     * - в зависимости откуда вызывается
     */
    private function getSettingsButtons(?int $settingEnumValue = null): array
    {
        $buttons = [];

        if ($settingEnumValue !== SettingEnum::EXCHANGER_FEE->value) {
            $buttons[] = InlineKeyboardButton::make(
                text: 'Изменить комиссию обменника',
                callback_data: '@showChangeExchangerFee'
            );
        }

        if ($settingEnumValue !== SettingEnum::MIN_AMOUNT->value) {
            $buttons[] = InlineKeyboardButton::make(
                text: 'Изменить минималку',
                callback_data: '@showChangeMinAmount'
            );
        }

        if ($settingEnumValue !== SettingEnum::MAX_AMOUNT->value) {
            $buttons[] = InlineKeyboardButton::make(
                text: 'Изменить максималку',
                callback_data: '@showChangeMaxAmount'
            );
        }

        $buttons[] = InlineKeyboardButton::make(
            text: 'Закрыть меню',
            callback_data: '@endInlineMenu'
        );

        return $buttons;
    }
}
