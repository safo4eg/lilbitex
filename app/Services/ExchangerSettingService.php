<?php

namespace App\Services;

use App\Enums\AssetEnum;
use App\Enums\WalletTypeEnum;
use App\Models\ExchangerSetting;
use App\Services\API\BlockStreamAPIService;
use App\Services\API\MempoolSpaceAPIService;

final class ExchangerSettingService
{
    private string $exchanger_btc_address;

    private BlockStreamAPIService $block_stram_service;

    private MempoolSpaceAPIService $mempool_space_service;

    public function __construct(BlockStreamAPIService $blockStreamAPI, MempoolSpaceAPIService $mempoolSpaceAPIService)
    {
        $this->exchanger_btc_address = config('app.exchanger_btc_address');
        $this->block_stram_service = $blockStreamAPI;
        $this->mempool_space_service = $mempoolSpaceAPIService;
    }

    /**
     * Обновить баланс настроек BTC
     */
    public function updateBalanceBTC(ExchangerSetting $setting): void
    {
        $balance = -1;

        // баланс BTC настроек
        if($setting->asset === AssetEnum::BTC->value) {
            switch ($setting->wallet_type) {
                case WalletTypeEnum::BIGMAFIA->value:
                    //
                    break;
                case WalletTypeEnum::EXTERNAL->value:
                    $balance = $this->block_stram_service->getAddressBalance($this->exchanger_btc_address);
                    break;
            }
        }

        if($balance !== -1) {
            $setting->balance = $balance;
            $setting->save();
        }
    }

    /**
     * Обновить комиссию сети
     * - для бигмафии она всегда 0, так как там процент берется за API
     */
    public function updateNetworkFee(ExchangerSetting $setting): bool
    {
        $networkFee = 0;

        // комиссия сети для BTC настроек обменника
        if($setting->asset === AssetEnum::BTC->value) {

            if($setting->wallet_type === WalletTypeEnum::EXTERNAL->value) {
                $feePerByte = $this->mempool_space_service->getRecommendedFees();

                if($feePerByte === -1) {
                    return false;
                }

                $networkFee = $feePerByte * 170; // сумма комисси для адресов bc1q
            }

            if($setting->wallet_type === WalletTypeEnum::BIGMAFIA->value) {
                // комиссия для бигмафии
            }
        }

        $setting->network_fee = $networkFee;
        $setting->save();
        return true;
    }
}