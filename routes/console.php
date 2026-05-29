<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:run')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('ldap:sync --all')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('routers:check-status')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('subscriptions:sync-connection-status')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('subscriptions:kick-suspended-online')
    ->everyFiveMinutes()
    ->withoutOverlapping();
