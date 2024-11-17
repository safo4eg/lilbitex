<?php
/**
 * Здесь используем по максимуму данные из БД
 * Потому что при тригере на стороне сервера $bot бдет пустой!
 */
namespace App\Telegram\Conversations\Order\Buy;

use App\Telegram\Conversations\InlineMenuWithSaveMessageId;
use SergiX44\Nutgram\Nutgram;

class CancelledOrderMenu extends InlineMenuWithSaveMessageId
{
    public function start()
    {
        $this->menuText(text: 'Счет был отменен')
            ->showMenu();
    }

    // будет вызываться если не нажата кнопка
    public function none(Nutgram $bot)
    {
        $bot->sendMessage('Bye!');
        $this->end();
    }
}