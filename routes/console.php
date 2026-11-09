<?php

use App\Console\Commands\BatalkanPesananLewatTenggatBayar;
use App\Console\Commands\BuatPengingatHariH;
use App\Console\Commands\PeringatanHariSatu;
use App\Console\Commands\UbahPesananHoldSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal otomatis harian pukul 00:01 WIB (lokal: php artisan schedule:work).
Schedule::command(UbahPesananHoldSchedule::class)->dailyAt('00:01')->timezone('Asia/Jakarta');
Schedule::command(BatalkanPesananLewatTenggatBayar::class)->dailyAt('00:01')->timezone('Asia/Jakarta');
Schedule::command(BuatPengingatHariH::class)->dailyAt('00:01')->timezone('Asia/Jakarta');
Schedule::command(PeringatanHariSatu::class)->dailyAt('00:01')->timezone('Asia/Jakarta');
Schedule::command('hotel:sinkron-google')->dailyAt('03:00')->timezone('Asia/Jakarta');
Schedule::command('database:cadangkan')->dailyAt('02:00')->timezone('Asia/Jakarta')->withoutOverlapping(30);
