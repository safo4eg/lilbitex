<?php

namespace App\Console\Commands;

use App\Enums\AssetEnum;
use App\Models\ExchangerSetting;
use App\Services\BTCService;
use Illuminate\Console\Command;

class UpdateBtcRateCommand extends Command
{
    protected $signature = 'btc:update:rate {--pair=}';

    protected $description = 'Обновление курса битка';

    public function handle(BTCService $BTCService)
    {
        $pair = $this->option('pair');

        if($pair === 'rub' || $pair === null) {
            $rate = $BTCService->getExchangeRateToRub();

            if($rate !== -1) {
                ExchangerSetting::where('asset', AssetEnum::BTC->value)
                    ->update(['rate' => $rate]);
            }
        }

    }
}
