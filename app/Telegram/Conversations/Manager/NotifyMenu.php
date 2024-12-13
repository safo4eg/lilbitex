<?php

namespace App\Telegram\Conversations\Manager;

use App\Jobs\SendBulkMessageJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Input\InputMediaPhoto;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class NotifyMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $this->menuText('Введите сообщение для рассылки.')
            ->addButtonRow(InlineKeyboardButton::make(
                text: 'Закрыть',
                callback_data: '@exitMenu'
            ))
            ->orNext('handleNotifyMessage')
            ->showMenu();
    }

    public function handleNotifyMessage(Nutgram $bot): void
    {
        $text = $bot->message()->text;
        // массив фотографий PhotoSize (см.https://core.telegram.org/bots/api#photosize)
        $photo = $bot->message()->photo;

        if(!$text && !$photo) {
            $bot->sendMessage(
                text: 'Некорректное сообщение для рассылки, попробуйте снова',
                chat_id: $bot->chatId()
            );
            return;
        }

        if($photo) {
            SendBulkMessageJob::dispatch(
                message: $bot->message()->caption,
                file_id: $photo[0]->file_id
            )
                ->onQueue('notifies');
        } else {
            SendBulkMessageJob::dispatch(message: $text)
                ->onQueue('notifies');
        }

        $bot->sendMessage(text: 'Успешная отправка рассылки', chat_id: $bot->chatId());
        $this->exitMenu($bot);
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
