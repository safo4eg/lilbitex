<?php

namespace App\Providers;

use App\Services\API\BlockStreamAPIService;
use App\Services\API\MempoolSpaceAPIService;
use App\Services\BTCService;
use App\Services\ExchangerSettingService;
use App\Services\OrderService;
use App\Telegram\Services\BotService;
use App\Telegram\Services\ManagerService;
use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BlockStreamAPIService::class, function () {
            return new BlockStreamAPIService();
        });

        $this->app->singleton(MempoolSpaceAPIService::class, function () {
            return new MempoolSpaceAPIService();
        });

        $this->app->singleton(BTCService::class, function () {
            return new BTCService($this->app->make(BlockStreamAPIService::class));
        });

        $this->app->singleton(ExchangerSettingService::class, function () {
            return new ExchangerSettingService(
                $this->app->make(BlockStreamAPIService::class),
                $this->app->make(MempoolSpaceAPIService::class)
            );
        });

        $this->app->singleton(OrderService::class, function () {
            return new OrderService();
        });

        $this->app->singleton(ManagerService::class, function () {
            return new ManagerService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Nutgram::macro('sendMessageWithSaveId', function (...$args) {
            $message = $this->sendMessage(...$args);
            BotService::saveBotLastMessageId($this, $message->message_id, $args['chat_id']);
        });
    }
}
