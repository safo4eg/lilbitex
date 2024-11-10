<?php

namespace Database\Seeders;

use App\Enums\Requisite\StatusEnum;
use App\Models\ExchangerSettings;
use App\Models\Requisite;
use Illuminate\Database\Seeder;

class ExchangerSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExchangerSettings::create([]);
    }
}
