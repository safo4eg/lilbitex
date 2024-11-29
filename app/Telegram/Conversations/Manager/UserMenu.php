<?php

namespace App\Telegram\Conversations\Manager;

use App\Enums\Manager\SettingEnum;
use App\Enums\Manager\UserMenuEnum;
use App\Enums\Order\StatusEnum;
use App\Models\ExchangerSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class UserMenu extends InlineMenu
{
    public int $user_id;

    public bool $is_banned;

    public function start(Nutgram $bot, int $id): void
    {
        $user = User::withTrashed()
            ->withSum(['orders as total_amount' => function ($query) {
                $query->where('status', StatusEnum::COMPLETED->value);
            }], 'amount')
            ->withCount(['orders as completed_orders_count' => function ($query) {
                $query->where('status', StatusEnum::COMPLETED->value);
            }])
            ->find($id);

        if(!$user) {
            $bot->sendMessage(text: 'Пользователя с таким ID не существует');
            $this->end();
            return;
        }

        $this->user_id = $user->id;
        $this->is_banned = $user->deleted_at ? true: false;

        $menuBuilder = $this->menuText(
            text: view('telegram.manager.user-menu', ['user' => $user]),
            opt: ['parse_mode' => ParseMode::HTML]
        )
            ->clearButtons();

        $buttons = $this->getUserMenuButtons();
        foreach ($buttons as $button) {
            $menuBuilder->addButtonRow($button);
        }

        $menuBuilder
            ->orNext('none')
            ->showMenu();
    }

    public function showChangePersonalDiscount(Nutgram $bot): void
    {
        $menuBuilder = $this->menuText('Введите персональную скидку от 0 до 5 %')
            ->clearButtons();

        $buttons = $this->getUserMenuButtons(UserMenuEnum::PERSONAL_DISCOUNT->value);
        foreach ($buttons as $button) {
            $menuBuilder->addButtonRow($button);
        }

        $menuBuilder->orNext('handleChangePersonalDiscount')
            ->showMenu();
    }

    public function handleChangePersonalDiscount(Nutgram $bot): void
    {
        $personalDiscount = $bot->message()->text;
        $errMessage = '';

        if(preg_match('/^[0-5]$/', $personalDiscount) === 0) {
            $errMessage = '⚠️ Введенная персональная скидка должна быть в диапазоне от 0 до 5!';
        }

        if(empty($errMessage) === false) {
            $this->closeMenu();
            $this->menuText($errMessage)
                ->showMenu();
            return;
        }

        try {
            User::withTrashed()
                ->where('id', $this->user_id)
                ->update(['personal_discount' => $personalDiscount]);

            $this->closeMenu();
            $this->start($bot, $this->user_id);
        } catch (\Exception $e) {
            $this->bot->sendMessage('Ошибка при обновлении БД, повторите попытку.');
            return;
        }
    }

    public function handleBan(Nutgram $bot): void
    {
        try {
            User::withTrashed()
                ->where('id', $this->user_id)
                ->update(['deleted_at' => Carbon::now()]);

            $this->closeMenu();
            $this->start($bot, $this->user_id);
        } catch (\Exception $e) {
            $this->bot->sendMessage('Ошибка при обновлении БД, повторите попытку.');
            return;
        }
    }

    public function handleUnban(Nutgram $bot): void
    {
        try {
            User::withTrashed()
                ->where('id', $this->user_id)
                ->update(['deleted_at' => null]);

            $this->closeMenu();
            $this->start($bot, $this->user_id);
        } catch (\Exception $e) {
            $this->bot->sendMessage('Ошибка при обновлении БД, повторите попытку.');
            return;
        }
    }

    public function none(Nutgram $bot)
    {
        $this->end();
    }

    public function exitMenu():void
    {
        $this->end();
    }

    private function getUserMenuButtons(?int $userMenuEnumValue = null): array
    {
        $buttons = [];

        if ($userMenuEnumValue !== UserMenuEnum::PERSONAL_DISCOUNT->value) {
            $buttons[] = InlineKeyboardButton::make(
                text: 'Изменить персональную скидку',
                callback_data: '@showChangePersonalDiscount'
            );
        }

        if ($userMenuEnumValue !== UserMenuEnum::BAN->value && !$this->is_banned) {
            $buttons[] = InlineKeyboardButton::make(
                text: 'Заблокировать',
                callback_data: '@handleBan'
            );
        }

        if ($userMenuEnumValue !== UserMenuEnum::UNBAN->value && $this->is_banned) {
            $buttons[] = InlineKeyboardButton::make(
                text: 'Разблокировать',
                callback_data: '@handleUnban'
            );
        }

        $buttons[] = InlineKeyboardButton::make(
            text: 'Закрыть меню',
            callback_data: '@exitMenu'
        );

        return $buttons;
    }
}
