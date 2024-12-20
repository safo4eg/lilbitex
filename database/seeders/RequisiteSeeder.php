<?php

namespace Database\Seeders;

use App\Enums\Requisite\StatusEnum;
use App\Models\Requisite;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RequisiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Requisite::create([
            'bank_name' => 'Т-банк',
            'phone' => '+7(923)409-35-30',
            'initials' => 'Станислав',
            'status' => StatusEnum::ENABLED->value
        ]);
    }
}
