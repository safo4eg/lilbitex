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
        Log::channel('single')->debug($bankName);
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

        $this->menuText('Введите ФИО, разделя через пробел. Например: Иванов Иван Иванович')
            ->addButtonRow($this->getCancelButton())
            ->orNext('handleFio')
            ->showMenu();
    }

    public function handleFio(Nutgram $bot): void
    {
        $fio = $bot->message()->text;

        $validator = Validator::make(['fio' => $fio], [
            'fio' => 'required|regex:/^[\wа-яА-ЯёЁ]{1,50} [\wа-яА-ЯёЁ]{1,50} [\wа-яА-ЯёЁ]{1,50}$/u',
        ]);

        if ($validator->fails()) {
            $this->closeMenu();
            $this->menuText('⚠️ Некорректный формат, он должен соотвествовать: Иванов Иван Иванович')
                ->showMenu();
            return;
        }

        $words = explode(' ', $fio);
        $this->last_name = $words[0];
        $this->first_name = $words[1];
        $this->middle_name = $words[2];

        try {
            DB::beginTransaction();

            if($this->enabled_requisite_id) {
                Requisite::where('id', $this->enabled_requisite_id)
                    ->update(['status' => StatusEnum::DISABLED->value]);
            }

            Requisite::create([
                'bank_name' => $this->bank_name,
                'phone' => $this->phone,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'middle_name' => $this->middle_name,
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
    public function deleteRequisite()
    {

    }

    public function none(Nutgram $bot)
    {
    }

    private function getCancelButton(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(
            text: 'Отмена',
            callback_data: '@start'
        );
    }
}
