<?php

use App\Modules\Dashboard\Http\Controllers\DashboardController;
use App\Modules\Katalog\Http\Controllers\DataMasterController;
use App\Modules\Katalog\Http\Controllers\TrainerController;
use App\Modules\Kehadiran\Http\Controllers\KehadiranController;
use App\Modules\Kehadiran\Http\Controllers\RekapKehadiranController;
use App\Modules\Penjadwalan\Http\Controllers\JadwalSesiController;
use App\Modules\Penjadwalan\Http\Controllers\MasterScheduleController;
use App\Modules\Penjadwalan\Http\Controllers\SesiController;
use App\Modules\Penjadwalan\Http\Controllers\ValidasiDurasiController;
use App\Modules\Peserta\Http\Controllers\PendaftaranController;
use App\Modules\Peserta\Http\Controllers\PesertaController;
use App\Modules\Peserta\Http\Controllers\ValidasiPesertaController;
use Illuminate\Support\Facades\Route;

// Pintu masuk: pengguna login diarahkan ke beranda sesuai perannya,
// selain itu ke halaman login (FR 01).
Route::get('/', function () {
    $user = auth()->user();

    return redirect()->route($user ? $user->homeRouteName() : 'login');
})->name('home');

// Dashboard: Admin dan Management (FR 14). Pembatasan dilakukan di server.
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'can:view-reports'])->name('dashboard');

// Jadwal saya dan kehadiran (FR 11): hanya Trainer yang terhubung ke satu data
// trainer; kepemilikan sesi diperiksa lagi lewat KehadiranPolicy::input.
Route::middleware(['auth', 'can:trainer'])->group(function () {
    Route::get('/jadwal-saya', [KehadiranController::class, 'jadwalSaya'])->name('jadwal-saya');
    Route::get('/kehadiran/{sesi}', [KehadiranController::class, 'form'])->name('kehadiran.form');
    Route::post('/kehadiran/{sesi}', [KehadiranController::class, 'simpan'])->name('kehadiran.simpan');
});

// Skema, batch, trainer (FR 02 sampai FR 04, FR 10): hanya Admin. Tanpa rute hapus.
Route::middleware(['auth', 'can:manage-operations'])->prefix('data-master')->group(function () {
    Route::get('/', [DataMasterController::class, 'index'])->name('data-master');
    Route::post('/batch', [DataMasterController::class, 'simpanBatch'])->name('data-master.batch.simpan');
    Route::post('/batch-bulanan', [DataMasterController::class, 'simpanBatchBulanan'])->name('data-master.batch.bulanan');
    Route::patch('/batch/{batch}/status', [DataMasterController::class, 'ubahStatusBatch'])->name('data-master.batch.status');
    Route::post('/trainer', [TrainerController::class, 'simpan'])->name('data-master.trainer.simpan');
});

// Rekap dan koreksi kehadiran (FR 12): hanya Admin.
Route::middleware(['auth', 'can:manage-operations'])->prefix('rekap-kehadiran')->group(function () {
    Route::get('/', [RekapKehadiranController::class, 'index'])->name('rekap-kehadiran');
    Route::get('/{peserta}/{batch}/koreksi', [RekapKehadiranController::class, 'form'])->name('rekap-kehadiran.koreksi');
    Route::post('/{peserta}/{batch}/koreksi', [RekapKehadiranController::class, 'simpan'])->name('rekap-kehadiran.koreksi.simpan');
});

// Master schedule online dan offline: hanya Admin, baca saja.
Route::get('/master-schedule', MasterScheduleController::class)
    ->middleware(['auth', 'can:manage-operations'])->name('master-schedule');

// Validasi durasi (FR 09): hanya Admin, baca saja.
Route::get('/validasi-durasi', [ValidasiDurasiController::class, 'index'])
    ->middleware(['auth', 'can:manage-operations'])->name('validasi-durasi');

// Peserta dan pendaftaran (FR 05, FR 06): hanya Admin.
Route::middleware(['auth', 'can:manage-operations'])->prefix('peserta')->group(function () {
    Route::get('/', [PesertaController::class, 'index'])->name('peserta');
    Route::post('/', [PesertaController::class, 'simpan'])->name('peserta.simpan');
    Route::post('/daftar', PendaftaranController::class)->name('peserta.daftar');
    Route::post('/impor', [PesertaController::class, 'impor'])->name('peserta.impor');
    Route::post('/validasi', ValidasiPesertaController::class)->name('peserta.validasi');
    Route::get('/{peserta}', [PesertaController::class, 'alur'])->whereNumber('peserta')->name('peserta.alur');
});

// Jadwal dan form sesi (FR 07, FR 08): hanya Admin. Tanpa rute hapus.
Route::middleware(['auth', 'can:manage-operations'])->group(function () {
    Route::get('/jadwal-sesi', [JadwalSesiController::class, 'index'])->name('jadwal-sesi');
    Route::get('/form-sesi/{sesi?}', [SesiController::class, 'form'])->name('form-sesi');
    Route::post('/jadwal-sesi', [SesiController::class, 'simpan'])->name('jadwal-sesi.simpan');
    Route::put('/jadwal-sesi/{sesi}', [SesiController::class, 'ubah'])->name('jadwal-sesi.ubah');
});

require __DIR__.'/auth.php';
