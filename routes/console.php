<?php

use App\Modules\Penjadwalan\Actions\PerbaruiStatusSesi;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sesi:perbarui-status', function (PerbaruiStatusSesi $perbarui) {
    $this->info($perbarui->execute().' sesi diperbarui.');
})->purpose('Perbarui status sesi menurut tanggal, jam, dan kuota peserta');

Schedule::command('sesi:perbarui-status')->everyMinute();
