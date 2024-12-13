<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class SendBulkMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 1;

    public string $message;
    public ?string $file_id;
    public function __construct(string $message, ?string $file_id = null)
    {
        $this->message = $message;
        $this->file_id = $file_id;
        $this->onQueue('notifies');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot = app(Nutgram::class);
        $chats = User::pluck('chat_id')->toArray();

        $bulkMessengerBuilder = $bot->getBulkMessenger()
            ->setChats($chats)
            ->setInterval(3);

        if($this->file_id) {
            $bulkMessengerBuilder->using(function (Nutgram $bot, int $chatId) {
                $bot->sendPhoto(
                    photo: $this->file_id,
                    caption: $this->message,
                    chat_id: $chatId,
                );
            });
        } else {
            $bulkMessengerBuilder
                ->setText($this->message);
        }

        $bulkMessengerBuilder->startSync();
    }
}
