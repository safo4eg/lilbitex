<?php

namespace Database\Seeders;

use App\Enums\AssetEnum;
use App\Enums\Requisite\StatusEnum;
use App\Enums\WalletTypeEnum;
use App\Models\ExchangerSetting;
use App\Models\Requisite;
use Illuminate\Database\Seeder;

class ExchangerSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExchangerSetting::insert([
            [
                'asset' => AssetEnum::BTC->value,
                'wallet_type' => WalletTypeEnum::EXTERNAL->value
            ],
            [
                'asset' => AssetEnum::BTC->value,
                'wallet_type' => WalletTypeEnum::BIGMAFIA->value
            ]
        ]);
    }
}
