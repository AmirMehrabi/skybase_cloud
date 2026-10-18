<?php

use App\Models\RadiusPostAuthRecord;
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

Schedule::command('monitoring:collect-router-health')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('monitoring:collect-subscription-bandwidth')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('subscriptions:sync-connection-status')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('subscriptions:kick-suspended-online')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('usage:reconcile')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('usage:rollover')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('monitoring:prune-orphans')
    ->daily()
    ->withoutOverlapping();

Schedule::command('model:prune', [
    '--model' => [RadiusPostAuthRecord::class],
])
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('security:harden-storage --prune')->daily()->withoutOverlapping()->when(fn (): bool => config('security.maintain_storage'));

Schedule::command('ipdr:sync')->everyMinute()->withoutOverlapping(30)->when(fn () => config('ipdr.enabled'));
