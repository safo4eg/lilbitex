<?php

use Illuminate\Support\Facades\Schedule;

use App\Console\Commands\UpdateBtcRateCommand;

Schedule::command(UpdateBtcRateCommand::class, ['--pair=rub'])
    ->everyMinute()
    ->withoutOverlapping(1);
