<?php

namespace App\Services;

use App\Enums\AssetEnum;
use App\Enums\WalletTypeEnum;
use App\Models\ExchangerSetting;
use App\Services\API\BlockStreamAPIService;

final class ExchangerSettingService
{
    private string $exchanger_btc_address;

    private BlockStreamAPIService $block_stream_api;

    public function __construct(BlockStreamAPIService $blockStreamAPI)
    {
        $this->exchanger_btc_address = config('app.exchanger_btc_address');
        $this->block_stream_api = $blockStreamAPI;
    }

    /**
     * Обновить баланс настроек BTC
     */
    public function updateBalanceBTC(ExchangerSetting $setting): void
    {
        $balance = -1;

        if($setting->asset === AssetEnum::BTC->value) {
            switch ($setting->wallet_type) {
                case WalletTypeEnum::BIGMAFIA->value:
                    //
                    break;
                case WalletTypeEnum::EXTERNAL->value:
                    $balance = $this->block_stream_api->getAddressBalance($this->exchanger_btc_address);
                    break;
            }
        }

        if($balance !== -1) {
            $setting->update(['balance' => $balance]);
        }
    }
}