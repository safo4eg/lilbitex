<?php

namespace App\Telegram\Conversations\Manager;

use App\Enums\Requisite\StatusEnum;
use App\Models\ExchangerSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use App\Models\Requisite;

class RequisiteMenu extends InlineMenu
{
    /**
     * Идентификатор активных реквизитов
     */
    public ?int $enabled_requisite_id = null;

    public string $bank_name;
    public string $phone;
    public string $first_name;
    public string $last_name;
    public string $middle_name;

    public function start(Nutgram $bot)
    {
        $this->clearButtons();

        $enabledRequisite = Requisite::where('status', StatusEnum::ENABLED->value)
            ->first();

        $menuBuilder = $this->menuText(view('telegram.manager.requisite-menu', ['enabledRequisite' => $enabledRequisite]));
        $menuBuilder = $menuBuilder->addButtonRow(InlineKeyboardButton::make(
            text: 'Обновить реквизиты',
            callback_data: '@updateRequisite'
        ));

        if($enabledRequisite) {
            $this->enabled_requisite_id = $enabledRequisite->id;
            $menuBuilder = $menuBuilder->addButtonRow(InlineKeyboardButton::make(
                text: 'Сбросить реквизиты',
                callback_data: '@deleteRequisite'
            ));
        }

        $menuBuilder->addButtonRow(InlineKeyboardButton::make(
            text: 'Закрыть',
            callback_data: '@exitMenu'
        ));

        $menuBuilder
            ->orNext('none')
            ->showMenu();
    }

    /**
     * Первое меню обновление реквизитов
     */
    public function updateRequisite(Nutgram $bot): void
    {
        $this->clearButtons()
            ->closeMenu();

        $this->menuText('Введите название банка')
            ->addButtonRow($this->getCancelButton())
            ->orNext('handleBankName')
            ->showMenu();
    }

    public function handleBankName(Nutgram $bot): void
    {
        $bankName = $bot->message()->text;

        $validator = Validator::make(
            ['bank_name' => $bankName],
            ['bank_name' => ['required', 'string', 'max:32']]
        );

        if($validator->fails()) {
            $this->closeMenu();
            $this->menuText('⚠️ Название банка слишком большое')
                ->showMenu();
            return;
        }

        $this->bank_name = $bankName;
        $this->requestPhone();
    }

    public function requestPhone(): void
    {
        $this->clearButtons()
            ->closeMenu();

        $this->menuText('Введите номер телефона в формате: +7(XXX)XXX-XX-XX')
            ->addButtonRow($this->getCancelButton())
            ->orNext('handlePhone')
            ->showMenu();
    }

    public function handlePhone(Nutgram $bot): void
    {
        $phone = $bot->message()->text;

        $validator = Validator::make(['phone' => $phone], [
            'phone' => 'required|regex:/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/',
        ]);

        if ($validator->fails()) {
            $this->closeMenu();
            $this->menuText('⚠️ Некорректный формат, он должен соотвествовать: +7(XXX)XXX-XX-XX')
                ->showMenu();
            return;
        }

        $this->phone = $phone;
        $this->requestFio();
    }

    public function requestFio(): void
    {
        $this->clearButtons()
            ->closeMenu();

        $this->menuText('Введите ФИО.')
            ->addButtonRow($this->getCancelButton())
            ->orNext('handleFio')
            ->showMenu();
    }

    public function handleFio(Nutgram $bot): void
    {
        $initials = trim($bot->message()->text);

        $validator = Validator::make(['fio' => $initials], [
            'fio' => ['required', 'max:150'],
        ]);

        if ($validator->fails()) {
            $this->closeMenu();
            $this->menuText('⚠️ Некорректный формат: максимально 50 символов.')
                ->showMenu();
            return;
        }

        try {
            DB::beginTransaction();

            if($this->enabled_requisite_id) {
                Requisite::where('id', $this->enabled_requisite_id)
                    ->update(['status' => StatusEnum::DISABLED->value]);
            }

            Requisite::create([
                'bank_name' => $this->bank_name,
                'phone' => $this->phone,
                'initials' => $initials,
                'status' => StatusEnum::ENABLED->value
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->bot->sendMessage(text: 'Ошибка в БД, попробуйте заново.');
        }

        $this->clearButtons()
            ->closeMenu();
        $this->start($bot);
    }

    /**
     * Отвечает за сброс реквизитов
     */
    public function deleteRequisite(Nutgram $bot)
    {
        $this->menuText(view('telegram.manager.delete-requisite'))
            ->clearButtons()
            ->addButtonRow(InlineKeyboardButton::make(
                text: 'Да, сбросить реквизиты',
                callback_data: '@handleDeleteRequisite'
            ))
            ->addButtonRow($this->getCancelButton())
            ->orNext('none')
            ->showMenu();
    }

    public function handleDeleteRequisite(Nutgram $bot): void
    {
        Requisite::where('status', StatusEnum::ENABLED->value)
            ->update(['status' => StatusEnum::DISABLED->value]);

        $this->enabled_requisite_id = null;

        $this->clearButtons()
            ->closeMenu();
        $this->start($bot);
    }

    public function none(Nutgram $bot)
    {
    }

    public function exitMenu(Nutgram $bot): void
    {
        $this->end();
    }

    private function getCancelButton(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(
            text: 'Отмена',
            callback_data: '@start'
        );
    }
}
