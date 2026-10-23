<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('shopkey:sync-bank-transactions --source=scheduler')
    ->name('shopkey-bank-deposit-sync')
    ->everyMinute()
    ->withoutOverlapping(2);
